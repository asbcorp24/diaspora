<?php

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
            ->where('type', 'employer|rental')
            ->where('review', '[0-9]+')
            ->middleware('throttle:10,10')
            ->name('reviews.report');
        Route::get('/reviews-moderation', [ReviewController::class, 'moderation'])->name('reviews.moderation');
        Route::post('/reviews-moderation/{type}/{review}', [ReviewController::class, 'moderate'])
            ->where('type', 'employer|rental')
            ->where('review', '[0-9]+')
            ->name('reviews.moderate');
    });

    Route::get('/jobs', [PlatformController::class, 'jobs'])->name('jobs');
    Route::get('/letters', [PlatformController::class, 'letters'])->name('letters');
    Route::post('/letters/{slug}/preview', [PlatformController::class, 'letterPreview'])->middleware('throttle:30,1')->name('letters.preview');
    Route::get('/safety', [PlatformController::class, 'safety'])->name('safety');
});
