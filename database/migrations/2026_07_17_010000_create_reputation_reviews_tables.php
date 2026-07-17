<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landlords', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('diaspora_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('display_name');
            $table->string('city', 120);
            $table->string('contact_hint', 30)->nullable();
            $table->string('verification_status', 30)->default('unverified');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['diaspora_id', 'city', 'display_name']);
        });

        Schema::create('rental_properties', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('diaspora_id')->constrained()->cascadeOnDelete();
            $table->foreignId('landlord_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('city', 120);
            $table->string('district', 120)->nullable();
            $table->string('public_location', 190)->nullable();
            $table->string('property_type', 30)->default('apartment');
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->index(['diaspora_id', 'city', 'status']);
        });

        Schema::create('employer_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('diaspora_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->boolean('salary_on_time')->nullable();
            $table->boolean('contract_provided')->nullable();
            $table->boolean('conditions_match')->nullable();
            $table->boolean('would_recommend')->nullable();
            $table->date('employment_started_at')->nullable();
            $table->date('employment_ended_at')->nullable();
            $table->text('pros')->nullable();
            $table->text('cons')->nullable();
            $table->text('comment');
            $table->boolean('anonymous_public')->default(false);
            $table->string('status', 30)->default('moderation');
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->text('moderator_note')->nullable();
            $table->timestamps();
            $table->unique(['employer_id', 'user_id']);
            $table->index(['diaspora_id', 'status', 'rating']);
        });

        Schema::create('rental_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('diaspora_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rental_property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('landlord_rating');
            $table->unsignedTinyInteger('housing_rating');
            $table->unsignedTinyInteger('listing_accuracy_rating')->nullable();
            $table->string('deposit_result', 30)->default('not_applicable');
            $table->boolean('would_recommend')->nullable();
            $table->date('rental_started_at')->nullable();
            $table->date('rental_ended_at')->nullable();
            $table->text('pros')->nullable();
            $table->text('cons')->nullable();
            $table->text('comment');
            $table->boolean('anonymous_public')->default(false);
            $table->string('status', 30)->default('moderation');
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->text('moderator_note')->nullable();
            $table->timestamps();
            $table->unique(['rental_property_id', 'user_id']);
            $table->index(['diaspora_id', 'status', 'landlord_rating']);
        });

        Schema::create('review_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('diaspora_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reporter_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('review_type', 20);
            $table->unsignedBigInteger('review_id');
            $table->string('reason', 40);
            $table->text('details')->nullable();
            $table->string('status', 30)->default('new');
            $table->timestamps();
            $table->unique(['reporter_user_id', 'review_type', 'review_id']);
            $table->index(['diaspora_id', 'status', 'review_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_reports');
        Schema::dropIfExists('rental_reviews');
        Schema::dropIfExists('employer_reviews');
        Schema::dropIfExists('rental_properties');
        Schema::dropIfExists('landlords');
    }
};
