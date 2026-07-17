<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    @php $locale=app()->getLocale();$primary=data_get($currentDiaspora->theme,'primary','#167B5A');$tr=function($value)use($locale){$data=is_string($value)?json_decode($value,true):(array)$value;return $data[$locale]??$data['ru']??reset($data)??'';}; @endphp
    <title>{{ $tr($newsItem->title) }} — {{ $currentDiaspora->native_name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>:root{--brand:{{ $primary }}}body{background:#f5f7fb}.navbar-brand{color:var(--brand)!important}.card{border:0;box-shadow:0 8px 28px rgba(20,35,65,.07)}.article-cover{width:100%;max-height:520px;object-fit:cover}.article-body{white-space:pre-wrap;line-height:1.75;font-size:1.08rem}</style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom"><div class="container"><a class="navbar-brand fw-bold" href="{{ route('home') }}">{{ $currentDiaspora->native_name }}</a><div class="ms-auto d-flex gap-2 align-items-center"><a class="btn btn-sm btn-outline-secondary" href="{{ route('news') }}">{{ $locale==='uz'?'Yangiliklar':'Все новости' }}</a>@auth @if(in_array(auth()->user()->role,['moderator','admin','superadmin'],true))<a class="btn btn-sm btn-outline-dark" href="{{ route('admin.index',['section'=>'news']) }}">Админка</a>@endif @endauth</div></div></nav>
<main class="container py-5" style="max-width:980px">
    <article class="card overflow-hidden">@if($newsItem->cover_image)<img class="article-cover" src="{{ $newsItem->cover_image }}" alt="">@endif<div class="card-body p-4 p-lg-5"><div class="small text-muted mb-3">{{ $newsItem->category }} · {{ \Carbon\Carbon::parse($newsItem->published_at)->format('d.m.Y H:i') }} @if($newsItem->author_name)· {{ $newsItem->author_name }}@endif</div><h1 class="display-5 fw-bold mb-4">{{ $tr($newsItem->title) }}</h1>@if($tr($newsItem->excerpt))<p class="lead text-muted">{{ $tr($newsItem->excerpt) }}</p>@endif<div class="article-body">{{ $tr($newsItem->body) }}</div></div></article>
    @if($relatedNews->isNotEmpty())<section class="mt-5"><h2 class="h4 mb-3">{{ $locale==='uz'?'Shu mavzuda':'По этой теме' }}</h2><div class="row g-3">@foreach($relatedNews as $item)<div class="col-md-6"><a class="card h-100 text-decoration-none text-dark" href="{{ route('news.show',$item->slug) }}"><div class="card-body"><div class="small text-muted">{{ \Carbon\Carbon::parse($item->published_at)->format('d.m.Y') }}</div><strong>{{ $tr($item->title) }}</strong></div></a></div>@endforeach</div></section>@endif
</main>
<footer class="bg-white border-top py-4"><div class="container small text-muted">{{ $currentDiaspora->name }} · {{ now()->year }}</div></footer>
</body></html>
