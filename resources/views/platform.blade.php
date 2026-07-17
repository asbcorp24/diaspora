<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $currentDiaspora->native_name }} — Diaspora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @php
        $locale = app()->getLocale();
        $primary = data_get($currentDiaspora->theme, 'primary', '#167B5A');
        $secondary = data_get($currentDiaspora->theme, 'secondary', '#2C5DAA');
        $t = $locale === 'uz' ? [
            'home'=>'Bosh sahifa',
            'community'=>'Muloqot va tanishuv',
            'jobs'=>'Ish',
            'reviews'=>'Sharhlar',
            'letters'=>'Tayyor xatlar',
            'safety'=>'Xavfsizlik',
            'messages'=>'Xabarlar',
            'login'=>'Kirish',
            'register'=>'Ro‘yxatdan o‘tish',
            'logout'=>'Chiqish',
            'search'=>'Qidirish',
            'send'=>'Yuborish',
        ] : [
            'home'=>'Главная',
            'community'=>'Общение и знакомства',
            'jobs'=>'Работа',
            'reviews'=>'Отзывы',
            'letters'=>'Готовые письма',
            'safety'=>'Безопасность',
            'messages'=>'Сообщения',
            'login'=>'Войти',
            'register'=>'Регистрация',
            'logout'=>'Выйти',
            'search'=>'Найти',
            'send'=>'Отправить',
        ];
        $tr = function ($value) use ($locale) {
            $data = is_string($value) ? json_decode($value, true) : (array) $value;
            return $data[$locale] ?? $data['ru'] ?? reset($data) ?? '';
        };
    @endphp
    <style>
        :root{--brand:{{ $primary }};--brand2:{{ $secondary }}}
        body{background:#f5f7fb}
        .navbar-brand{color:var(--brand)!important}
        .btn-brand{background:var(--brand);border-color:var(--brand);color:white}
        .btn-brand:hover{background:var(--brand2);color:white}
        .hero{background:linear-gradient(135deg,var(--brand),var(--brand2));color:white}
        .card{border:0;box-shadow:0 8px 28px rgba(20,35,65,.07)}
        .own{background:var(--brand);color:white}
        @media print{nav,footer,.no-print,.alert{display:none!important}.card{box-shadow:none}}
    </style>
</head>
<body>
<div class="bg-warning-subtle border-bottom py-2 text-center small">
    {{ $locale==='uz' ? 'Hayotga xavf bo‘lsa, 112 raqamiga qo‘ng‘iroq qiling.' : 'При непосредственной угрозе жизни звоните 112.' }}
</div>
<nav class="navbar navbar-expand-xl bg-white border-bottom sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('home') }}">{{ $currentDiaspora->native_name }}</a>
        <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="{{ route('community') }}">{{ $t['community'] }}</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('jobs') }}">{{ $t['jobs'] }}</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('reviews') }}">{{ $t['reviews'] }}</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('letters') }}">{{ $t['letters'] }}</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('safety') }}">{{ $t['safety'] }}</a></li>
                @auth<li class="nav-item"><a class="nav-link" href="{{ route('messages') }}">{{ $t['messages'] }}</a></li>@endauth
            </ul>
            <div class="d-flex align-items-center gap-2">
                @foreach($currentDiaspora->supported_locales as $lang)
                    <a class="small text-decoration-none {{ $locale===$lang?'fw-bold':'' }}" href="{{ request()->fullUrlWithQuery(['lang'=>$lang]) }}">{{ strtoupper($lang) }}</a>
                @endforeach
                @auth
                    <span class="small">{{ auth()->user()->name }}</span>
                    <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-outline-secondary">{{ $t['logout'] }}</button></form>
                @else
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('login') }}">{{ $t['login'] }}</a>
                    <a class="btn btn-sm btn-brand" href="{{ route('register') }}">{{ $t['register'] }}</a>
                @endauth
            </div>
        </div>
    </div>
</nav>

