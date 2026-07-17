<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ app()->getLocale()==='uz' ? 'Ish beruvchilar va uy egalari haqida sharhlar' : 'Отзывы о работодателях и арендодателях' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @php
        $locale = app()->getLocale();
        $primary = data_get($currentDiaspora->theme, 'primary', '#167B5A');
        $secondary = data_get($currentDiaspora->theme, 'secondary', '#2C5DAA');
        $isModerator = auth()->check() && in_array(auth()->user()->role, ['moderator','admin','superadmin'], true);
        $yesNo = fn($value) => $value === null ? '—' : ($value ? ($locale==='uz'?'Ha':'Да') : ($locale==='uz'?'Yo‘q':'Нет'));
        $stars = fn($rating) => str_repeat('★', (int)$rating).str_repeat('☆', 5-(int)$rating);
        $depositLabels = $locale==='uz' ? [
            'returned'=>'To‘liq qaytarildi','partially_returned'=>'Qisman qaytarildi','not_returned'=>'Qaytarilmadi','not_applicable'=>'Garov bo‘lmagan'
        ] : [
            'returned'=>'Возвращён полностью','partially_returned'=>'Возвращён частично','not_returned'=>'Не возвращён','not_applicable'=>'Залога не было'
        ];
    @endphp
    <style>
        :root{--brand:{{ $primary }};--brand2:{{ $secondary }}}
        body{background:#f5f7fb}.navbar-brand{color:var(--brand)!important}.btn-brand{background:var(--brand);border-color:var(--brand);color:#fff}.btn-brand:hover{background:var(--brand2);color:#fff}.card{border:0;box-shadow:0 8px 28px rgba(20,35,65,.07)}.rating{color:#e5a100;letter-spacing:1px}.review-text{white-space:pre-wrap}.metric{background:#f8f9fa;border-radius:.7rem;padding:.55rem .7rem}.nav-pills .nav-link.active{background:var(--brand)}
    </style>
</head>
<body>
<div class="bg-warning-subtle border-bottom py-2 text-center small">
    {{ $locale==='uz' ? 'Sharhda faqat tekshiriladigan faktlarni yozing. Pasport, aniq xonadon raqami va bank ma’lumotlarini joylamang.' : 'Пишите только проверяемые факты. Не публикуйте паспорта, точный номер квартиры и банковские данные.' }}
</div>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('home') }}">{{ $currentDiaspora->native_name }}</a>
        <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#reviewsNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="reviewsNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="{{ route('community') }}">{{ $locale==='uz'?'Muloqot':'Общение' }}</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('jobs') }}">{{ $locale==='uz'?'Ish':'Работа' }}</a></li>
                <li class="nav-item"><a class="nav-link active" href="{{ route('reviews') }}">{{ $locale==='uz'?'Sharhlar':'Отзывы' }}</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('letters') }}">{{ $locale==='uz'?'Tayyor xatlar':'Готовые письма' }}</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('safety') }}">{{ $locale==='uz'?'Xavfsizlik':'Безопасность' }}</a></li>
                @if($isModerator)<li class="nav-item"><a class="nav-link text-danger" href="{{ route('reviews.moderation') }}">{{ $locale==='uz'?'Moderatsiya':'Модерация' }}</a></li>@endif
            </ul>
            <div class="d-flex gap-2 align-items-center">
                @foreach($currentDiaspora->supported_locales as $lang)<a class="small text-decoration-none {{ $locale===$lang?'fw-bold':'' }}" href="{{ request()->fullUrlWithQuery(['lang'=>$lang]) }}">{{ strtoupper($lang) }}</a>@endforeach
                @auth<span class="small">{{ auth()->user()->name }}</span>@else<a class="btn btn-sm btn-outline-secondary" href="{{ route('login') }}">{{ $locale==='uz'?'Kirish':'Войти' }}</a>@endauth
            </div>
        </div>
    </div>
</nav>

