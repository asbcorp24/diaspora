<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $diasporaId = app('currentDiaspora')->id;
        $type = $request->input('type') === 'rental' ? 'rental' : 'employer';
        $search = trim((string) $request->input('search'));
        $city = trim((string) $request->input('city'));

        $employerReviews = collect();
        $rentalReviews = collect();

        if ($type === 'employer') {
            $employerReviews = DB::table('employer_reviews')
                ->join('employers', 'employers.id', '=', 'employer_reviews.employer_id')
                ->join('users', 'users.id', '=', 'employer_reviews.user_id')
                ->where('employer_reviews.diaspora_id', $diasporaId)
                ->where('employer_reviews.status', 'published')
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($nested) use ($search): void {
                        $nested->where('employers.name', 'like', '%'.$search.'%')
                            ->orWhere('employers.legal_name', 'like', '%'.$search.'%')
                            ->orWhere('employers.tax_id', 'like', '%'.$search.'%')
                            ->orWhere('employer_reviews.comment', 'like', '%'.$search.'%');
                    });
                })
                ->when($city !== '', fn ($query) => $query->where('employers.city', 'like', '%'.$city.'%'))
                ->select(
                    'employer_reviews.*',
                    'employers.name as subject_name',
                    'employers.legal_name',
                    'employers.tax_id',
                    'employers.city',
                    'employers.verification_status',
                    'users.name as reviewer_name'
                )
                ->latest('employer_reviews.created_at')
                ->paginate(15)
                ->withQueryString();
        } else {
            $rentalReviews = DB::table('rental_reviews')
                ->join('rental_properties', 'rental_properties.id', '=', 'rental_reviews.rental_property_id')
                ->join('landlords', 'landlords.id', '=', 'rental_properties.landlord_id')
                ->join('users', 'users.id', '=', 'rental_reviews.user_id')
                ->where('rental_reviews.diaspora_id', $diasporaId)
                ->where('rental_reviews.status', 'published')
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($nested) use ($search): void {
                        $nested->where('landlords.display_name', 'like', '%'.$search.'%')
                            ->orWhere('rental_properties.title', 'like', '%'.$search.'%')
                            ->orWhere('rental_properties.district', 'like', '%'.$search.'%')
                            ->orWhere('rental_properties.public_location', 'like', '%'.$search.'%')
                            ->orWhere('rental_reviews.comment', 'like', '%'.$search.'%');
                    });
                })
                ->when($city !== '', fn ($query) => $query->where('rental_properties.city', 'like', '%'.$city.'%'))
                ->select(
                    'rental_reviews.*',
                    'landlords.display_name as subject_name',
                    'landlords.contact_hint',
                    'landlords.verification_status',
                    'rental_properties.title as property_title',
                    'rental_properties.city',
                    'rental_properties.district',
                    'rental_properties.public_location',
                    'rental_properties.property_type',
                    'users.name as reviewer_name'
                )
                ->latest('rental_reviews.created_at')
                ->paginate(15)
                ->withQueryString();
        }

        return view('reviews.index', compact('type', 'employerReviews', 'rentalReviews'));
    }

    public function storeEmployer(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employer_name' => ['required', 'string', 'max:190'],
            'legal_name' => ['nullable', 'string', 'max:190'],
            'tax_id' => ['nullable', 'string', 'max:30'],
            'city' => ['required', 'string', 'max:120'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'salary_on_time' => ['nullable', 'boolean'],
            'contract_provided' => ['nullable', 'boolean'],
            'conditions_match' => ['nullable', 'boolean'],
            'would_recommend' => ['nullable', 'boolean'],
            'employment_started_at' => ['nullable', 'date', 'before_or_equal:today'],
            'employment_ended_at' => ['nullable', 'date', 'after_or_equal:employment_started_at', 'before_or_equal:today'],
            'pros' => ['nullable', 'string', 'max:3000'],
            'cons' => ['nullable', 'string', 'max:3000'],
            'comment' => ['required', 'string', 'min:30', 'max:10000'],
            'anonymous_public' => ['nullable', 'boolean'],
            'rules_confirmed' => ['accepted'],
        ]);

        $diasporaId = app('currentDiaspora')->id;

        DB::transaction(function () use ($request, $data, $diasporaId): void {
            $employer = null;

            if (!empty($data['tax_id'])) {
                $employer = DB::table('employers')
                    ->where('diaspora_id', $diasporaId)
                    ->where('tax_id', $data['tax_id'])
                    ->first();
            }

            if (!$employer) {
                $employer = DB::table('employers')
                    ->where('diaspora_id', $diasporaId)
                    ->where('name', $data['employer_name'])
                    ->where('city', $data['city'])
                    ->first();
            }

            $employerId = $employer?->id ?? DB::table('employers')->insertGetId([
                'diaspora_id' => $diasporaId,
                'owner_user_id' => null,
                'name' => $data['employer_name'],
                'legal_name' => $data['legal_name'] ?? null,
                'tax_id' => $data['tax_id'] ?? null,
                'city' => $data['city'],
                'verification_status' => 'unverified',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $alreadyExists = DB::table('employer_reviews')
                ->where('employer_id', $employerId)
                ->where('user_id', $request->user()->id)
                ->exists();

            abort_if($alreadyExists, 422, 'Вы уже оставляли отзыв об этом работодателе.');

            DB::table('employer_reviews')->insert([
                'diaspora_id' => $diasporaId,
                'employer_id' => $employerId,
                'user_id' => $request->user()->id,
                'rating' => $data['rating'],
                'salary_on_time' => $this->nullableBoolean($request, 'salary_on_time'),
                'contract_provided' => $this->nullableBoolean($request, 'contract_provided'),
                'conditions_match' => $this->nullableBoolean($request, 'conditions_match'),
                'would_recommend' => $this->nullableBoolean($request, 'would_recommend'),
                'employment_started_at' => $data['employment_started_at'] ?? null,
                'employment_ended_at' => $data['employment_ended_at'] ?? null,
                'pros' => $data['pros'] ?? null,
                'cons' => $data['cons'] ?? null,
                'comment' => $data['comment'],
                'anonymous_public' => $request->boolean('anonymous_public'),
                'status' => 'moderation',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()->route('reviews', ['type' => 'employer'])
            ->with('success', 'Отзыв о работодателе отправлен на модерацию.');
    }

    public function storeRental(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'landlord_name' => ['required', 'string', 'max:190'],
            'contact_hint' => ['nullable', 'string', 'max:30'],
            'city' => ['required', 'string', 'max:120'],
            'district' => ['nullable', 'string', 'max:120'],
            'public_location' => ['nullable', 'string', 'max:190'],
            'property_title' => ['nullable', 'string', 'max:190'],
            'property_type' => ['required', 'in:apartment,room,house,hostel,bed_place'],
            'landlord_rating' => ['required', 'integer', 'between:1,5'],
            'housing_rating' => ['required', 'integer', 'between:1,5'],
            'listing_accuracy_rating' => ['nullable', 'integer', 'between:1,5'],
            'deposit_result' => ['required', 'in:returned,partially_returned,not_returned,not_applicable'],
            'would_recommend' => ['nullable', 'boolean'],
            'rental_started_at' => ['nullable', 'date', 'before_or_equal:today'],
            'rental_ended_at' => ['nullable', 'date', 'after_or_equal:rental_started_at', 'before_or_equal:today'],
            'pros' => ['nullable', 'string', 'max:3000'],
            'cons' => ['nullable', 'string', 'max:3000'],
            'comment' => ['required', 'string', 'min:30', 'max:10000'],
            'anonymous_public' => ['nullable', 'boolean'],
            'rules_confirmed' => ['accepted'],
        ]);

        $diasporaId = app('currentDiaspora')->id;

        DB::transaction(function () use ($request, $data, $diasporaId): void {
            $landlordQuery = DB::table('landlords')
                ->where('diaspora_id', $diasporaId)
                ->where('display_name', $data['landlord_name'])
                ->where('city', $data['city']);

            if (!empty($data['contact_hint'])) {
                $landlordQuery->where('contact_hint', $data['contact_hint']);
            }

            $landlord = $landlordQuery->first();
            $landlordId = $landlord?->id ?? DB::table('landlords')->insertGetId([
                'diaspora_id' => $diasporaId,
                'created_by_user_id' => $request->user()->id,
                'display_name' => $data['landlord_name'],
                'city' => $data['city'],
                'contact_hint' => $data['contact_hint'] ?? null,
                'verification_status' => 'unverified',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $property = DB::table('rental_properties')
                ->where('diaspora_id', $diasporaId)
                ->where('landlord_id', $landlordId)
                ->where('city', $data['city'])
                ->where('public_location', $data['public_location'] ?? null)
                ->where('property_type', $data['property_type'])
                ->first();

            $propertyId = $property?->id ?? DB::table('rental_properties')->insertGetId([
                'diaspora_id' => $diasporaId,
                'landlord_id' => $landlordId,
                'created_by_user_id' => $request->user()->id,
                'title' => $data['property_title'] ?? null,
                'city' => $data['city'],
                'district' => $data['district'] ?? null,
                'public_location' => $data['public_location'] ?? null,
                'property_type' => $data['property_type'],
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $alreadyExists = DB::table('rental_reviews')
                ->where('rental_property_id', $propertyId)
                ->where('user_id', $request->user()->id)
                ->exists();

            abort_if($alreadyExists, 422, 'Вы уже оставляли отзыв об этом жилье.');

            DB::table('rental_reviews')->insert([
                'diaspora_id' => $diasporaId,
                'rental_property_id' => $propertyId,
                'user_id' => $request->user()->id,
                'landlord_rating' => $data['landlord_rating'],
                'housing_rating' => $data['housing_rating'],
                'listing_accuracy_rating' => $data['listing_accuracy_rating'] ?? null,
                'deposit_result' => $data['deposit_result'],
                'would_recommend' => $this->nullableBoolean($request, 'would_recommend'),
                'rental_started_at' => $data['rental_started_at'] ?? null,
                'rental_ended_at' => $data['rental_ended_at'] ?? null,
                'pros' => $data['pros'] ?? null,
                'cons' => $data['cons'] ?? null,
                'comment' => $data['comment'],
                'anonymous_public' => $request->boolean('anonymous_public'),
                'status' => 'moderation',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()->route('reviews', ['type' => 'rental'])
            ->with('success', 'Отзыв об арендодателе и жилье отправлен на модерацию.');
    }

    public function report(Request $request, string $type, int $review): RedirectResponse
    {
        abort_unless(in_array($type, ['employer', 'rental'], true), 404);

        $data = $request->validate([
            'reason' => ['required', 'in:false_information,personal_data,insults,spam,conflict_of_interest,other'],
            'details' => ['nullable', 'string', 'max:3000'],
        ]);

        $table = $type === 'employer' ? 'employer_reviews' : 'rental_reviews';
        $exists = DB::table($table)
            ->where('id', $review)
            ->where('diaspora_id', app('currentDiaspora')->id)
            ->where('status', 'published')
            ->exists();
        abort_unless($exists, 404);

        DB::table('review_reports')->updateOrInsert([
            'reporter_user_id' => $request->user()->id,
            'review_type' => $type,
            'review_id' => $review,
        ], [
            'diaspora_id' => app('currentDiaspora')->id,
            'reason' => $data['reason'],
            'details' => $data['details'] ?? null,
            'status' => 'new',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Жалоба на отзыв передана модератору.');
    }

    public function moderation(Request $request): View
    {
        $this->assertModerator($request);
        $diasporaId = app('currentDiaspora')->id;

        $employerReviews = DB::table('employer_reviews')
            ->join('employers', 'employers.id', '=', 'employer_reviews.employer_id')
            ->join('users', 'users.id', '=', 'employer_reviews.user_id')
            ->where('employer_reviews.diaspora_id', $diasporaId)
            ->where('employer_reviews.status', 'moderation')
            ->select('employer_reviews.*', 'employers.name as subject_name', 'employers.city', 'users.name as reviewer_name')
            ->oldest('employer_reviews.created_at')
            ->get();

        $rentalReviews = DB::table('rental_reviews')
            ->join('rental_properties', 'rental_properties.id', '=', 'rental_reviews.rental_property_id')
            ->join('landlords', 'landlords.id', '=', 'rental_properties.landlord_id')
            ->join('users', 'users.id', '=', 'rental_reviews.user_id')
            ->where('rental_reviews.diaspora_id', $diasporaId)
            ->where('rental_reviews.status', 'moderation')
            ->select('rental_reviews.*', 'landlords.display_name as subject_name', 'rental_properties.city', 'rental_properties.public_location', 'users.name as reviewer_name')
            ->oldest('rental_reviews.created_at')
            ->get();

        return view('reviews.moderation', compact('employerReviews', 'rentalReviews'));
    }

    public function moderate(Request $request, string $type, int $review): RedirectResponse
    {
        $this->assertModerator($request);
        abort_unless(in_array($type, ['employer', 'rental'], true), 404);

        $data = $request->validate([
            'status' => ['required', 'in:published,rejected'],
            'moderator_note' => ['nullable', 'string', 'max:3000'],
        ]);

        $table = $type === 'employer' ? 'employer_reviews' : 'rental_reviews';
        $updated = DB::table($table)
            ->where('id', $review)
            ->where('diaspora_id', app('currentDiaspora')->id)
            ->where('status', 'moderation')
            ->update([
                'status' => $data['status'],
                'moderated_by' => $request->user()->id,
                'moderated_at' => now(),
                'moderator_note' => $data['moderator_note'] ?? null,
                'updated_at' => now(),
            ]);

        abort_unless($updated, 404);

        return back()->with('success', $data['status'] === 'published' ? 'Отзыв опубликован.' : 'Отзыв отклонён.');
    }

    private function nullableBoolean(Request $request, string $field): ?bool
    {
        $value = $request->input($field);

        if ($value === null || $value === '') {
            return null;
        }

        return $request->boolean($field);
    }

    private function assertModerator(Request $request): void
    {
        abort_unless(in_array($request->user()->role, ['moderator', 'admin', 'superadmin'], true), 403);
    }
}
