<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LegalHelpController extends Controller
{
    public function index(): View
    {
        $templates = DB::table('letter_templates')
            ->where('diaspora_id', app('currentDiaspora')->id)
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('id')
            ->get();

        return view('platform', [
            'page' => 'letters',
            'templates' => $templates,
        ]);
    }

    public function preview(Request $request, string $slug): View
    {
        $template = DB::table('letter_templates')
            ->where('diaspora_id', app('currentDiaspora')->id)
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        abort_unless($template, 404);

        $fields = collect(json_decode($template->fields, true) ?: [])
            ->filter(fn ($field) => is_array($field) && !empty($field['name']))
            ->values();

        $rules = [];
        foreach ($fields as $field) {
            $name = (string) $field['name'];
            $type = in_array($field['type'] ?? 'text', ['text', 'textarea', 'email', 'tel', 'number', 'date', 'select'], true)
                ? $field['type']
                : 'text';
            $fieldRules = [!empty($field['required']) ? 'required' : 'nullable'];

            if ($type === 'email') {
                $fieldRules[] = 'email';
                $fieldRules[] = 'max:190';
            } elseif ($type === 'number') {
                $fieldRules[] = 'numeric';
            } elseif ($type === 'date') {
                $fieldRules[] = 'date';
            } else {
                $fieldRules[] = 'string';
                $fieldRules[] = 'max:5000';
            }

            if ($type === 'select') {
                $options = $this->optionValues($field['options'] ?? []);
                if ($options !== []) {
                    $fieldRules[] = Rule::in($options);
                }
            }

            $rules[$name] = $fieldRules;
        }

        $data = $request->validate($rules);
        $bodies = json_decode($template->body_template, true) ?: [];
        $body = $bodies[app()->getLocale()] ?? $bodies['ru'] ?? '';

        foreach ($fields as $field) {
            $name = (string) $field['name'];
            $value = $data[$name] ?? '';
            $body = str_replace('{{'.$name.'}}', e((string) $value), $body);
        }

        return view('platform', [
            'page' => 'letter_preview',
            'template' => $template,
            'body' => $body,
        ]);
    }

    private function optionValues(mixed $options): array
    {
        if (!is_array($options)) {
            return [];
        }

        return collect($options)
            ->map(function ($option): ?string {
                if (is_string($option)) {
                    return $option;
                }
                if (is_array($option)) {
                    return isset($option['value']) ? (string) $option['value'] : null;
                }
                return null;
            })
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->values()
            ->all();
    }
}