<div class="container py-5">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">{{ $locale==='uz' ? 'Ish beruvchilar va uy egalari haqida sharhlar' : 'Отзывы о работодателях и арендодателях' }}</h1>
            <p class="text-muted mb-0">{{ $locale==='uz' ? 'Ish va uy-joy tanlashdan oldin boshqa odamlarning tajribasini tekshiring.' : 'Проверяйте опыт других людей перед выбором работы или жилья.' }}</p>
        </div>
        @auth<button class="btn btn-brand" data-bs-toggle="offcanvas" data-bs-target="#addReview">{{ $locale==='uz'?'Sharh qoldirish':'Оставить отзыв' }}</button>@endauth
    </div>

    <ul class="nav nav-pills mb-4">
        <li class="nav-item"><a class="nav-link {{ $type==='employer'?'active':'' }}" href="{{ route('reviews',['type'=>'employer']) }}">{{ $locale==='uz'?'Ish beruvchilar':'Работодатели' }}</a></li>
        <li class="nav-item"><a class="nav-link {{ $type==='rental'?'active':'' }}" href="{{ route('reviews',['type'=>'rental']) }}">{{ $locale==='uz'?'Uy egalari va uy-joy':'Арендодатели и жильё' }}</a></li>
    </ul>

    <form class="card card-body mb-4" method="get">
        <input type="hidden" name="type" value="{{ $type }}">
        <div class="row g-2">
            <div class="col-md-6"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="{{ $type==='employer' ? ($locale==='uz'?'Tashkilot, STIR yoki matn':'Организация, ИНН или текст') : ($locale==='uz'?'Uy egasi, tuman yoki ko‘cha':'Арендодатель, район или улица') }}"></div>
            <div class="col-md-4"><input class="form-control" name="city" value="{{ request('city') }}" placeholder="{{ $locale==='uz'?'Shahar':'Город' }}"></div>
            <div class="col-md-2"><button class="btn btn-brand w-100">{{ $locale==='uz'?'Qidirish':'Найти' }}</button></div>
        </div>
    </form>

    @if($type==='employer')
        @forelse($employerReviews as $review)
            <article class="card mb-3"><div class="card-body p-4">
                <div class="d-flex justify-content-between gap-3 flex-wrap">
                    <div><h2 class="h5 mb-1">{{ $review->subject_name }}</h2><div class="text-muted small">{{ $review->city }} @if($review->tax_id) · {{ $locale==='uz'?'STIR':'ИНН' }} {{ $review->tax_id }} @endif @if($review->verification_status==='verified') · <span class="text-success">{{ $locale==='uz'?'Tasdiqlangan':'Проверен' }}</span>@endif</div></div>
                    <div class="text-end"><div class="rating fs-5" title="{{ $review->rating }}/5">{{ $stars($review->rating) }}</div><div class="small text-muted">{{ $review->rating }}/5</div></div>
                </div>
                <div class="row g-2 my-3">
                    <div class="col-6 col-lg-3"><div class="metric small"><strong>{{ $locale==='uz'?'Maosh vaqtida':'Зарплата вовремя' }}</strong><br>{{ $yesNo($review->salary_on_time) }}</div></div>
                    <div class="col-6 col-lg-3"><div class="metric small"><strong>{{ $locale==='uz'?'Shartnoma':'Трудовой договор' }}</strong><br>{{ $yesNo($review->contract_provided) }}</div></div>
                    <div class="col-6 col-lg-3"><div class="metric small"><strong>{{ $locale==='uz'?'Shartlar mos':'Условия совпали' }}</strong><br>{{ $yesNo($review->conditions_match) }}</div></div>
                    <div class="col-6 col-lg-3"><div class="metric small"><strong>{{ $locale==='uz'?'Tavsiya qiladi':'Рекомендует' }}</strong><br>{{ $yesNo($review->would_recommend) }}</div></div>
                </div>
                @if($review->pros)<div class="mb-2"><strong class="text-success">{{ $locale==='uz'?'Afzalliklari':'Плюсы' }}:</strong> <span class="review-text">{{ $review->pros }}</span></div>@endif
                @if($review->cons)<div class="mb-2"><strong class="text-danger">{{ $locale==='uz'?'Kamchiliklari':'Минусы' }}:</strong> <span class="review-text">{{ $review->cons }}</span></div>@endif
                <p class="review-text mb-3">{{ $review->comment }}</p>
                <div class="d-flex justify-content-between align-items-center small text-muted">
                    <span>{{ $review->anonymous_public ? ($locale==='uz'?'Anonim foydalanuvchi':'Анонимный пользователь') : $review->reviewer_name }} · {{ \Carbon\Carbon::parse($review->created_at)->format('d.m.Y') }}</span>
                    @auth<button class="btn btn-sm btn-link text-danger p-0" data-bs-toggle="collapse" data-bs-target="#report-employer-{{ $review->id }}">{{ $locale==='uz'?'Shikoyat':'Пожаловаться' }}</button>@endauth
                </div>
                @auth<div class="collapse mt-3" id="report-employer-{{ $review->id }}"><form method="post" action="{{ route('reviews.report',['type'=>'employer','review'=>$review->id]) }}" class="border rounded p-3">@csrf<div class="row g-2"><div class="col-md-4"><select class="form-select" name="reason" required><option value="false_information">{{ $locale==='uz'?'Noto‘g‘ri ma’lumot':'Ложная информация' }}</option><option value="personal_data">{{ $locale==='uz'?'Shaxsiy ma’lumotlar':'Персональные данные' }}</option><option value="insults">{{ $locale==='uz'?'Haqorat':'Оскорбления' }}</option><option value="spam">Spam</option><option value="conflict_of_interest">{{ $locale==='uz'?'Manfaatlar to‘qnashuvi':'Конфликт интересов' }}</option><option value="other">{{ $locale==='uz'?'Boshqa':'Другое' }}</option></select></div><div class="col-md-6"><input class="form-control" name="details" placeholder="{{ $locale==='uz'?'Izoh':'Пояснение' }}"></div><div class="col-md-2"><button class="btn btn-outline-danger w-100">{{ $locale==='uz'?'Yuborish':'Отправить' }}</button></div></div></form></div>@endauth
            </div></article>
        @empty<div class="alert alert-light border">{{ $locale==='uz'?'Sharhlar topilmadi.':'Отзывы не найдены.' }}</div>@endforelse
        @if(method_exists($employerReviews,'links')){{ $employerReviews->links() }}@endif
    @else
        @forelse($rentalReviews as $review)
            <article class="card mb-3"><div class="card-body p-4">
                <div class="d-flex justify-content-between gap-3 flex-wrap">
                    <div><h2 class="h5 mb-1">{{ $review->subject_name }}</h2><div class="text-muted small">{{ $review->city }} @if($review->district) · {{ $review->district }} @endif @if($review->public_location) · {{ $review->public_location }} @endif</div><div class="small">{{ $review->property_title ?: ($locale==='uz'?'Ijara uy-joyi':'Арендное жильё') }}</div></div>
                    <div class="text-end"><div class="small">{{ $locale==='uz'?'Uy egasi':'Арендодатель' }}</div><div class="rating">{{ $stars($review->landlord_rating) }}</div><div class="small mt-1">{{ $locale==='uz'?'Uy-joy':'Жильё' }}</div><div class="rating">{{ $stars($review->housing_rating) }}</div></div>
                </div>
                <div class="row g-2 my-3">
                    <div class="col-md-4"><div class="metric small"><strong>{{ $locale==='uz'?'E’longa mosligi':'Соответствие объявлению' }}</strong><br>{{ $review->listing_accuracy_rating ? $review->listing_accuracy_rating.'/5' : '—' }}</div></div>
                    <div class="col-md-4"><div class="metric small"><strong>{{ $locale==='uz'?'Garov':'Залог' }}</strong><br>{{ $depositLabels[$review->deposit_result] ?? $review->deposit_result }}</div></div>
                    <div class="col-md-4"><div class="metric small"><strong>{{ $locale==='uz'?'Tavsiya qiladi':'Рекомендует' }}</strong><br>{{ $yesNo($review->would_recommend) }}</div></div>
                </div>
                @if($review->pros)<div class="mb-2"><strong class="text-success">{{ $locale==='uz'?'Afzalliklari':'Плюсы' }}:</strong> <span class="review-text">{{ $review->pros }}</span></div>@endif
                @if($review->cons)<div class="mb-2"><strong class="text-danger">{{ $locale==='uz'?'Kamchiliklari':'Минусы' }}:</strong> <span class="review-text">{{ $review->cons }}</span></div>@endif
                <p class="review-text mb-3">{{ $review->comment }}</p>
                <div class="d-flex justify-content-between align-items-center small text-muted">
                    <span>{{ $review->anonymous_public ? ($locale==='uz'?'Anonim foydalanuvchi':'Анонимный пользователь') : $review->reviewer_name }} · {{ \Carbon\Carbon::parse($review->created_at)->format('d.m.Y') }}</span>
                    @auth<button class="btn btn-sm btn-link text-danger p-0" data-bs-toggle="collapse" data-bs-target="#report-rental-{{ $review->id }}">{{ $locale==='uz'?'Shikoyat':'Пожаловаться' }}</button>@endauth
                </div>
                @auth<div class="collapse mt-3" id="report-rental-{{ $review->id }}"><form method="post" action="{{ route('reviews.report',['type'=>'rental','review'=>$review->id]) }}" class="border rounded p-3">@csrf<div class="row g-2"><div class="col-md-4"><select class="form-select" name="reason" required><option value="false_information">{{ $locale==='uz'?'Noto‘g‘ri ma’lumot':'Ложная информация' }}</option><option value="personal_data">{{ $locale==='uz'?'Shaxsiy ma’lumotlar':'Персональные данные' }}</option><option value="insults">{{ $locale==='uz'?'Haqorat':'Оскорбления' }}</option><option value="spam">Spam</option><option value="conflict_of_interest">{{ $locale==='uz'?'Manfaatlar to‘qnashuvi':'Конфликт интересов' }}</option><option value="other">{{ $locale==='uz'?'Boshqa':'Другое' }}</option></select></div><div class="col-md-6"><input class="form-control" name="details" placeholder="{{ $locale==='uz'?'Izoh':'Пояснение' }}"></div><div class="col-md-2"><button class="btn btn-outline-danger w-100">{{ $locale==='uz'?'Yuborish':'Отправить' }}</button></div></div></form></div>@endauth
            </div></article>
        @empty<div class="alert alert-light border">{{ $locale==='uz'?'Sharhlar topilmadi.':'Отзывы не найдены.' }}</div>@endforelse
        @if(method_exists($rentalReviews,'links')){{ $rentalReviews->links() }}@endif
    @endif
