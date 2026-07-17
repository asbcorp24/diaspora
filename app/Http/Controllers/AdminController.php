<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(Request $request): View
    {
        $role = $request->user()->role;
        $sections = $this->allowedSections($role);
        $section = (string) $request->input('section', 'dashboard');
        abort_unless(array_key_exists($section, $sections), 403);

        $diaspora = app('currentDiaspora');
        $diasporaId = $diaspora->id;
        $isSuperadmin = $role === 'superadmin';
        $data = [];

        if ($section === 'dashboard') {
            $data['stats'] = [
                'users' => DB::table('users')->when(!$isSuperadmin, fn ($q) => $q->where('diaspora_id', $diasporaId))->count(),
                'active_users' => DB::table('users')->when(!$isSuperadmin, fn ($q) => $q->where('diaspora_id', $diasporaId))->where('status', 'active')->count(),
                'jobs_moderation' => DB::table('job_vacancies')->where('diaspora_id', $diasporaId)->where('status', 'moderation')->count(),
                'posts' => DB::table('posts')->where('diaspora_id', $diasporaId)->count(),
                'reviews_moderation' => DB::table('employer_reviews')->where('diaspora_id', $diasporaId)->where('status', 'moderation')->count()
                    + DB::table('rental_reviews')->where('diaspora_id', $diasporaId)->where('status', 'moderation')->count(),
                'incidents_new' => DB::table('incident_reports')->where('diaspora_id', $diasporaId)->where('status', 'new')->count(),
                'review_reports_new' => DB::table('review_reports')->where('diaspora_id', $diasporaId)->where('status', 'new')->count(),
                'news_published' => DB::table('news')->where('diaspora_id', $diasporaId)->where('is_published', true)->count(),
            ];
            $data['latestUsers'] = DB::table('users')
                ->when(!$isSuperadmin, fn ($q) => $q->where('diaspora_id', $diasporaId))
                ->latest()->limit(8)->get();
            $data['latestIncidents'] = DB::table('incident_reports')
                ->where('diaspora_id', $diasporaId)->latest()->limit(8)->get();
        }

        if ($section === 'users') {
            $this->requireRole($request, ['admin', 'superadmin']);
            $data['users'] = DB::table('users')
                ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'users.id')
                ->leftJoin('diasporas', 'diasporas.id', '=', 'users.diaspora_id')
                ->when(!$isSuperadmin, fn ($q) => $q->where('users.diaspora_id', $diasporaId))
                ->when($request->filled('search'), function ($q) use ($request): void {
                    $search = trim((string) $request->input('search'));
                    $q->where(function ($n) use ($search): void {
                        $n->where('users.name', 'like', '%'.$search.'%')
                            ->orWhere('users.email', 'like', '%'.$search.'%')
                            ->orWhere('users.phone', 'like', '%'.$search.'%');
                    });
                })
                ->when($request->filled('role'), fn ($q) => $q->where('users.role', $request->input('role')))
                ->when($request->filled('status'), fn ($q) => $q->where('users.status', $request->input('status')))
                ->select('users.*', 'user_profiles.city', 'user_profiles.is_verified', 'diasporas.name as diaspora_name')
                ->latest('users.created_at')->paginate(25)->withQueryString();
        }

        if ($section === 'jobs') {
            $data['jobs'] = DB::table('job_vacancies')
                ->join('employers', 'employers.id', '=', 'job_vacancies.employer_id')
                ->leftJoin('users', 'users.id', '=', 'employers.owner_user_id')
                ->where('job_vacancies.diaspora_id', $diasporaId)
                ->when($request->filled('status'), fn ($q) => $q->where('job_vacancies.status', $request->input('status')))
                ->when($request->filled('search'), function ($q) use ($request): void {
                    $search = trim((string) $request->input('search'));
                    $q->where(fn ($n) => $n->where('job_vacancies.title', 'like', '%'.$search.'%')->orWhere('employers.name', 'like', '%'.$search.'%'));
                })
                ->select('job_vacancies.*', 'employers.name as employer_name', 'users.name as owner_name')
                ->latest('job_vacancies.created_at')->paginate(25)->withQueryString();
        }

        if ($section === 'posts') {
            $data['posts'] = DB::table('posts')
                ->join('users', 'users.id', '=', 'posts.user_id')
                ->where('posts.diaspora_id', $diasporaId)
                ->when($request->filled('status'), fn ($q) => $q->where('posts.status', $request->input('status')))
                ->when($request->filled('search'), fn ($q) => $q->where('posts.body', 'like', '%'.trim((string) $request->input('search')).'%'))
                ->select('posts.*', 'users.name as user_name')
                ->latest('posts.created_at')->paginate(25)->withQueryString();
        }

        if ($section === 'reviews') {
            $data['employerReviews'] = DB::table('employer_reviews')
                ->join('employers', 'employers.id', '=', 'employer_reviews.employer_id')
                ->join('users', 'users.id', '=', 'employer_reviews.user_id')
                ->where('employer_reviews.diaspora_id', $diasporaId)
                ->when($request->filled('status'), fn ($q) => $q->where('employer_reviews.status', $request->input('status')))
                ->select('employer_reviews.*', 'employers.name as subject_name', 'users.name as reviewer_name')
                ->latest('employer_reviews.created_at')->paginate(15, ['*'], 'employer_page')->withQueryString();
            $data['rentalReviews'] = DB::table('rental_reviews')
                ->join('rental_properties', 'rental_properties.id', '=', 'rental_reviews.rental_property_id')
                ->join('landlords', 'landlords.id', '=', 'rental_properties.landlord_id')
                ->join('users', 'users.id', '=', 'rental_reviews.user_id')
                ->where('rental_reviews.diaspora_id', $diasporaId)
                ->when($request->filled('status'), fn ($q) => $q->where('rental_reviews.status', $request->input('status')))
                ->select('rental_reviews.*', 'landlords.display_name as subject_name', 'rental_properties.city', 'users.name as reviewer_name')
                ->latest('rental_reviews.created_at')->paginate(15, ['*'], 'rental_page')->withQueryString();
        }

        if ($section === 'review_reports') {
            $data['reviewReports'] = DB::table('review_reports')
                ->join('users', 'users.id', '=', 'review_reports.reporter_user_id')
                ->where('review_reports.diaspora_id', $diasporaId)
                ->when($request->filled('status'), fn ($q) => $q->where('review_reports.status', $request->input('status')))
                ->select('review_reports.*', 'users.name as reporter_name')
                ->latest('review_reports.created_at')->paginate(25)->withQueryString();
        }

        if ($section === 'incidents') {
            $data['incidents'] = DB::table('incident_reports')
                ->leftJoin('users', 'users.id', '=', 'incident_reports.user_id')
                ->where('incident_reports.diaspora_id', $diasporaId)
                ->when($request->filled('status'), fn ($q) => $q->where('incident_reports.status', $request->input('status')))
                ->when($request->filled('category'), fn ($q) => $q->where('incident_reports.category', $request->input('category')))
                ->select('incident_reports.*', 'users.name as user_name')
                ->latest('incident_reports.created_at')->paginate(25)->withQueryString();
        }

        if ($section === 'news') {
            $data['newsItems'] = DB::table('news')
                ->leftJoin('users', 'users.id', '=', 'news.author_user_id')
                ->where('news.diaspora_id', $diasporaId)
                ->when($request->filled('status'), function ($q) use ($request): void {
                    $request->input('status') === 'published' ? $q->where('news.is_published', true) : $q->where('news.is_published', false);
                })
                ->select('news.*', 'users.name as author_name')
                ->orderByDesc('news.is_pinned')->latest('news.created_at')->paginate(20)->withQueryString();
        }

        if ($section === 'employers') {
            $this->requireRole($request, ['admin', 'superadmin']);
            $data['employers'] = DB::table('employers')
                ->where('diaspora_id', $diasporaId)
                ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.trim((string) $request->input('search')).'%'))
                ->latest()->paginate(25)->withQueryString();
        }

        if ($section === 'rentals') {
            $this->requireRole($request, ['admin', 'superadmin']);
            $data['landlords'] = DB::table('landlords')
                ->where('diaspora_id', $diasporaId)
                ->when($request->filled('search'), fn ($q) => $q->where('display_name', 'like', '%'.trim((string) $request->input('search')).'%'))
                ->latest()->paginate(25)->withQueryString();
        }

        if ($section === 'letters') {
            $this->requireRole($request, ['admin', 'superadmin']);
            $data['letterTemplates'] = DB::table('letter_templates')->where('diaspora_id', $diasporaId)->orderBy('category')->paginate(20)->withQueryString();
        }

        if ($section === 'safety') {
            $this->requireRole($request, ['admin', 'superadmin']);
            $data['safetyArticles'] = DB::table('safety_articles')->where('diaspora_id', $diasporaId)->orderByDesc('emergency')->latest()->paginate(20)->withQueryString();
        }

        if ($section === 'diasporas') {
            $this->requireRole($request, ['superadmin']);
            $data['diasporas'] = DB::table('diasporas')->orderBy('name')->get();
            $data['domains'] = DB::table('diaspora_domains')->join('diasporas', 'diasporas.id', '=', 'diaspora_domains.diaspora_id')
                ->select('diaspora_domains.*', 'diasporas.name as diaspora_name')->orderBy('domain')->get();
        }

        if ($section === 'audit') {
            $this->requireRole($request, ['admin', 'superadmin']);
            $data['auditLogs'] = DB::table('admin_audit_logs')
                ->leftJoin('users', 'users.id', '=', 'admin_audit_logs.actor_user_id')
                ->leftJoin('diasporas', 'diasporas.id', '=', 'admin_audit_logs.diaspora_id')
                ->when(!$isSuperadmin, fn ($q) => $q->where('admin_audit_logs.diaspora_id', $diasporaId))
                ->select('admin_audit_logs.*', 'users.name as actor_name', 'diasporas.name as diaspora_name')
                ->latest('admin_audit_logs.created_at')->paginate(50)->withQueryString();
        }

        return view('admin.index', array_merge($data, compact('section', 'sections', 'diaspora')));
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $this->requireRole($request, ['admin', 'superadmin']);
        $this->assertUserScope($request, $user);

        $rules = ['status' => ['required', Rule::in(['active', 'blocked', 'suspended'])], 'is_verified' => ['nullable', 'boolean']];
        if ($request->user()->role === 'superadmin') {
            $rules['role'] = ['required', Rule::in(['user', 'moderator', 'admin', 'superadmin'])];
        }
        $data = $request->validate($rules);

        abort_if($user->id === $request->user()->id && $data['status'] !== 'active', 422, 'Нельзя заблокировать собственную учетную запись.');
        if ($request->user()->role !== 'superadmin') {
            abort_if(in_array($user->role, ['admin', 'superadmin'], true), 403);
        }

        $changes = ['status' => $data['status'], 'updated_at' => now()];
        if (isset($data['role'])) {
            $changes['role'] = $data['role'];
        }
        DB::table('users')->where('id', $user->id)->update($changes);
        DB::table('user_profiles')->where('user_id', $user->id)->update(['is_verified' => $request->boolean('is_verified'), 'updated_at' => now()]);
        $this->audit($request, 'user.updated', 'user', $user->id, $changes);

        return back()->with('success', 'Пользователь обновлён.');
    }

    public function updateJob(Request $request, int $job): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['moderation', 'published', 'rejected', 'closed'])]]);
        $this->updateScoped($request, 'job_vacancies', $job, [
            'status' => $data['status'],
            'published_at' => $data['status'] === 'published' ? now() : null,
            'updated_at' => now(),
        ]);
        $this->audit($request, 'job.status_changed', 'job', $job, $data);
        return back()->with('success', 'Статус вакансии изменён.');
    }

    public function updatePost(Request $request, int $post): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['published', 'hidden', 'rejected'])]]);
        $this->updateScoped($request, 'posts', $post, ['status' => $data['status'], 'updated_at' => now()]);
        $this->audit($request, 'post.status_changed', 'post', $post, $data);
        return back()->with('success', 'Публикация обновлена.');
    }

    public function updateReview(Request $request, string $type, int $review): RedirectResponse
    {
        abort_unless(in_array($type, ['employer', 'rental'], true), 404);
        $data = $request->validate([
            'status' => ['required', Rule::in(['moderation', 'published', 'rejected', 'hidden'])],
            'moderator_note' => ['nullable', 'string', 'max:3000'],
        ]);
        $table = $type === 'employer' ? 'employer_reviews' : 'rental_reviews';
        $this->updateScoped($request, $table, $review, [
            'status' => $data['status'],
            'moderator_note' => $data['moderator_note'] ?? null,
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
            'updated_at' => now(),
        ]);
        $this->audit($request, 'review.moderated', $type.'_review', $review, $data);
        return back()->with('success', 'Отзыв обработан.');
    }

    public function updateReviewReport(Request $request, int $report): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['new', 'reviewed', 'resolved', 'rejected'])]]);
        $this->updateScoped($request, 'review_reports', $report, ['status' => $data['status'], 'updated_at' => now()]);
        $this->audit($request, 'review_report.updated', 'review_report', $report, $data);
        return back()->with('success', 'Жалоба обработана.');
    }

    public function updateIncident(Request $request, int $incident): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['new', 'in_progress', 'resolved', 'rejected'])]]);
        $this->updateScoped($request, 'incident_reports', $incident, ['status' => $data['status'], 'updated_at' => now()]);
        $this->audit($request, 'incident.updated', 'incident', $incident, $data);
        return back()->with('success', 'Обращение обновлено.');
    }

    public function storeNews(Request $request): RedirectResponse
    {
        $data = $this->validateNews($request);
        $diasporaId = app('currentDiaspora')->id;
        $slug = $this->uniqueSlug('news', $diasporaId, $data['slug'] ?: $data['title_ru']);
        $id = DB::table('news')->insertGetId([
            'diaspora_id' => $diasporaId,
            'author_user_id' => $request->user()->id,
            'slug' => $slug,
            'category' => $data['category'],
            'title' => $this->localized($data['title_ru'], $data['title_native'] ?? null),
            'excerpt' => $this->localized($data['excerpt_ru'] ?? null, $data['excerpt_native'] ?? null),
            'body' => $this->localized($data['body_ru'], $data['body_native'] ?? null),
            'cover_image' => $data['cover_image'] ?? null,
            'is_pinned' => $request->boolean('is_pinned'),
            'is_published' => $request->boolean('is_published'),
            'published_at' => $request->boolean('is_published') ? now() : null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->audit($request, 'news.created', 'news', $id, ['slug' => $slug]);
        return back()->with('success', 'Новость создана.');
    }

    public function updateNews(Request $request, int $news): RedirectResponse
    {
        $data = $this->validateNews($request);
        $item = $this->scopedRecord('news', $news);
        $slug = $this->uniqueSlug('news', $item->diaspora_id, $data['slug'] ?: $data['title_ru'], $news);
        DB::table('news')->where('id', $news)->update([
            'slug' => $slug,
            'category' => $data['category'],
            'title' => $this->localized($data['title_ru'], $data['title_native'] ?? null),
            'excerpt' => $this->localized($data['excerpt_ru'] ?? null, $data['excerpt_native'] ?? null),
            'body' => $this->localized($data['body_ru'], $data['body_native'] ?? null),
            'cover_image' => $data['cover_image'] ?? null,
            'is_pinned' => $request->boolean('is_pinned'),
            'is_published' => $request->boolean('is_published'),
            'published_at' => $request->boolean('is_published') ? ($item->published_at ?: now()) : null,
            'updated_at' => now(),
        ]);
        $this->audit($request, 'news.updated', 'news', $news, ['slug' => $slug]);
        return back()->with('success', 'Новость сохранена.');
    }

    public function deleteNews(Request $request, int $news): RedirectResponse
    {
        $this->scopedRecord('news', $news);
        DB::table('news')->where('id', $news)->delete();
        $this->audit($request, 'news.deleted', 'news', $news);
        return back()->with('success', 'Новость удалена.');
    }

    public function storeLetter(Request $request): RedirectResponse
    {
        $this->requireRole($request, ['admin', 'superadmin']);
        $data = $this->validateDocument($request, true);
        $diasporaId = app('currentDiaspora')->id;
        $slug = $this->uniqueSlug('letter_templates', $diasporaId, $data['slug'] ?: $data['title_ru']);
        $id = DB::table('letter_templates')->insertGetId([
            'diaspora_id' => $diasporaId, 'slug' => $slug, 'category' => $data['category'],
            'title' => $this->localized($data['title_ru'], $data['title_native'] ?? null),
            'description' => $this->localized($data['description_ru'] ?? null, $data['description_native'] ?? null),
            'body_template' => $this->localized($data['body_ru'], $data['body_native'] ?? null),
            'fields' => $data['fields_json'], 'is_active' => $request->boolean('is_active'),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->audit($request, 'letter.created', 'letter_template', $id);
        return back()->with('success', 'Шаблон письма создан.');
    }

    public function updateLetter(Request $request, int $letter): RedirectResponse
    {
        $this->requireRole($request, ['admin', 'superadmin']);
        $data = $this->validateDocument($request, true);
        $item = $this->scopedRecord('letter_templates', $letter);
        DB::table('letter_templates')->where('id', $letter)->update([
            'slug' => $this->uniqueSlug('letter_templates', $item->diaspora_id, $data['slug'] ?: $data['title_ru'], $letter),
            'category' => $data['category'],
            'title' => $this->localized($data['title_ru'], $data['title_native'] ?? null),
            'description' => $this->localized($data['description_ru'] ?? null, $data['description_native'] ?? null),
            'body_template' => $this->localized($data['body_ru'], $data['body_native'] ?? null),
            'fields' => $data['fields_json'], 'is_active' => $request->boolean('is_active'), 'updated_at' => now(),
        ]);
        $this->audit($request, 'letter.updated', 'letter_template', $letter);
        return back()->with('success', 'Шаблон письма сохранён.');
    }

    public function deleteLetter(Request $request, int $letter): RedirectResponse
    {
        $this->requireRole($request, ['admin', 'superadmin']);
        $this->scopedRecord('letter_templates', $letter);
        DB::table('letter_templates')->where('id', $letter)->delete();
        $this->audit($request, 'letter.deleted', 'letter_template', $letter);
        return back()->with('success', 'Шаблон удалён.');
    }

    public function storeSafety(Request $request): RedirectResponse
    {
        $this->requireRole($request, ['admin', 'superadmin']);
        $data = $this->validateDocument($request, false);
        $diasporaId = app('currentDiaspora')->id;
        $slug = $this->uniqueSlug('safety_articles', $diasporaId, $data['slug'] ?: $data['title_ru']);
        $id = DB::table('safety_articles')->insertGetId([
            'diaspora_id' => $diasporaId, 'slug' => $slug, 'category' => $data['category'],
            'title' => $this->localized($data['title_ru'], $data['title_native'] ?? null),
            'summary' => $this->localized($data['description_ru'] ?? null, $data['description_native'] ?? null),
            'body' => $this->localized($data['body_ru'], $data['body_native'] ?? null),
            'emergency' => $request->boolean('emergency'), 'is_published' => $request->boolean('is_published'),
            'published_at' => $request->boolean('is_published') ? now() : null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->audit($request, 'safety.created', 'safety_article', $id);
        return back()->with('success', 'Материал создан.');
    }

    public function updateSafety(Request $request, int $article): RedirectResponse
    {
        $this->requireRole($request, ['admin', 'superadmin']);
        $data = $this->validateDocument($request, false);
        $item = $this->scopedRecord('safety_articles', $article);
        DB::table('safety_articles')->where('id', $article)->update([
            'slug' => $this->uniqueSlug('safety_articles', $item->diaspora_id, $data['slug'] ?: $data['title_ru'], $article),
            'category' => $data['category'],
            'title' => $this->localized($data['title_ru'], $data['title_native'] ?? null),
            'summary' => $this->localized($data['description_ru'] ?? null, $data['description_native'] ?? null),
            'body' => $this->localized($data['body_ru'], $data['body_native'] ?? null),
            'emergency' => $request->boolean('emergency'), 'is_published' => $request->boolean('is_published'),
            'published_at' => $request->boolean('is_published') ? ($item->published_at ?: now()) : null, 'updated_at' => now(),
        ]);
        $this->audit($request, 'safety.updated', 'safety_article', $article);
        return back()->with('success', 'Материал сохранён.');
    }

    public function deleteSafety(Request $request, int $article): RedirectResponse
    {
        $this->requireRole($request, ['admin', 'superadmin']);
        $this->scopedRecord('safety_articles', $article);
        DB::table('safety_articles')->where('id', $article)->delete();
        $this->audit($request, 'safety.deleted', 'safety_article', $article);
        return back()->with('success', 'Материал удалён.');
    }

    public function updateEmployer(Request $request, int $employer): RedirectResponse
    {
        $this->requireRole($request, ['admin', 'superadmin']);
        $data = $request->validate(['verification_status' => ['required', Rule::in(['unverified', 'verified', 'rejected'])]]);
        $this->updateScoped($request, 'employers', $employer, [
            'verification_status' => $data['verification_status'],
            'verified_at' => $data['verification_status'] === 'verified' ? now() : null,
            'updated_at' => now(),
        ]);
        $this->audit($request, 'employer.verification_changed', 'employer', $employer, $data);
        return back()->with('success', 'Работодатель обновлён.');
    }

    public function updateLandlord(Request $request, int $landlord): RedirectResponse
    {
        $this->requireRole($request, ['admin', 'superadmin']);
        $data = $request->validate(['verification_status' => ['required', Rule::in(['unverified', 'verified', 'rejected'])]]);
        $this->updateScoped($request, 'landlords', $landlord, [
            'verification_status' => $data['verification_status'],
            'verified_at' => $data['verification_status'] === 'verified' ? now() : null,
            'updated_at' => now(),
        ]);
        $this->audit($request, 'landlord.verification_changed', 'landlord', $landlord, $data);
        return back()->with('success', 'Арендодатель обновлён.');
    }

    public function storeDiaspora(Request $request): RedirectResponse
    {
        $this->requireRole($request, ['superadmin']);
        $data = $request->validate([
            'code' => ['required', 'alpha_dash', 'max:30', 'unique:diasporas,code'],
            'name' => ['required', 'string', 'max:190'], 'native_name' => ['required', 'string', 'max:190'],
            'default_locale' => ['required', 'string', 'max:10'], 'supported_locales' => ['required', 'string', 'max:190'],
            'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'], 'secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);
        $id = DB::table('diasporas')->insertGetId([
            'code' => $data['code'], 'name' => $data['name'], 'native_name' => $data['native_name'],
            'default_locale' => $data['default_locale'], 'supported_locales' => json_encode($this->localeList($data['supported_locales']), JSON_UNESCAPED_UNICODE),
            'theme' => json_encode(['primary' => $data['primary_color'] ?: '#167B5A', 'secondary' => $data['secondary_color'] ?: '#2C5DAA']),
            'is_active' => $request->boolean('is_active'), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->audit($request, 'diaspora.created', 'diaspora', $id);
        return back()->with('success', 'Диаспора создана.');
    }

    public function updateDiaspora(Request $request, int $diaspora): RedirectResponse
    {
        $this->requireRole($request, ['superadmin']);
        $data = $request->validate([
            'code' => ['required', 'alpha_dash', 'max:30', Rule::unique('diasporas', 'code')->ignore($diaspora)],
            'name' => ['required', 'string', 'max:190'], 'native_name' => ['required', 'string', 'max:190'],
            'default_locale' => ['required', 'string', 'max:10'], 'supported_locales' => ['required', 'string', 'max:190'],
            'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'], 'secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);
        abort_unless(DB::table('diasporas')->where('id', $diaspora)->exists(), 404);
        DB::table('diasporas')->where('id', $diaspora)->update([
            'code' => $data['code'], 'name' => $data['name'], 'native_name' => $data['native_name'],
            'default_locale' => $data['default_locale'], 'supported_locales' => json_encode($this->localeList($data['supported_locales']), JSON_UNESCAPED_UNICODE),
            'theme' => json_encode(['primary' => $data['primary_color'] ?: '#167B5A', 'secondary' => $data['secondary_color'] ?: '#2C5DAA']),
            'is_active' => $request->boolean('is_active'), 'updated_at' => now(),
        ]);
        $this->audit($request, 'diaspora.updated', 'diaspora', $diaspora);
        return back()->with('success', 'Диаспора сохранена.');
    }

    public function storeDomain(Request $request): RedirectResponse
    {
        $this->requireRole($request, ['superadmin']);
        $data = $request->validate([
            'diaspora_id' => ['required', 'exists:diasporas,id'],
            'domain' => ['required', 'string', 'max:190', 'unique:diaspora_domains,domain'],
        ]);
        $id = DB::table('diaspora_domains')->insertGetId([
            'diaspora_id' => $data['diaspora_id'], 'domain' => strtolower(trim($data['domain'])),
            'is_primary' => $request->boolean('is_primary'), 'is_active' => $request->boolean('is_active'),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->audit($request, 'domain.created', 'diaspora_domain', $id, $data);
        return back()->with('success', 'Домен добавлен.');
    }

    public function deleteDomain(Request $request, int $domain): RedirectResponse
    {
        $this->requireRole($request, ['superadmin']);
        abort_unless(DB::table('diaspora_domains')->where('id', $domain)->exists(), 404);
        DB::table('diaspora_domains')->where('id', $domain)->delete();
        $this->audit($request, 'domain.deleted', 'diaspora_domain', $domain);
        return back()->with('success', 'Домен удалён.');
    }

    private function allowedSections(string $role): array
    {
        $sections = [
            'dashboard' => 'Обзор', 'jobs' => 'Вакансии', 'posts' => 'Публикации', 'reviews' => 'Отзывы',
            'review_reports' => 'Жалобы на отзывы', 'incidents' => 'Обращения', 'news' => 'Новости',
        ];
        if (in_array($role, ['admin', 'superadmin'], true)) {
            $sections = array_merge($sections, [
                'users' => 'Пользователи', 'employers' => 'Работодатели', 'rentals' => 'Арендодатели',
                'letters' => 'Шаблоны писем', 'safety' => 'Безопасность', 'audit' => 'Журнал действий',
            ]);
        }
        if ($role === 'superadmin') {
            $sections['diasporas'] = 'Диаспоры и домены';
        }
        return $sections;
    }

    private function requireRole(Request $request, array $roles): void
    {
        abort_unless(in_array($request->user()->role, $roles, true), 403);
    }

    private function assertUserScope(Request $request, User $user): void
    {
        if ($request->user()->role !== 'superadmin') {
            abort_unless($user->diaspora_id === app('currentDiaspora')->id, 404);
        }
    }

    private function scopedRecord(string $table, int $id): object
    {
        $record = DB::table($table)->where('id', $id)->where('diaspora_id', app('currentDiaspora')->id)->first();
        abort_unless($record, 404);
        return $record;
    }

    private function updateScoped(Request $request, string $table, int $id, array $data): void
    {
        $updated = DB::table($table)->where('id', $id)->where('diaspora_id', app('currentDiaspora')->id)->update($data);
        abort_unless($updated || DB::table($table)->where('id', $id)->where('diaspora_id', app('currentDiaspora')->id)->exists(), 404);
    }

    private function validateNews(Request $request): array
    {
        return $request->validate([
            'slug' => ['nullable', 'string', 'max:160'], 'category' => ['required', 'string', 'max:60'],
            'title_ru' => ['required', 'string', 'max:250'], 'title_native' => ['nullable', 'string', 'max:250'],
            'excerpt_ru' => ['nullable', 'string', 'max:1000'], 'excerpt_native' => ['nullable', 'string', 'max:1000'],
            'body_ru' => ['required', 'string', 'max:50000'], 'body_native' => ['nullable', 'string', 'max:50000'],
            'cover_image' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function validateDocument(Request $request, bool $withFields): array
    {
        $rules = [
            'slug' => ['nullable', 'string', 'max:160'], 'category' => ['required', 'string', 'max:60'],
            'title_ru' => ['required', 'string', 'max:250'], 'title_native' => ['nullable', 'string', 'max:250'],
            'description_ru' => ['nullable', 'string', 'max:3000'], 'description_native' => ['nullable', 'string', 'max:3000'],
            'body_ru' => ['required', 'string', 'max:50000'], 'body_native' => ['nullable', 'string', 'max:50000'],
        ];
        if ($withFields) {
            $rules['fields_json'] = ['required', 'json', 'max:30000'];
        }
        return $request->validate($rules);
    }

    private function localized(?string $russian, ?string $native): string
    {
        $diaspora = app('currentDiaspora');
        $locales = is_string($diaspora->supported_locales) ? json_decode($diaspora->supported_locales, true) : (array) $diaspora->supported_locales;
        $nativeLocale = collect($locales)->first(fn ($locale) => $locale !== 'ru') ?: $diaspora->default_locale;
        $values = ['ru' => $russian ?: ''];
        if ($nativeLocale !== 'ru') {
            $values[$nativeLocale] = $native ?: $russian ?: '';
        }
        return json_encode($values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function uniqueSlug(string $table, int $diasporaId, string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'item-'.now()->format('YmdHis');
        $slug = $base;
        $number = 2;
        while (DB::table($table)->where('diaspora_id', $diasporaId)->where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$number++;
        }
        return $slug;
    }

    private function localeList(string $value): array
    {
        $locales = array_values(array_unique(array_filter(array_map('trim', explode(',', strtolower($value))))));
        return in_array('ru', $locales, true) ? $locales : array_merge(['ru'], $locales);
    }

    private function audit(Request $request, string $action, ?string $targetType = null, ?int $targetId = null, array $metadata = []): void
    {
        DB::table('admin_audit_logs')->insert([
            'diaspora_id' => app('currentDiaspora')->id,
            'actor_user_id' => $request->user()->id,
            'action' => $action, 'target_type' => $targetType, 'target_id' => $targetId,
            'metadata' => $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'ip_address' => $request->ip(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
