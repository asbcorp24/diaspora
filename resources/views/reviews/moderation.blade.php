<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Модерация отзывов</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @php
        $locale = app()->getLocale();
        $primary = data_get($currentDiaspora->theme, 'primary', '#167B5A');
    @endphp
    <style>:root{--brand:{{ $primary }}}body{background:#f5f7fb}.card{border:0;box-shadow:0 8px 28px rgba(20,35,65,.07)}.review-text{white-space:pre-wrap}</style>
</head>
<body>
<nav class="navbar bg-white border-bottom"><div class="container"><a class="navbar-brand fw-bold" href="{{ route('reviews') }}">← {{ $locale==='uz'?'Sharhlarga qaytish':'Вернуться к отзывам' }}</a><span class="badge text-bg-danger">{{ $locale==='uz'?'Moderatsiya':'Модерация' }}</span></div></nav>
<div class="container py-5">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <h1 class="h2 mb-4">{{ $locale==='uz'?'Sharhlarni moderatsiya qilish':'Модерация отзывов' }}</h1>

    <h2 class="h4 mt-4">{{ $locale==='uz'?'Ish beruvchilar':'Работодатели' }} <span class="badge text-bg-secondary">{{ $employerReviews->count() }}</span></h2>
    @forelse($employerReviews as $review)
        <article class="card mb-3"><div class="card-body p-4">
            <div class="d-flex justify-content-between flex-wrap gap-2"><div><strong>{{ $review->subject_name }}</strong><div class="small text-muted">{{ $review->city }} · {{ $review->reviewer_name }} · {{ \Carbon\Carbon::parse($review->created_at)->format('d.m.Y H:i') }}</div></div><div class="fs-5 text-warning">{{ str_repeat('★',(int)$review->rating) }}{{ str_repeat('☆',5-(int)$review->rating) }}</div></div>
            @if($review->pros)<p class="mb-1 mt-3"><strong class="text-success">Плюсы:</strong> {{ $review->pros }}</p>@endif
            @if($review->cons)<p class="mb-1"><strong class="text-danger">Минусы:</strong> {{ $review->cons }}</p>@endif
            <p class="review-text mt-3">{{ $review->comment }}</p>
            <form method="post" action="{{ route('reviews.moderate',['type'=>'employer','review'=>$review->id]) }}" class="border-top pt-3">@csrf<div class="row g-2"><div class="col-md-7"><input class="form-control" name="moderator_note" placeholder="Комментарий модератора"></div><div class="col-md-2"><button class="btn btn-success w-100" name="status" value="published">Опубликовать</button></div><div class="col-md-3"><button class="btn btn-outline-danger w-100" name="status" value="rejected">Отклонить</button></div></div></form>
        </div></article>
    @empty<div class="alert alert-light border">Нет отзывов работодателей на модерации.</div>@endforelse

    <h2 class="h4 mt-5">{{ $locale==='uz'?'Uy egalari va uy-joy':'Арендодатели и жильё' }} <span class="badge text-bg-secondary">{{ $rentalReviews->count() }}</span></h2>
    @forelse($rentalReviews as $review)
        <article class="card mb-3"><div class="card-body p-4">
            <div class="d-flex justify-content-between flex-wrap gap-2"><div><strong>{{ $review->subject_name }}</strong><div class="small text-muted">{{ $review->city }} @if($review->public_location) · {{ $review->public_location }} @endif · {{ $review->reviewer_name }} · {{ \Carbon\Carbon::parse($review->created_at)->format('d.m.Y H:i') }}</div></div><div class="small text-end">Арендодатель: <span class="text-warning">{{ str_repeat('★',(int)$review->landlord_rating) }}</span><br>Жильё: <span class="text-warning">{{ str_repeat('★',(int)$review->housing_rating) }}</span></div></div>
            @if($review->pros)<p class="mb-1 mt-3"><strong class="text-success">Плюсы:</strong> {{ $review->pros }}</p>@endif
            @if($review->cons)<p class="mb-1"><strong class="text-danger">Минусы:</strong> {{ $review->cons }}</p>@endif
            <p class="review-text mt-3">{{ $review->comment }}</p>
            <form method="post" action="{{ route('reviews.moderate',['type'=>'rental','review'=>$review->id]) }}" class="border-top pt-3">@csrf<div class="row g-2"><div class="col-md-7"><input class="form-control" name="moderator_note" placeholder="Комментарий модератора"></div><div class="col-md-2"><button class="btn btn-success w-100" name="status" value="published">Опубликовать</button></div><div class="col-md-3"><button class="btn btn-outline-danger w-100" name="status" value="rejected">Отклонить</button></div></div></form>
        </div></article>
    @empty<div class="alert alert-light border">Нет отзывов об аренде на модерации.</div>@endforelse
</div>
</body>
</html>