@if(session('success'))
    <div class="container mt-3"><div class="alert alert-success">{{ session('success') }}</div></div>
@endif
@if($errors->any())
    <div class="container mt-3"><div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
@endif

@if($page==='home')
    <section class="hero py-5">
        <div class="container py-4"><div class="row align-items-center g-4">
            <div class="col-lg-8">
                <h1 class="display-5 fw-bold">{{ $locale==='uz' ? 'O‘z odamlaringiz, ish va yordam bir joyda' : 'Свои люди, работа и помощь в одном месте' }}</h1>
                <p class="lead">{{ $locale==='uz' ? 'Muloqot qiling, tanishing, ish toping va huquqlaringizni himoya qiling.' : 'Общайтесь, знакомьтесь, находите работу и защищайте свои права.' }}</p>
                <a class="btn btn-light btn-lg" href="{{ route('community') }}">{{ $t['community'] }}</a>
                <a class="btn btn-outline-light btn-lg" href="{{ route('jobs') }}">{{ $t['jobs'] }}</a>
                <a class="btn btn-outline-light btn-lg" href="{{ route('reviews') }}">{{ $t['reviews'] }}</a>
            </div>
            <div class="col-lg-4"><div class="bg-white text-dark rounded-4 p-4">
                <h2 class="h5">{{ $t['safety'] }}</h2>
                <p class="small text-muted">Задержание, мошенничество, удержание документов, невыплата зарплаты, насилие.</p>
                <a class="btn btn-danger w-100" href="{{ route('safety') }}">Открыть помощь</a>
            </div></div>
        </div></div>
    </section>
    <div class="container py-5"><div class="row g-4">
        <div class="col-lg-4"><h2 class="h4">{{ $t['jobs'] }}</h2>
            @forelse($jobs as $job)<div class="card mb-2"><div class="card-body"><strong>{{ $job->title }}</strong><div class="small text-muted">{{ $job->city }}</div></div></div>@empty<p class="text-muted">Пока нет вакансий.</p>@endforelse
        </div>
        <div class="col-lg-4"><h2 class="h4">{{ $t['community'] }}</h2>
            @forelse($posts as $post)<div class="card mb-2"><div class="card-body"><strong>{{ $post->user_name }}</strong><p class="mb-0">{{ Str::limit($post->body,150) }}</p></div></div>@empty<p class="text-muted">Пока нет публикаций.</p>@endforelse
        </div>
        <div class="col-lg-4"><h2 class="h4">{{ $t['safety'] }}</h2>
            @foreach($safetyArticles as $article)<div class="card mb-2"><div class="card-body"><strong>{{ $tr($article->title) }}</strong><p class="small text-muted mb-0">{{ $tr($article->summary) }}</p></div></div>@endforeach
        </div>
    </div></div>

@elseif($page==='login')
    <div class="container py-5" style="max-width:560px"><div class="card"><div class="card-body p-4">
        <h1 class="h3">{{ $t['login'] }}</h1>
        <form method="post" action="{{ route('login') }}">@csrf
            <div class="mb-3"><label class="form-label">Email / телефон</label><input class="form-control" name="login" value="{{ old('login') }}" required></div>
            <div class="mb-3"><label class="form-label">Пароль / Parol</label><input class="form-control" type="password" name="password" required></div>
            <button class="btn btn-brand w-100">{{ $t['login'] }}</button>
        </form>
    </div></div></div>

