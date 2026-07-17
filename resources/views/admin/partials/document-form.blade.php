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
    $formId = 'document-form-'.$kind.'-'.($item->id ?? 'new');
    $fieldRows = [];

    if ($kind === 'letter' && $isEdit) {
        $decodedFields = is_string($item->fields) ? json_decode($item->fields, true) : (array) $item->fields;
        foreach (($decodedFields ?: []) as $field) {
            $fieldLabel = is_array($field['label'] ?? null) ? $field['label'] : ['ru' => ($field['label'] ?? '')];
            $fieldPlaceholder = is_array($field['placeholder'] ?? null) ? $field['placeholder'] : ['ru' => ($field['placeholder'] ?? '')];
            $fieldOptions = collect($field['options'] ?? [])->map(function ($option) {
                if (is_string($option)) return $option;
                if (is_array($option)) return $option['value'] ?? data_get($option, 'label.ru') ?? '';
                return '';
            })->filter()->implode(', ');
            $fieldRows[] = [
                'name' => $field['name'] ?? '',
                'label_ru' => $fieldLabel['ru'] ?? reset($fieldLabel) ?: '',
                'label_native' => $fieldLabel[$nativeLocale] ?? '',
                'type' => $field['type'] ?? 'text',
                'placeholder_ru' => $fieldPlaceholder['ru'] ?? '',
                'placeholder_native' => $fieldPlaceholder[$nativeLocale] ?? '',
                'options' => $fieldOptions,
                'required' => !empty($field['required']),
            ];
        }
    }

    if ($kind === 'letter' && $fieldRows === []) {
        $fieldRows[] = ['name'=>'full_name','label_ru'=>'ФИО','label_native'=>'','type'=>'text','placeholder_ru'=>'Полностью','placeholder_native'=>'','options'=>'','required'=>true];
    }

    $categories = [
        'employment' => 'Трудовые отношения',
        'migration' => 'Миграционные вопросы',
        'housing' => 'Жильё и аренда',
        'family' => 'Семейные вопросы',
        'consumer' => 'Защита потребителей',
        'government' => 'Обращения в госорганы',
        'documents' => 'Документы и персональные данные',
        'court' => 'Судебные документы',
        'other' => 'Другое',
    ];
