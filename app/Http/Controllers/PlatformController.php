<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PlatformController extends Controller
{
    public function home(): View
    {
        $diaspora = app('currentDiaspora');

        return $this->page('home', [
            'jobs' => DB::table('job_vacancies')->where('diaspora_id', $diaspora->id)->where('status', 'published')->latest('published_at')->limit(6)->get(),
            'posts' => DB::table('posts')->join('users', 'users.id', '=', 'posts.user_id')->where('posts.diaspora_id', $diaspora->id)->where('posts.status', 'published')->select('posts.*', 'users.name as user_name')->latest('posts.created_at')->limit(6)->get(),
            'safetyArticles' => DB::table('safety_articles')->where('diaspora_id', $diaspora->id)->where('is_published', true)->orderByDesc('emergency')->limit(4)->get(),
        ]);
    }

    public function loginForm(): View
    {
        return $this->page('login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate(['login' => ['required', 'string'], 'password' => ['required', 'string']]);
        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (!Auth::attempt([$field => $data['login'], 'password' => $data['password'], 'status' => 'active'], $request->boolean('remember'))) {
            return back()->withErrors(['login' => 'Неверный логин или пароль.'])->onlyInput('login');
        }

        $request->session()->regenerate();

        return redirect()->route('home');
    }

    public function registerForm(): View
    {
        return $this->page('register');
    }

    public function register(Request $request): RedirectResponse
    {
        $diaspora = app('currentDiaspora');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone'],
            'birth_date' => ['required', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()],
            'gender' => ['required', 'in:male,female,hidden'],
            'city' => ['nullable', 'string', 'max:120'],
            'relationship_goal' => ['required', 'in:communication,friendship,family,work,networking'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'terms' => ['accepted'],
        ]);

        $user = DB::transaction(function () use ($data, $diaspora) {
            $user = User::create([
                'diaspora_id' => $diaspora->id,
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'role' => 'user',
                'status' => 'active',
                'preferred_locale' => app()->getLocale(),
            ]);

            DB::table('user_profiles')->insert([
                'user_id' => $user->id,
                'birth_date' => $data['birth_date'],
                'gender' => $data['gender'],
                'city' => $data['city'] ?? null,
                'relationship_goal' => $data['relationship_goal'],
                'visibility' => 'public',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('community');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function community(Request $request): View
    {
        $diaspora = app('currentDiaspora');

        $people = DB::table('users')
            ->join('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->where('users.diaspora_id', $diaspora->id)
            ->where('users.status', 'active')
            ->where('user_profiles.visibility', 'public')
            ->when(Auth::check(), fn ($q) => $q->where('users.id', '!=', Auth::id()))
            ->when($request->filled('city'), fn ($q) => $q->where('user_profiles.city', 'like', '%'.$request->input('city').'%'))
            ->when($request->filled('gender'), fn ($q) => $q->where('user_profiles.gender', $request->input('gender')))
            ->when($request->filled('goal'), fn ($q) => $q->where('user_profiles.relationship_goal', $request->input('goal')))
            ->select('users.id', 'users.name', 'user_profiles.city', 'user_profiles.profession', 'user_profiles.relationship_goal', 'user_profiles.is_verified')
            ->latest('users.created_at')
            ->paginate(18, ['*'], 'people')
            ->withQueryString();

        $posts = DB::table('posts')
            ->join('users', 'users.id', '=', 'posts.user_id')
            ->where('posts.diaspora_id', $diaspora->id)
            ->where('posts.status', 'published')
            ->select('posts.*', 'users.name as user_name')
            ->latest('posts.created_at')
            ->paginate(15, ['*'], 'posts');

        return $this->page('community', compact('people', 'posts'));
    }

    public function storePost(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:general,meeting,work,housing,help'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        DB::table('posts')->insert([
            'diaspora_id' => app('currentDiaspora')->id,
            'user_id' => $request->user()->id,
            'type' => $data['type'],
            'body' => $data['body'],
            'status' => 'published',
            'comments_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Публикация добавлена.');
    }

    public function startConversation(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 422);
        abort_unless($user->diaspora_id === app('currentDiaspora')->id && $user->status === 'active', 404);

        $blocked = DB::table('user_blocks')
            ->where(fn ($q) => $q->where('user_id', $request->user()->id)->where('blocked_user_id', $user->id))
            ->orWhere(fn ($q) => $q->where('user_id', $user->id)->where('blocked_user_id', $request->user()->id))
            ->exists();
        abort_if($blocked, 403);

        $directKey = collect([$request->user()->id, $user->id])->sort()->implode(':');

        $conversationId = DB::transaction(function () use ($request, $user, $directKey) {
            $conversation = DB::table('conversations')
                ->where('diaspora_id', app('currentDiaspora')->id)
                ->where('direct_key', $directKey)
                ->first();

            if (!$conversation) {
                $id = DB::table('conversations')->insertGetId([
                    'diaspora_id' => app('currentDiaspora')->id,
                    'type' => 'direct',
                    'direct_key' => $directKey,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('conversation_members')->insert([
                    ['conversation_id' => $id, 'user_id' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()],
                    ['conversation_id' => $id, 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()],
                ]);

                return $id;
            }

            return $conversation->id;
        });

        return redirect()->route('conversation', $conversationId);
    }

    public function messages(Request $request): View
    {
        $conversations = DB::table('conversations')
            ->join('conversation_members as mine', function ($join) use ($request) {
                $join->on('mine.conversation_id', '=', 'conversations.id')->where('mine.user_id', '=', $request->user()->id);
            })
            ->join('conversation_members as other_member', function ($join) use ($request) {
                $join->on('other_member.conversation_id', '=', 'conversations.id')->where('other_member.user_id', '!=', $request->user()->id);
            })
            ->join('users as other_user', 'other_user.id', '=', 'other_member.user_id')
            ->where('conversations.diaspora_id', app('currentDiaspora')->id)
            ->select('conversations.*', 'other_user.name as other_name')
            ->latest('conversations.updated_at')
            ->paginate(30);

        return $this->page('messages', compact('conversations'));
    }

    public function conversation(Request $request, int $conversation): View
    {
        $this->assertConversationMember($request, $conversation);

        $other = DB::table('conversation_members')
            ->join('users', 'users.id', '=', 'conversation_members.user_id')
            ->where('conversation_id', $conversation)
            ->where('user_id', '!=', $request->user()->id)
            ->select('users.id', 'users.name')
            ->first();

        $chatMessages = DB::table('messages')
            ->join('users', 'users.id', '=', 'messages.sender_user_id')
            ->where('conversation_id', $conversation)
            ->select('messages.*', 'users.name as sender_name')
            ->oldest('messages.created_at')
            ->paginate(50);

        DB::table('conversation_members')->where('conversation_id', $conversation)->where('user_id', $request->user()->id)->update(['last_read_at' => now()]);

        return $this->page('conversation', compact('conversation', 'other', 'chatMessages'));
    }

    public function sendMessage(Request $request, int $conversation): RedirectResponse
    {
        $this->assertConversationMember($request, $conversation);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        DB::table('messages')->insert([
            'conversation_id' => $conversation,
            'sender_user_id' => $request->user()->id,
            'body' => $data['body'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('conversations')->where('id', $conversation)->update(['updated_at' => now()]);

        return back();
    }

    public function jobs(Request $request): View
    {
        $jobs = DB::table('job_vacancies')
            ->leftJoin('employers', 'employers.id', '=', 'job_vacancies.employer_id')
            ->where('job_vacancies.diaspora_id', app('currentDiaspora')->id)
            ->where('job_vacancies.status', 'published')
            ->when($request->filled('search'), fn ($q) => $q->where('job_vacancies.title', 'like', '%'.$request->input('search').'%'))
            ->when($request->filled('city'), fn ($q) => $q->where('job_vacancies.city', 'like', '%'.$request->input('city').'%'))
            ->when($request->boolean('official'), fn ($q) => $q->where('official_employment', true))
            ->when($request->boolean('housing'), fn ($q) => $q->where('housing_provided', true))
            ->select('job_vacancies.*', 'employers.name as employer_name', 'employers.verification_status')
            ->latest('published_at')
            ->paginate(20)
            ->withQueryString();

        return $this->page('jobs', compact('jobs'));
    }

    public function storeJob(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employer_name' => ['required', 'string', 'max:190'],
            'tax_id' => ['nullable', 'string', 'max:30'],
            'title' => ['required', 'string', 'max:190'],
            'description' => ['required', 'string', 'max:10000'],
            'city' => ['required', 'string', 'max:120'],
            'salary_from' => ['nullable', 'numeric', 'min:0'],
            'salary_to' => ['nullable', 'numeric', 'gte:salary_from'],
            'contact_phone' => ['required', 'string', 'max:30'],
        ]);

        DB::transaction(function () use ($request, $data) {
            $employerId = DB::table('employers')->insertGetId([
                'diaspora_id' => app('currentDiaspora')->id,
                'owner_user_id' => $request->user()->id,
                'name' => $data['employer_name'],
                'tax_id' => $data['tax_id'] ?? null,
                'phone' => $data['contact_phone'],
                'city' => $data['city'],
                'verification_status' => 'unverified',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('job_vacancies')->insert([
                'diaspora_id' => app('currentDiaspora')->id,
                'employer_id' => $employerId,
                'title' => $data['title'],
                'description' => $data['description'],
                'city' => $data['city'],
                'salary_from' => $data['salary_from'] ?? null,
                'salary_to' => $data['salary_to'] ?? null,
                'contact_phone' => $data['contact_phone'],
                'official_employment' => $request->boolean('official_employment'),
                'housing_provided' => $request->boolean('housing_provided'),
                'status' => 'moderation',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('success', 'Вакансия отправлена на модерацию.');
    }

    public function letters(): View
    {
        $templates = DB::table('letter_templates')->where('diaspora_id', app('currentDiaspora')->id)->where('is_active', true)->orderBy('category')->get();

        return $this->page('letters', compact('templates'));
    }

    public function letterPreview(Request $request, string $slug): View
    {
        $template = DB::table('letter_templates')->where('diaspora_id', app('currentDiaspora')->id)->where('slug', $slug)->where('is_active', true)->first();
        abort_unless($template, 404);

        $fields = json_decode($template->fields, true) ?: [];
        $rules = [];
        foreach ($fields as $field) {
            $rules[$field['name']] = [($field['required'] ?? false) ? 'required' : 'nullable', 'string', 'max:2000'];
        }

        $data = $request->validate($rules);
        $bodies = json_decode($template->body_template, true) ?: [];
        $body = $bodies[app()->getLocale()] ?? $bodies['ru'] ?? '';

        foreach ($data as $key => $value) {
            $body = str_replace('{{'.$key.'}}', e($value ?? ''), $body);
        }

        return $this->page('letter_preview', compact('template', 'body'));
    }

    public function safety(): View
    {
        $articles = DB::table('safety_articles')->where('diaspora_id', app('currentDiaspora')->id)->where('is_published', true)->orderByDesc('emergency')->paginate(20);

        return $this->page('safety', compact('articles'));
    }

    public function reportIncident(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', 'in:fraud,extortion,documents,violence,missing_person,trafficking,detention,wage_theft,other'],
            'city' => ['nullable', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:10000'],
            'contact' => ['nullable', 'string', 'max:190'],
        ]);

        DB::table('incident_reports')->insert([
            'diaspora_id' => app('currentDiaspora')->id,
            'user_id' => $request->user()->id,
            'category' => $data['category'],
            'city' => $data['city'] ?? null,
            'description' => $data['description'],
            'allow_contact' => $request->boolean('allow_contact'),
            'contact' => $request->boolean('allow_contact') ? ($data['contact'] ?? null) : null,
            'status' => 'new',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Сообщение передано модератору. При срочной угрозе звоните 112.');
    }

    private function page(string $page, array $data = []): View
    {
        return view('platform', array_merge($data, ['page' => $page]));
    }

    private function assertConversationMember(Request $request, int $conversation): void
    {
        $exists = DB::table('conversations')
            ->join('conversation_members', 'conversation_members.conversation_id', '=', 'conversations.id')
            ->where('conversations.id', $conversation)
            ->where('conversations.diaspora_id', app('currentDiaspora')->id)
            ->where('conversation_members.user_id', $request->user()->id)
            ->exists();

        abort_unless($exists, 403);
    }
}
