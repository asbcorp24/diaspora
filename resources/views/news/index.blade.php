<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ app()->getLocale()==='uz' ? 'Yangiliklar' : 'Новости' }} — {{ $currentDiaspora->native_name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @php
        $locale=app()->getLocale();
        $primary=data_get($currentDiaspora->theme,'primary','#167B5A');
        $tr=function($value)use($locale){$data=is_string($value)?json_decode($value,true):(array)$value;return $data[$locale]??$data['ru']??reset($data)??'';};
    @endphp
    <style>:root{--brand:{{ $primary }}}body{background:#f5f7fb}.navbar-brand{color:var(--brand)!important}.card{border:0;box-shadow:0 8px 28px rgba(20,35,65,.07)}.cover{width:100%;height:210px;object-fit:cover}.btn-brand{background:var(--brand);border-color:var(--brand);color:#fff}</style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top"><div class="container"><a class="navbar-brand fw-bold" href="{{ route('home') }}">{{ $currentDiaspora->native_name }}</a><button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button><div class="collapse navbar-collapse" id="nav"><ul class="navbar-nav me-auto"><li class="nav-item"><a class="nav-link" href="{{ route('community') }}">{{ $locale==='uz'?'Muloqot':'Общение' }}</a></li><li class="nav-item"><a class="nav-link" href="{{ route('jobs') }}">{{ $locale==='uz'?'Ish':'Работа' }}</a></li><li class="nav-item"><a class="nav-link active" href="{{ route('news') }}">{{ $locale==='uz'?'Yangiliklar':'Новости' }}</a></li><li class="nav-item"><a class="nav-link" href="{{ route('reviews') }}">{{ $locale==='uz'?'Sharhlar':'Отзывы' }}</a></li><li class="nav-item"><a class="nav-link" href="{{ route('letters') }}">{{ $locale==='uz'?'Xatlar':'Письма' }}</a></li><li class="nav-item"><a class="nav-link" href="{{ route('safety') }}">{{ $locale==='uz'?'Xavfsizlik':'Безопасность' }}</a></li></ul><div class="d-flex gap-2 align-items-center">@foreach($currentDiaspora->supported_locales as $lang)<a class="small text-decoration-none {{ $locale===$lang?'fw-bold':'' }}" href="{{ request()->fullUrlWithQuery(['lang'=>$lang]) }}">{{ strtoupper($lang) }}</a>@endforeach @auth @if(in_array(auth()->user()->role,['moderator','admin','superadmin'],true))<a class="btn btn-sm btn-outline-dark" href="{{ route('admin.index',['section'=>'news']) }}">Админка</a>@endif @else<a class="btn btn-sm btn-outline-secondary" href="{{ route('login') }}">Войти</a>@endauth</div></div></div></nav>
<main class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4"><div><h1 class="display-6 fw-bold">{{ $locale==='uz'?'Yangiliklar':'Новости диаспоры' }}</h1><p class="text-muted mb-0">{{ $locale==='uz'?'Jamiyat yangiliklari, hujjatlar, ish va tadbirlar.':'События сообщества, документы, работа и полезная информация.' }}</p></div>@auth @if(in_array(auth()->user()->role,['moderator','admin','superadmin'],true))<a class="btn btn-brand" href="{{ route('admin.index',['section'=>'news']) }}">Добавить новость</a>@endif @endauth</div>
    @if($categories->isNotEmpty())<div class="d-flex flex-wrap gap-2 mb-4"><a class="btn btn-sm {{ request('category')?'btn-outline-secondary':'btn-dark' }}" href="{{ route('news') }}">Все</a>@foreach($categories as $category)<a class="btn btn-sm {{ request('category')===$category?'btn-dark':'btn-outline-secondary' }}" href="{{ route('news',['category'=>$category]) }}">{{ $category }}</a>@endforeach</div>@endif
    <div class="row g-4">@forelse($newsItems as $item)<div class="col-md-6 col-xl-4"><article class="card h-100">@if($item->cover_image)<img class="cover rounded-top" src="{{ $item->cover_image }}" alt="">@endif<div class="card-body d-flex flex-column"> <div class="small text-muted mb-2">{{ $item->category }} · {{ \Carbon\Carbon::parse($item->published_at)->format('d.m.Y') }} @if($item->is_pinned)· <span class="text-danger">закреплено</span>@endif</div><h2 class="h5">{{ $tr($item->title) }}</h2><p class="text-muted">{{ $tr($item->excerpt) }}</p><a class="stretched-link mt-auto" href="{{ route('news.show',$item->slug) }}">{{ $locale==='uz'?'Batafsil':'Читать' }}</a></div></article></div>@empty<div class="col-12"><div class="alert alert-light border">{{ $locale==='uz'?'Yangiliklar hozircha yo‘q.':'Новостей пока нет.' }}</div></div>@endforelse</div>
    <div class="mt-4">{{ $newsItems->links() }}</div>
</main>
<footer class="bg-white border-top py-4"><div class="container small text-muted">{{ $currentDiaspora->name }} · {{ now()->year }}</div></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