@endphp
<form method="post" action="{{ $action }}" id="{{ $formId }}">
    @csrf
    @if($method !== 'post') @method($method) @endif
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Категория</label>
            @if($kind === 'letter')
                <select class="form-select" name="category" required>
                    @foreach($categories as $categoryKey => $categoryLabel)
                        <option value="{{ $categoryKey }}" @selected(($item->category ?? 'employment') === $categoryKey)>{{ $categoryLabel }}</option>
                    @endforeach
                </select>
            @else
                <input class="form-control" name="category" value="{{ $item->category ?? 'general' }}" required>
            @endif
        </div>
        <div class="col-md-8"><label class="form-label">Slug</label><input class="form-control" name="slug" value="{{ $item->slug ?? '' }}" placeholder="Можно оставить пустым"></div>
        <div class="col-md-6"><label class="form-label">Заголовок RU</label><input class="form-control" name="title_ru" value="{{ $title }}" required></div>
        <div class="col-md-6"><label class="form-label">Заголовок {{ strtoupper($nativeLocale) }}</label><input class="form-control" name="title_native" value="{{ $titleNative }}"></div>
        <div class="col-md-6"><label class="form-label">Описание RU</label><textarea class="form-control" name="description_ru" rows="3">{{ $description }}</textarea></div>
        <div class="col-md-6"><label class="form-label">Описание {{ strtoupper($nativeLocale) }}</label><textarea class="form-control" name="description_native" rows="3">{{ $descriptionNative }}</textarea></div>
        <div class="col-md-6">
            <label class="form-label">Текст RU</label>
            @if($kind === 'letter')<div class="form-text mb-1">Вставляйте поля как <code>@{{full_name}}</code>, <code>@{{date}}</code>.</div>@endif
            <textarea class="form-control font-monospace" name="body_ru" rows="14" required>{{ $body }}</textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">Текст {{ strtoupper($nativeLocale) }}</label>
            <textarea class="form-control font-monospace" name="body_native" rows="14">{{ $bodyNative }}</textarea>
        </div>

        @if($kind === 'letter')
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h3 class="h6 mb-1">Поля, которые заполнит пользователь</h3>
                        <div class="small text-muted">Системное имя поля должно совпадать с переменной в тексте документа.</div>
                    </div>
                    <button class="btn btn-outline-primary btn-sm legal-add-field" type="button"><i class="bi bi-plus-circle"></i> Добавить поле</button>
                </div>
                <div class="legal-fields-builder d-grid gap-2">
                    @foreach($fieldRows as $field)
                        <div class="legal-field-row border rounded-3 p-3 bg-light">
                            <div class="row g-2">
                                <div class="col-md-3"><label class="form-label small">Подпись RU</label><input class="form-control form-control-sm" data-field="label_ru" value="{{ $field['label_ru'] }}" required></div>
                                <div class="col-md-3"><label class="form-label small">Подпись {{ strtoupper($nativeLocale) }}</label><input class="form-control form-control-sm" data-field="label_native" value="{{ $field['label_native'] }}"></div>
                                <div class="col-md-2"><label class="form-label small">Системное имя</label><input class="form-control form-control-sm font-monospace" data-field="name" value="{{ $field['name'] }}" placeholder="full_name" required></div>
                                <div class="col-md-2"><label class="form-label small">Тип</label><select class="form-select form-select-sm" data-field="type">@foreach(['text'=>'Строка','textarea'=>'Большой текст','email'=>'Email','tel'=>'Телефон','number'=>'Число','date'=>'Дата','select'=>'Список'] as $typeKey=>$typeLabel)<option value="{{ $typeKey }}" @selected($field['type']===$typeKey)>{{ $typeLabel }}</option>@endforeach</select></div>
                                <div class="col-md-2 d-flex align-items-end justify-content-between gap-2"><label class="form-check mb-1"><input class="form-check-input" type="checkbox" data-field="required" @checked($field['required'])> Обязательно</label><button type="button" class="btn btn-outline-danger btn-sm legal-remove-field" title="Удалить"><i class="bi bi-trash"></i></button></div>
                                <div class="col-md-4"><label class="form-label small">Подсказка RU</label><input class="form-control form-control-sm" data-field="placeholder_ru" value="{{ $field['placeholder_ru'] }}"></div>
                                <div class="col-md-4"><label class="form-label small">Подсказка {{ strtoupper($nativeLocale) }}</label><input class="form-control form-control-sm" data-field="placeholder_native" value="{{ $field['placeholder_native'] }}"></div>
                                <div class="col-md-4"><label class="form-label small">Варианты для списка через запятую</label><input class="form-control form-control-sm" data-field="options" value="{{ $field['options'] }}" placeholder="Вернуть деньги, Заменить товар"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <textarea class="d-none" name="fields_json"></textarea>
            </div>
            <div class="col-12"><label><input type="checkbox" name="is_active" value="1" @checked(!$isEdit || $item->is_active)> Опубликован и доступен пользователям</label></div>
        @else
            <div class="col-12 d-flex gap-4"><label><input type="checkbox" name="emergency" value="1" @checked($isEdit && $item->emergency)> Срочный материал</label><label><input type="checkbox" name="is_published" value="1" @checked($isEdit && $item->is_published)> Опубликован</label></div>
        @endif
    </div>
    <button class="btn btn-primary mt-3">{{ $isEdit ? 'Сохранить' : 'Создать' }}</button>
</form>

