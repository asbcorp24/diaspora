<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\LegalHelpController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\ReviewController;
use App\Http\Middleware\ResolveDiaspora;
use Illuminate\Support\Facades\Route;

Route::middleware(ResolveDiaspora::class)->group(function (): void {
    Route::get('/', [PlatformController::class, 'home'])->name('home');

    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [PlatformController::class, 'loginForm'])->name('login');
        Route::post('/login', [PlatformController::class, 'login'])->middleware('throttle:10,1');
        Route::get('/register', [PlatformController::class, 'registerForm'])->name('register');
        Route::post('/register', [PlatformController::class, 'register'])->middleware('throttle:5,1');
    });

    Route::post('/logout', [PlatformController::class, 'logout'])->middleware('auth')->name('logout');

    Route::get('/community', [PlatformController::class, 'community'])->name('community');
    Route::post('/community/posts', [PlatformController::class, 'storePost'])->middleware(['auth', 'throttle:30,1'])->name('posts.store');

    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews');
    Route::get('/news', [NewsController::class, 'index'])->name('news');
    Route::get('/news/{slug}', [NewsController::class, 'show'])->name('news.show');

    Route::middleware('auth')->group(function (): void {
        Route::get('/messages', [PlatformController::class, 'messages'])->name('messages');
        Route::post('/messages/start/{user}', [PlatformController::class, 'startConversation'])->name('messages.start');
        Route::get('/messages/{conversation}', [PlatformController::class, 'conversation'])->where('conversation', '[0-9]+')->name('conversation');
        Route::post('/messages/{conversation}', [PlatformController::class, 'sendMessage'])->where('conversation', '[0-9]+')->middleware('throttle:60,1')->name('messages.send');
        Route::post('/jobs', [PlatformController::class, 'storeJob'])->middleware('throttle:10,1')->name('jobs.store');
        Route::post('/safety/report', [PlatformController::class, 'reportIncident'])->middleware('throttle:5,10')->name('safety.report');

        Route::post('/reviews/employers', [ReviewController::class, 'storeEmployer'])->middleware('throttle:5,10')->name('reviews.employer.store');
        Route::post('/reviews/rentals', [ReviewController::class, 'storeRental'])->middleware('throttle:5,10')->name('reviews.rental.store');
        Route::post('/reviews/{type}/{review}/report', [ReviewController::class, 'report'])
            ->where('type', 'employer|rental')->where('review', '[0-9]+')->middleware('throttle:10,10')->name('reviews.report');
        Route::get('/reviews-moderation', [ReviewController::class, 'moderation'])->name('reviews.moderation');
        Route::post('/reviews-moderation/{type}/{review}', [ReviewController::class, 'moderate'])
            ->where('type', 'employer|rental')->where('review', '[0-9]+')->name('reviews.moderate');
    });

    Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::patch('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::patch('/jobs/{job}', [AdminController::class, 'updateJob'])->whereNumber('job')->name('jobs.update');
        Route::patch('/posts/{post}', [AdminController::class, 'updatePost'])->whereNumber('post')->name('posts.update');
        Route::patch('/reviews/{type}/{review}', [AdminController::class, 'updateReview'])->where('type', 'employer|rental')->whereNumber('review')->name('reviews.update');
        Route::patch('/review-reports/{report}', [AdminController::class, 'updateReviewReport'])->whereNumber('report')->name('review_reports.update');
        Route::patch('/incidents/{incident}', [AdminController::class, 'updateIncident'])->whereNumber('incident')->name('incidents.update');

        Route::post('/news', [AdminController::class, 'storeNews'])->name('news.store');
        Route::patch('/news/{news}', [AdminController::class, 'updateNews'])->whereNumber('news')->name('news.update');
        Route::delete('/news/{news}', [AdminController::class, 'deleteNews'])->whereNumber('news')->name('news.delete');

        Route::patch('/employers/{employer}', [AdminController::class, 'updateEmployer'])->whereNumber('employer')->name('employers.update');
        Route::patch('/landlords/{landlord}', [AdminController::class, 'updateLandlord'])->whereNumber('landlord')->name('landlords.update');

        Route::post('/letters', [AdminController::class, 'storeLetter'])->name('letters.store');
        Route::patch('/letters/{letter}', [AdminController::class, 'updateLetter'])->whereNumber('letter')->name('letters.update');
        Route::delete('/letters/{letter}', [AdminController::class, 'deleteLetter'])->whereNumber('letter')->name('letters.delete');

        Route::post('/safety', [AdminController::class, 'storeSafety'])->name('safety.store');
        Route::patch('/safety/{article}', [AdminController::class, 'updateSafety'])->whereNumber('article')->name('safety.update');
        Route::delete('/safety/{article}', [AdminController::class, 'deleteSafety'])->whereNumber('article')->name('safety.delete');

        Route::post('/diasporas', [AdminController::class, 'storeDiaspora'])->name('diasporas.store');
        Route::patch('/diasporas/{diaspora}', [AdminController::class, 'updateDiaspora'])->whereNumber('diaspora')->name('diasporas.update');
        Route::post('/domains', [AdminController::class, 'storeDomain'])->name('domains.store');
        Route::delete('/domains/{domain}', [AdminController::class, 'deleteDomain'])->whereNumber('domain')->name('domains.delete');
    });

    Route::get('/jobs', [PlatformController::class, 'jobs'])->name('jobs');
    Route::get('/legal-help', [LegalHelpController::class, 'index'])->name('letters');
    Route::post('/legal-help/{slug}/preview', [LegalHelpController::class, 'preview'])->middleware('throttle:30,1')->name('letters.preview');
    Route::get('/letters', fn () => redirect()->route('letters', [], 301));
    Route::post('/letters/{slug}/preview', [LegalHelpController::class, 'preview'])->middleware('throttle:30,1');
    Route::get('/safety', [PlatformController::class, 'safety'])->name('safety');
});