@elseif($page==='register')
    <div class="container py-5" style="max-width:780px"><div class="card"><div class="card-body p-4">
        <h1 class="h3">{{ $t['register'] }}</h1>
        <p class="text-muted">Только 18+. Не публикуйте паспорт, точный адрес и банковские данные.</p>
        <form method="post" action="{{ route('register') }}">@csrf
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Имя / Ism</label><input class="form-control" name="name" required></div>
                <div class="col-md-6"><label class="form-label">Телефон</label><input class="form-control" name="phone" required></div>
                <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email"></div>
                <div class="col-md-6"><label class="form-label">Дата рождения</label><input class="form-control" type="date" name="birth_date" max="{{ now()->subYears(18)->toDateString() }}" required></div>
                <div class="col-md-6"><label class="form-label">Пол</label><select class="form-select" name="gender"><option value="male">Мужчина</option><option value="female">Женщина</option><option value="hidden">Не показывать</option></select></div>
                <div class="col-md-6"><label class="form-label">Город</label><input class="form-control" name="city"></div>
                <div class="col-12"><label class="form-label">Цель</label><select class="form-select" name="relationship_goal"><option value="communication">Общение</option><option value="friendship">Дружба</option><option value="family">Семья</option><option value="work">Работа</option><option value="networking">Деловые контакты</option></select></div>
                <div class="col-md-6"><input class="form-control" type="password" name="password" placeholder="Пароль" required></div>
                <div class="col-md-6"><input class="form-control" type="password" name="password_confirmation" placeholder="Повтор пароля" required></div>
                <div class="col-12"><label><input type="checkbox" name="terms" value="1" required> Мне исполнилось 18 лет, принимаю правила.</label></div>
            </div>
            <button class="btn btn-brand mt-4">{{ $t['register'] }}</button>
        </form>
    </div></div></div>

@elseif($page==='community')
    <div class="container py-5">
        <h1 class="h2">{{ $t['community'] }} <span class="badge text-bg-secondary">18+</span></h1>
        <form class="card card-body my-4"><div class="row g-2">
            <div class="col-md-4"><input class="form-control" name="city" value="{{ request('city') }}" placeholder="Город"></div>
            <div class="col-md-3"><select class="form-select" name="gender"><option value="">Любой пол</option><option value="male">Мужчины</option><option value="female">Женщины</option></select></div>
            <div class="col-md-3"><select class="form-select" name="goal"><option value="">Любая цель</option><option value="communication">Общение</option><option value="friendship">Дружба</option><option value="family">Семья</option><option value="work">Работа</option></select></div>
            <div class="col-md-2"><button class="btn btn-brand w-100">{{ $t['search'] }}</button></div>
        </div></form>
        @auth
            <div class="card card-body mb-4"><form method="post" action="{{ route('posts.store') }}">@csrf<div class="row g-2">
                <div class="col-md-3"><select class="form-select" name="type"><option value="general">Общение</option><option value="meeting">Знакомство</option><option value="work">Ищу работу</option><option value="housing">Жильё</option><option value="help">Нужна помощь</option></select></div>
                <div class="col-md-7"><textarea class="form-control" name="body" rows="2" maxlength="5000" required></textarea></div>
                <div class="col-md-2"><button class="btn btn-brand w-100 h-100">Опубликовать</button></div>
            </div></form></div>
        @endauth
        <div class="row g-4">
            <div class="col-lg-8"><h2 class="h4">Лента</h2>
                @forelse($posts as $post)<div class="card mb-3"><div class="card-body"><strong>{{ $post->user_name }}</strong><span class="badge text-bg-light float-end">{{ $post->type }}</span><p class="mt-2 mb-0">{!! nl2br(e($post->body)) !!}</p></div></div>@empty<p>Нет публикаций.</p>@endforelse
                {{ $posts->links() }}
            </div>
            <div class="col-lg-4"><h2 class="h4">Люди</h2><div class="row g-2">
                @foreach($people as $person)<div class="col-6"><div class="card h-100"><div class="card-body"><strong>{{ $person->name }}</strong><div class="small text-muted">{{ $person->city ?: 'Город не указан' }}</div><div class="small">{{ $person->profession }}</div>@auth<form method="post" action="{{ route('messages.start',$person->id) }}" class="mt-2">@csrf<button class="btn btn-sm btn-outline-primary w-100">Написать</button></form>@endauth</div></div></div>@endforeach
            </div>{{ $people->links() }}</div>
        </div>
    </div>