@if($kind === 'letter')
<script>
(() => {
    const form = document.getElementById(@json($formId));
    if (!form || form.dataset.builderReady === '1') return;
    form.dataset.builderReady = '1';

    document.querySelectorAll('a[href*="section=letters"]').forEach(link => {
        link.innerHTML = '<i class="bi bi-file-earmark-text"></i>Юридическая помощь';
    });
    const pageTitle = document.querySelector('.admin-main header h1');
    if (pageTitle) pageTitle.textContent = 'Юридическая помощь';
    const info = document.querySelector('.admin-main .alert-info');
    if (info) info.innerHTML = '<strong>Конструктор юридических документов.</strong> Создайте текст с переменными и добавьте поля, которые пользователь должен заполнить.';

    const builder = form.querySelector('.legal-fields-builder');
    const hidden = form.querySelector('[name="fields_json"]');

    const rowHtml = () => `
        <div class="legal-field-row border rounded-3 p-3 bg-light">
            <div class="row g-2">
                <div class="col-md-3"><label class="form-label small">Подпись RU</label><input class="form-control form-control-sm" data-field="label_ru" required></div>
                <div class="col-md-3"><label class="form-label small">Подпись {{ strtoupper($nativeLocale) }}</label><input class="form-control form-control-sm" data-field="label_native"></div>
                <div class="col-md-2"><label class="form-label small">Системное имя</label><input class="form-control form-control-sm font-monospace" data-field="name" placeholder="field_name" required></div>
                <div class="col-md-2"><label class="form-label small">Тип</label><select class="form-select form-select-sm" data-field="type"><option value="text">Строка</option><option value="textarea">Большой текст</option><option value="email">Email</option><option value="tel">Телефон</option><option value="number">Число</option><option value="date">Дата</option><option value="select">Список</option></select></div>
                <div class="col-md-2 d-flex align-items-end justify-content-between gap-2"><label class="form-check mb-1"><input class="form-check-input" type="checkbox" data-field="required"> Обязательно</label><button type="button" class="btn btn-outline-danger btn-sm legal-remove-field"><i class="bi bi-trash"></i></button></div>
                <div class="col-md-4"><label class="form-label small">Подсказка RU</label><input class="form-control form-control-sm" data-field="placeholder_ru"></div>
                <div class="col-md-4"><label class="form-label small">Подсказка {{ strtoupper($nativeLocale) }}</label><input class="form-control form-control-sm" data-field="placeholder_native"></div>
                <div class="col-md-4"><label class="form-label small">Варианты для списка через запятую</label><input class="form-control form-control-sm" data-field="options"></div>
            </div>
        </div>`;

    form.querySelector('.legal-add-field')?.addEventListener('click', () => builder.insertAdjacentHTML('beforeend', rowHtml()));
    builder.addEventListener('click', event => {
        const button = event.target.closest('.legal-remove-field');
        if (!button) return;
        button.closest('.legal-field-row')?.remove();
    });

    form.addEventListener('submit', event => {
        const fields = [...builder.querySelectorAll('.legal-field-row')].map(row => {
            const value = key => row.querySelector(`[data-field="${key}"]`)?.value.trim() || '';
            const name = value('name').replace(/[^a-zA-Z0-9_]/g, '_').replace(/^_+|_+$/g, '');
            const type = value('type') || 'text';
            const field = {
                name,
                label: {ru: value('label_ru')},
                type,
                required: !!row.querySelector('[data-field="required"]')?.checked,
            };
            const nativeLabel = value('label_native');
            if (nativeLabel) field.label[@json($nativeLocale)] = nativeLabel;
            const placeholderRu = value('placeholder_ru');
            const placeholderNative = value('placeholder_native');
            if (placeholderRu || placeholderNative) {
                field.placeholder = {ru: placeholderRu};
                if (placeholderNative) field.placeholder[@json($nativeLocale)] = placeholderNative;
            }
            if (type === 'select') {
                field.options = value('options').split(',').map(item => item.trim()).filter(Boolean);
            }
            return field;
        }).filter(field => field.name && field.label.ru);

        const uniqueNames = new Set(fields.map(field => field.name));
        if (fields.length === 0) {
            event.preventDefault();
            alert('Добавьте хотя бы одно поле для заполнения.');
            return;
        }
        if (uniqueNames.size !== fields.length) {
            event.preventDefault();
            alert('Системные имена полей не должны повторяться.');
            return;
        }
        hidden.value = JSON.stringify(fields);
    });
})();
</script>
@endif