</div>

@auth
<div class="offcanvas offcanvas-end" tabindex="-1" id="addReview" style="width:min(680px,100vw)">
    <div class="offcanvas-header"><h2 class="offcanvas-title h4">{{ $locale==='uz'?'Sharh qoldirish':'Оставить отзыв' }}</h2><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
    <div class="offcanvas-body">
        <div class="alert alert-warning small">{{ $locale==='uz' ? 'Aniq sanalar, va’dalar va haqiqatda sodir bo‘lgan holatlarni yozing. Haqorat, tahdid va begona shaxsiy ma’lumotlar taqiqlanadi. Sharh moderatsiyadan so‘ng chiqadi.' : 'Указывайте даты, обещанные и фактические условия. Запрещены оскорбления, угрозы и чужие персональные данные. Отзыв появится после модерации.' }}</div>
        <ul class="nav nav-tabs mb-3"><li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#employerForm">{{ $locale==='uz'?'Ish beruvchi':'Работодатель' }}</button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#rentalForm">{{ $locale==='uz'?'Uy egasi va uy-joy':'Арендодатель и жильё' }}</button></li></ul>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="employerForm">
                <form method="post" action="{{ route('reviews.employer.store') }}">@csrf
                    <div class="row g-3">
                        <div class="col-md-7"><label class="form-label">{{ $locale==='uz'?'Ish beruvchi nomi':'Название работодателя' }}</label><input class="form-control" name="employer_name" required maxlength="190"></div>
                        <div class="col-md-5"><label class="form-label">{{ $locale==='uz'?'Shahar':'Город' }}</label><input class="form-control" name="city" required maxlength="120"></div>
                        <div class="col-md-7"><label class="form-label">{{ $locale==='uz'?'Yuridik nomi':'Юридическое название' }}</label><input class="form-control" name="legal_name" maxlength="190"></div>
                        <div class="col-md-5"><label class="form-label">{{ $locale==='uz'?'STIR':'ИНН' }}</label><input class="form-control" name="tax_id" maxlength="30"></div>
                        <div class="col-md-4"><label class="form-label">{{ $locale==='uz'?'Umumiy baho':'Общая оценка' }}</label><select class="form-select" name="rating" required>@for($i=5;$i>=1;$i--)<option value="{{ $i }}">{{ $i }} / 5</option>@endfor</select></div>
                        <div class="col-md-4"><label class="form-label">{{ $locale==='uz'?'Ish boshlangan':'Начало работы' }}</label><input class="form-control" type="date" name="employment_started_at"></div>
                        <div class="col-md-4"><label class="form-label">{{ $locale==='uz'?'Ish tugagan':'Окончание работы' }}</label><input class="form-control" type="date" name="employment_ended_at"></div>
                        @foreach(['salary_on_time'=>($locale==='uz'?'Maosh vaqtida to‘landimi?':'Зарплата выплачивалась вовремя?'),'contract_provided'=>($locale==='uz'?'Shartnoma berildimi?':'Выдали трудовой договор?'),'conditions_match'=>($locale==='uz'?'Shartlar va’daga mosmi?':'Условия совпали с обещанными?'),'would_recommend'=>($locale==='uz'?'Tavsiya qilasizmi?':'Рекомендуете работодателя?')] as $field=>$label)
                            <div class="col-md-6"><label class="form-label">{{ $label }}</label><select class="form-select" name="{{ $field }}"><option value="">{{ $locale==='uz'?'Ko‘rsatilmagan':'Не указано' }}</option><option value="1">{{ $locale==='uz'?'Ha':'Да' }}</option><option value="0">{{ $locale==='uz'?'Yo‘q':'Нет' }}</option></select></div>
                        @endforeach
                        <div class="col-12"><label class="form-label text-success">{{ $locale==='uz'?'Afzalliklari':'Плюсы' }}</label><textarea class="form-control" name="pros" rows="2" maxlength="3000"></textarea></div>
                        <div class="col-12"><label class="form-label text-danger">{{ $locale==='uz'?'Kamchiliklari':'Минусы' }}</label><textarea class="form-control" name="cons" rows="2" maxlength="3000"></textarea></div>
                        <div class="col-12"><label class="form-label">{{ $locale==='uz'?'Batafsil sharh':'Подробный отзыв' }}</label><textarea class="form-control" name="comment" rows="5" minlength="30" maxlength="10000" required></textarea></div>
                        <div class="col-12"><label><input type="checkbox" name="anonymous_public" value="1"> {{ $locale==='uz'?'Ismimni ochiq ko‘rsatmaslik':'Не показывать моё имя публично' }}</label></div>
                        <div class="col-12"><label><input type="checkbox" name="rules_confirmed" value="1" required> {{ $locale==='uz'?'Sharh shaxsiy tajribamga asoslangan va qoidalarni buzmaydi.':'Отзыв основан на моём личном опыте и не нарушает правила.' }}</label></div>
                    </div><button class="btn btn-brand w-100 mt-4">{{ $locale==='uz'?'Moderatsiyaga yuborish':'Отправить на модерацию' }}</button>
                </form>
            </div>
            <div class="tab-pane fade" id="rentalForm">
                <form method="post" action="{{ route('reviews.rental.store') }}">@csrf
                    <div class="row g-3">
                        <div class="col-md-7"><label class="form-label">{{ $locale==='uz'?'Uy egasining ismi yoki nomi':'Имя или обозначение арендодателя' }}</label><input class="form-control" name="landlord_name" required maxlength="190"></div>
                        <div class="col-md-5"><label class="form-label">{{ $locale==='uz'?'Telefonning oxirgi raqamlari':'Последние цифры телефона' }}</label><input class="form-control" name="contact_hint" maxlength="30" placeholder="***-**-12"></div>
                        <div class="col-md-5"><label class="form-label">{{ $locale==='uz'?'Shahar':'Город' }}</label><input class="form-control" name="city" required maxlength="120"></div>
                        <div class="col-md-7"><label class="form-label">{{ $locale==='uz'?'Tuman':'Район' }}</label><input class="form-control" name="district" maxlength="120"></div>
                        <div class="col-md-7"><label class="form-label">{{ $locale==='uz'?'Ko‘cha yoki joy, xonadon raqamisiz':'Улица или ориентир без номера квартиры' }}</label><input class="form-control" name="public_location" maxlength="190"></div>
                        <div class="col-md-5"><label class="form-label">{{ $locale==='uz'?'Uy turi':'Тип жилья' }}</label><select class="form-select" name="property_type"><option value="apartment">{{ $locale==='uz'?'Kvartira':'Квартира' }}</option><option value="room">{{ $locale==='uz'?'Xona':'Комната' }}</option><option value="house">{{ $locale==='uz'?'Uy':'Дом' }}</option><option value="hostel">{{ $locale==='uz'?'Yotoqxona':'Общежитие' }}</option><option value="bed_place">{{ $locale==='uz'?'Yotoq joyi':'Койко-место' }}</option></select></div>
                        <div class="col-12"><label class="form-label">{{ $locale==='uz'?'Uy-joyning qisqa nomi':'Краткое название жилья' }}</label><input class="form-control" name="property_title" maxlength="190" placeholder="{{ $locale==='uz'?'Metro yonidagi xona':'Комната рядом с метро' }}"></div>
                        <div class="col-md-4"><label class="form-label">{{ $locale==='uz'?'Uy egasi bahosi':'Оценка арендодателя' }}</label><select class="form-select" name="landlord_rating">@for($i=5;$i>=1;$i--)<option value="{{ $i }}">{{ $i }} / 5</option>@endfor</select></div>
                        <div class="col-md-4"><label class="form-label">{{ $locale==='uz'?'Uy-joy bahosi':'Оценка жилья' }}</label><select class="form-select" name="housing_rating">@for($i=5;$i>=1;$i--)<option value="{{ $i }}">{{ $i }} / 5</option>@endfor</select></div>
                        <div class="col-md-4"><label class="form-label">{{ $locale==='uz'?'E’longa mosligi':'Соответствие объявлению' }}</label><select class="form-select" name="listing_accuracy_rating"><option value="">—</option>@for($i=5;$i>=1;$i--)<option value="{{ $i }}">{{ $i }} / 5</option>@endfor</select></div>
                        <div class="col-md-4"><label class="form-label">{{ $locale==='uz'?'Ijara boshlangan':'Начало аренды' }}</label><input class="form-control" type="date" name="rental_started_at"></div>
                        <div class="col-md-4"><label class="form-label">{{ $locale==='uz'?'Ijara tugagan':'Окончание аренды' }}</label><input class="form-control" type="date" name="rental_ended_at"></div>
                        <div class="col-md-4"><label class="form-label">{{ $locale==='uz'?'Garov natijasi':'Что было с залогом' }}</label><select class="form-select" name="deposit_result"><option value="not_applicable">{{ $depositLabels['not_applicable'] }}</option><option value="returned">{{ $depositLabels['returned'] }}</option><option value="partially_returned">{{ $depositLabels['partially_returned'] }}</option><option value="not_returned">{{ $depositLabels['not_returned'] }}</option></select></div>
                        <div class="col-md-6"><label class="form-label">{{ $locale==='uz'?'Tavsiya qilasizmi?':'Рекомендуете арендодателя?' }}</label><select class="form-select" name="would_recommend"><option value="">{{ $locale==='uz'?'Ko‘rsatilmagan':'Не указано' }}</option><option value="1">{{ $locale==='uz'?'Ha':'Да' }}</option><option value="0">{{ $locale==='uz'?'Yo‘q':'Нет' }}</option></select></div>
                        <div class="col-12"><label class="form-label text-success">{{ $locale==='uz'?'Afzalliklari':'Плюсы' }}</label><textarea class="form-control" name="pros" rows="2" maxlength="3000"></textarea></div>
                        <div class="col-12"><label class="form-label text-danger">{{ $locale==='uz'?'Kamchiliklari':'Минусы' }}</label><textarea class="form-control" name="cons" rows="2" maxlength="3000"></textarea></div>
                        <div class="col-12"><label class="form-label">{{ $locale==='uz'?'Batafsil sharh':'Подробный отзыв' }}</label><textarea class="form-control" name="comment" rows="5" minlength="30" maxlength="10000" required></textarea></div>
                        <div class="col-12"><label><input type="checkbox" name="anonymous_public" value="1"> {{ $locale==='uz'?'Ismimni ochiq ko‘rsatmaslik':'Не показывать моё имя публично' }}</label></div>
                        <div class="col-12"><label><input type="checkbox" name="rules_confirmed" value="1" required> {{ $locale==='uz'?'Sharh shaxsiy ijara tajribamga asoslangan va qoidalarni buzmaydi.':'Отзыв основан на моём личном опыте аренды и не нарушает правила.' }}</label></div>
                    </div><button class="btn btn-brand w-100 mt-4">{{ $locale==='uz'?'Moderatsiyaga yuborish':'Отправить на модерацию' }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endauth

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