@elseif($page==='messages')
    <div class="container py-5" style="max-width:900px"><h1 class="h2">{{ $t['messages'] }}</h1>
        @forelse($conversations as $item)<a class="card my-3 text-decoration-none text-dark" href="{{ route('conversation',$item->id) }}"><div class="card-body"><strong>{{ $item->other_name }}</strong><div class="small text-muted">{{ \Carbon\Carbon::parse($item->updated_at)->diffForHumans() }}</div></div></a>@empty<div class="alert alert-light">Диалогов пока нет.</div>@endforelse
        {{ $conversations->links() }}
    </div>

@elseif($page==='conversation')
    <div class="container py-5" style="max-width:900px"><h1 class="h3">{{ $other?->name ?? 'Диалог' }}</h1>
        <div class="card my-3"><div class="card-body" style="min-height:350px">
            @foreach($chatMessages as $message)<div class="mb-3 {{ $message->sender_user_id===auth()->id()?'text-end':'' }}"><div class="d-inline-block rounded-3 px-3 py-2 {{ $message->sender_user_id===auth()->id()?'own':'bg-light' }}" style="max-width:80%">{!! nl2br(e($message->body)) !!}</div></div>@endforeach
        </div></div>
        <form method="post" action="{{ route('messages.send',$conversation) }}" class="card card-body">@csrf<div class="input-group"><textarea class="form-control" name="body" rows="2" maxlength="5000" required></textarea><button class="btn btn-brand">{{ $t['send'] }}</button></div></form>
    </div>

@elseif($page==='jobs')
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap"><h1 class="h2 mb-0">{{ $t['jobs'] }}</h1><a class="btn btn-outline-primary" href="{{ route('reviews',['type'=>'employer']) }}">Отзывы о работодателях</a></div>
        <form class="card card-body my-4"><div class="row g-2">
            <div class="col-md-4"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Профессия"></div>
            <div class="col-md-3"><input class="form-control" name="city" value="{{ request('city') }}" placeholder="Город"></div>
            <div class="col-md-2"><label><input type="checkbox" name="official" value="1"> Официально</label></div>
            <div class="col-md-2"><label><input type="checkbox" name="housing" value="1"> С жильём</label></div>
            <div class="col-md-1"><button class="btn btn-brand w-100">{{ $t['search'] }}</button></div>
        </div></form>
        <div class="row g-4">
            <div class="col-lg-8">
                @forelse($jobs as $job)<div class="card mb-3"><div class="card-body"><h2 class="h5">{{ $job->title }}</h2><div class="text-muted">{{ $job->employer_name }} · {{ $job->city }}</div><p class="mt-2">{{ Str::limit($job->description,300) }}</p>@if($job->official_employment)<span class="badge text-bg-success">Официально</span>@endif @if($job->housing_provided)<span class="badge text-bg-info">Жильё</span>@endif</div></div>@empty<p>Вакансий нет.</p>@endforelse
                {{ $jobs->links() }}
            </div>
            <div class="col-lg-4">
                @auth<div class="card"><div class="card-body"><h2 class="h5">Разместить вакансию</h2><p class="small text-muted">Публикация после проверки.</p><form method="post" action="{{ route('jobs.store') }}">@csrf
                    <input class="form-control mb-2" name="employer_name" placeholder="Работодатель" required>
                    <input class="form-control mb-2" name="tax_id" placeholder="ИНН">
                    <input class="form-control mb-2" name="title" placeholder="Вакансия" required>
                    <textarea class="form-control mb-2" name="description" rows="4" placeholder="Условия" required></textarea>
                    <input class="form-control mb-2" name="city" placeholder="Город" required>
                    <div class="row g-2"><div class="col"><input class="form-control" type="number" name="salary_from" placeholder="От"></div><div class="col"><input class="form-control" type="number" name="salary_to" placeholder="До"></div></div>
                    <input class="form-control my-2" name="contact_phone" placeholder="Телефон" required>
                    <label class="d-block"><input type="checkbox" name="official_employment" value="1"> Официальное оформление</label>
                    <label class="d-block mb-2"><input type="checkbox" name="housing_provided" value="1"> Предоставляется жильё</label>
                    <button class="btn btn-brand w-100">На модерацию</button>
                </form></div></div>@else<div class="alert alert-info">Войдите, чтобы разместить вакансию.</div>@endauth
            </div>
        </div>
    </div>

