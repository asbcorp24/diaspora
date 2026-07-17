<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diasporas', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('native_name');
            $table->string('default_locale', 10)->default('ru');
            $table->json('supported_locales');
            $table->json('theme')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('diaspora_domains', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('diaspora_id')->constrained()->cascadeOnDelete();
            $table->string('domain', 190)->unique();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('diaspora_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->string('phone', 30)->nullable()->unique()->after('email');
            $table->string('role', 30)->default('user')->after('password');
            $table->string('status', 30)->default('active')->after('role');
            $table->string('preferred_locale', 10)->default('ru')->after('status');
            $table->timestamp('last_seen_at')->nullable()->after('preferred_locale');
            $table->index(['diaspora_id', 'status']);
        });

        Schema::create('user_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('birth_date');
            $table->string('gender', 20);
            $table->string('city', 120)->nullable();
            $table->string('hometown', 120)->nullable();
            $table->string('relationship_goal', 30)->default('communication');
            $table->string('employment_status', 30)->nullable();
            $table->string('profession', 190)->nullable();
            $table->text('bio')->nullable();
            $table->json('languages')->nullable();
            $table->string('avatar_path')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->string('visibility', 20)->default('public');
            $table->timestamps();
            $table->index(['city', 'relationship_goal', 'visibility']);
        });

        Schema::create('user_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blocked_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'blocked_user_id']);
        });

        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('diaspora_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30)->default('general');
            $table->text('body');
            $table->json('media')->nullable();
            $table->string('status', 20)->default('published');
            $table->boolean('comments_enabled')->default(true);
            $table->timestamps();
            $table->index(['diaspora_id', 'status', 'created_at']);
        });

        Schema::create('conversations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('diaspora_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('direct');
            $table->string('direct_key', 60)->nullable();
            $table->string('title')->nullable();
            $table->timestamps();
            $table->unique(['diaspora_id', 'direct_key']);
        });

        Schema::create('conversation_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('last_read_at')->nullable();
            $table->boolean('is_muted')->default(false);
            $table->timestamps();
            $table->unique(['conversation_id', 'user_id']);
        });

        Schema::create('messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
            $table->index(['conversation_id', 'created_at']);
        });

        Schema::create('employers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('diaspora_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('tax_id', 30)->nullable();
            $table->text('description')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('address')->nullable();
            $table->string('verification_status', 30)->default('unverified');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('job_vacancies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('diaspora_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employer_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('city', 120);
            $table->string('address')->nullable();
            $table->decimal('salary_from', 12, 2)->nullable();
            $table->decimal('salary_to', 12, 2)->nullable();
            $table->string('currency', 3)->default('RUB');
            $table->string('employment_type', 30)->default('full_time');
            $table->boolean('housing_provided')->default(false);
            $table->boolean('official_employment')->default(true);
            $table->string('contact_phone', 30)->nullable();
            $table->string('status', 30)->default('moderation');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['diaspora_id', 'status', 'city']);
        });

        Schema::create('letter_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('diaspora_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 150);
            $table->string('category', 60);
            $table->json('title');
            $table->json('description')->nullable();
            $table->json('body_template');
            $table->json('fields');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['diaspora_id', 'slug']);
        });

        Schema::create('safety_articles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('diaspora_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 150);
            $table->string('category', 60);
            $table->json('title');
            $table->json('summary')->nullable();
            $table->json('body');
            $table->boolean('emergency')->default(false);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['diaspora_id', 'slug']);
        });

        Schema::create('incident_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('diaspora_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 60);
            $table->string('city', 120)->nullable();
            $table->text('description');
            $table->json('evidence')->nullable();
            $table->boolean('allow_contact')->default(false);
            $table->string('contact')->nullable();
            $table->string('status', 30)->default('new');
            $table->timestamps();
            $table->index(['diaspora_id', 'status', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_reports');
        Schema::dropIfExists('safety_articles');
        Schema::dropIfExists('letter_templates');
        Schema::dropIfExists('job_vacancies');
        Schema::dropIfExists('employers');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_members');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('user_blocks');
        Schema::dropIfExists('user_profiles');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['diaspora_id']);
            $table->dropIndex(['diaspora_id', 'status']);
            $table->dropColumn(['diaspora_id', 'phone', 'role', 'status', 'preferred_locale', 'last_seen_at']);
        });

        Schema::dropIfExists('diaspora_domains');
        Schema::dropIfExists('diasporas');
    }
};
