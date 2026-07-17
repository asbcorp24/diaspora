@php
    $isEdit = !is_null($item);
    $title = $isEdit ? $localized($item->title) : '';
    $titleNative = $isEdit ? $localized($item->title, $nativeLocale) : '';
    $descriptionSource = $kind === 'letter' ? ($item->description ?? null) : ($item->summary ?? null);
    $description = $isEdit ? $localized($descriptionSource) : '';
    $descriptionNative = $isEdit ? $localized($descriptionSource, $nativeLocale) : '';
    $bodySource = $kind === 'letter' ? ($item->body_template ?? null) : ($item->body ?? null);
    $body = $isEdit ? $localized($bodySource) : '';
    $bodyNative = $isEdit ? $localized($bodySource, $nativeLocale) : '';
@endphp
<form method="post" action="{{ $action }}">
    @csrf
    @if($method !== 'post') @method($method) @endif
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Категория</label><input class="form-control" name="category" value="{{ $item->category ?? 'general' }}" required></div>
        <div class="col-md-8"><label class="form-label">Slug</label><input class="form-control" name="slug" value="{{ $item->slug ?? '' }}" placeholder="Можно оставить пустым"></div>
        <div class="col-md-6"><label class="form-label">Заголовок RU</label><input class="form-control" name="title_ru" value="{{ $title }}" required></div>
        <div class="col-md-6"><label class="form-label">Заголовок {{ strtoupper($nativeLocale) }}</label><input class="form-control" name="title_native" value="{{ $titleNative }}"></div>
        <div class="col-md-6"><label class="form-label">Описание RU</label><textarea class="form-control" name="description_ru" rows="3">{{ $description }}</textarea></div>
        <div class="col-md-6"><label class="form-label">Описание {{ strtoupper($nativeLocale) }}</label><textarea class="form-control" name="description_native" rows="3">{{ $descriptionNative }}</textarea></div>
        <div class="col-md-6"><label class="form-label">Текст RU</label><textarea class="form-control" name="body_ru" rows="10" required>{{ $body }}</textarea></div>
        <div class="col-md-6"><label class="form-label">Текст {{ strtoupper($nativeLocale) }}</label><textarea class="form-control" name="body_native" rows="10">{{ $bodyNative }}</textarea></div>
        @if($kind === 'letter')
            <div class="col-12"><label class="form-label">Поля формы в JSON</label><textarea class="form-control font-monospace" name="fields_json" rows="8" required>{{ $isEdit ? json_encode(is_string($item->fields) ? json_decode($item->fields, true) : $item->fields, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : "[]" }}</textarea></div>
            <div class="col-12"><label><input type="checkbox" name="is_active" value="1" @checked(!$isEdit || $item->is_active)> Активен</label></div>
        @else
            <div class="col-12 d-flex gap-4"><label><input type="checkbox" name="emergency" value="1" @checked($isEdit && $item->emergency)> Срочный материал</label><label><input type="checkbox" name="is_published" value="1" @checked($isEdit && $item->is_published)> Опубликован</label></div>
        @endif
    </div>
    <button class="btn btn-primary mt-3">{{ $isEdit ? 'Сохранить' : 'Создать' }}</button>
</form>