@elseif($page==='letters')
    <div class="container py-5"><h1 class="h2">{{ $t['letters'] }}</h1><p class="text-muted">Заполните поля, получите готовое обращение и проверьте его перед отправкой.</p><div class="row g-4">
        @foreach($templates as $template)@php($fields=json_decode($template->fields,true)?:[])<div class="col-lg-6"><div class="card"><div class="card-body"><h2 class="h5">{{ $tr($template->title) }}</h2><p>{{ $tr($template->description) }}</p><form method="post" action="{{ route('letters.preview',$template->slug) }}">@csrf
            @foreach($fields as $field)@php($label=$field['label'][$locale]??$field['label']['ru']??$field['name'])<label class="form-label small">{{ $label }}</label><input class="form-control mb-2" name="{{ $field['name'] }}" @required($field['required']??false)>@endforeach
            <button class="btn btn-brand mt-2">Сформировать</button>
        </form></div></div></div>@endforeach
    </div></div>

@elseif($page==='letter_preview')
    <div class="container py-5" style="max-width:900px"><button class="btn btn-outline-secondary float-end no-print" onclick="window.print()">Печать / PDF</button><h1 class="h3">{{ $tr($template->title) }}</h1><div class="alert alert-warning">Проверьте даты, суммы, адресата. Шаблон не заменяет консультацию юриста.</div><div class="card"><div class="card-body p-4"><pre style="white-space:pre-wrap;font-family:inherit">{!! $body !!}</pre></div></div></div>

@elseif($page==='safety')
    <div class="container py-5"><div class="alert alert-danger"><strong>Срочно:</strong> при угрозе жизни звоните 112. Запрещены самосуд, травля и публикация чужих персональных данных.</div><h1 class="h2">{{ $t['safety'] }} и правовая помощь</h1><div class="row g-4">
        <div class="col-lg-7">@foreach($articles as $article)<div class="card mb-3"><div class="card-body">@if($article->emergency)<span class="badge text-bg-danger">Срочно</span>@endif<h2 class="h5 mt-2">{{ $tr($article->title) }}</h2><p>{{ $tr($article->summary) }}</p><div class="small">{!! nl2br(e($tr($article->body))) !!}</div></div></div>@endforeach{{ $articles->links() }}</div>
        <div class="col-lg-5">@auth<div class="card"><div class="card-body"><h2 class="h5">Сообщить модератору</h2><form method="post" action="{{ route('safety.report') }}">@csrf
            <select class="form-select mb-2" name="category"><option value="fraud">Мошенничество</option><option value="extortion">Вымогательство</option><option value="documents">Удержание документов</option><option value="violence">Насилие</option><option value="missing_person">Пропавший человек</option><option value="trafficking">Принудительный труд</option><option value="detention">Задержание</option><option value="wage_theft">Невыплата зарплаты</option><option value="other">Другое</option></select>
            <input class="form-control mb-2" name="city" placeholder="Город">
            <textarea class="form-control mb-2" name="description" rows="6" placeholder="Факты, даты и место" required></textarea>
            <label><input type="checkbox" name="allow_contact" value="1"> Разрешаю связаться</label>
            <input class="form-control my-2" name="contact" placeholder="Контакт">
            <button class="btn btn-danger w-100">Передать модератору</button>
        </form></div></div>@else<div class="alert alert-info">Войдите, чтобы отправить сообщение модератору.</div>@endauth</div>
    </div></div>
@endif

<footer class="bg-white border-top mt-5 py-4"><div class="container small text-muted">{{ $currentDiaspora->name }} · {{ now()->year }}. Раздел безопасности предназначен только для законной защиты прав.</div></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
