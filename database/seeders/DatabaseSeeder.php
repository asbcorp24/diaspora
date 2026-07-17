<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('diasporas')->updateOrInsert(
            ['code' => 'uz'],
            [
                'name' => 'Узбекская диаспора',
                'native_name' => 'O‘zbek diasporasi',
                'default_locale' => 'uz',
                'supported_locales' => json_encode(['ru', 'uz'], JSON_UNESCAPED_UNICODE),
                'theme' => json_encode(['primary' => '#167B5A', 'secondary' => '#2C5DAA', 'accent' => '#E7B64A']),
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $diasporaId = DB::table('diasporas')->where('code', 'uz')->value('id');

        foreach (['localhost', '127.0.0.1', 'diaspora.test', 'uz.diaspora.test'] as $index => $domain) {
            DB::table('diaspora_domains')->updateOrInsert(
                ['domain' => $domain],
                [
                    'diaspora_id' => $diasporaId,
                    'is_primary' => $index === 2,
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        DB::table('letter_templates')->updateOrInsert(
            ['diaspora_id' => $diasporaId, 'slug' => 'salary-claim'],
            [
                'category' => 'employment',
                'title' => json_encode(['ru' => 'Требование о выплате зарплаты', 'uz' => 'Ish haqini to‘lash talabi'], JSON_UNESCAPED_UNICODE),
                'description' => json_encode([
                    'ru' => 'Письменное требование работодателю о выплате задолженности.',
                    'uz' => 'Ish beruvchiga ish haqi qarzini to‘lash to‘g‘risidagi yozma talab.',
                ], JSON_UNESCAPED_UNICODE),
                'body_template' => json_encode([
                    'ru' => "Кому: {{employer}}\nОт: {{full_name}}\n\nТРЕБОВАНИЕ\n\nЯ работал(а) в организации {{employer}} в должности {{position}}. За период {{period}} мне не выплачена заработная плата в размере {{amount}} рублей.\n\nПрошу выплатить задолженность и выдать расчётные документы.\n\nДата: {{date}}\nПодпись: ____________",
                    'uz' => "Kimga: {{employer}}\nKimdan: {{full_name}}\n\nTALAB\n\nMen {{employer}} tashkilotida {{position}} lavozimida ishladim. {{period}} davri uchun {{amount}} rubl miqdoridagi ish haqi to‘lanmadi.\n\nQarzdorlikni to‘lashni va hisob-kitob hujjatlarini berishni so‘rayman.\n\nSana: {{date}}\nImzo: ____________",
                ], JSON_UNESCAPED_UNICODE),
                'fields' => json_encode([
                    ['name' => 'employer', 'label' => ['ru' => 'Работодатель', 'uz' => 'Ish beruvchi'], 'required' => true],
                    ['name' => 'full_name', 'label' => ['ru' => 'ФИО', 'uz' => 'F.I.Sh.'], 'required' => true],
                    ['name' => 'position', 'label' => ['ru' => 'Должность', 'uz' => 'Lavozim'], 'required' => true],
                    ['name' => 'period', 'label' => ['ru' => 'Период задолженности', 'uz' => 'Qarzdorlik davri'], 'required' => true],
                    ['name' => 'amount', 'label' => ['ru' => 'Сумма', 'uz' => 'Miqdor'], 'required' => true],
                    ['name' => 'date', 'label' => ['ru' => 'Дата', 'uz' => 'Sana'], 'required' => true],
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        DB::table('safety_articles')->updateOrInsert(
            ['diaspora_id' => $diasporaId, 'slug' => 'police-detention'],
            [
                'category' => 'detention',
                'title' => json_encode(['ru' => 'Что делать при задержании полицией', 'uz' => 'Politsiya ushlaganda nima qilish kerak'], JSON_UNESCAPED_UNICODE),
                'summary' => json_encode([
                    'ru' => 'Право узнать причину задержания, получить переводчика, адвоката и уведомить близких.',
                    'uz' => 'Ushlash sababini bilish, tarjimon, advokat va yaqinlarga xabar berish huquqi.',
                ], JSON_UNESCAPED_UNICODE),
                'body' => json_encode([
                    'ru' => 'Сохраняйте спокойствие. Уточните основание задержания. Не подписывайте непонятные документы без переводчика. Попросите адвоката и возможность уведомить близкого человека или консульство. Не оказывайте сопротивления.',
                    'uz' => 'Tinchlikni saqlang. Ushlash sababini aniqlang. Tarjimonsiz tushunarsiz hujjatlarni imzolamang. Advokat va yaqin kishiga yoki konsullikka xabar berish imkonini so‘rang. Qarshilik ko‘rsatmang.',
                ], JSON_UNESCAPED_UNICODE),
                'emergency' => true,
                'is_published' => true,
                'published_at' => $now,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        if (env('ADMIN_EMAIL') && env('ADMIN_PASSWORD')) {
            DB::table('users')->updateOrInsert(
                ['email' => env('ADMIN_EMAIL')],
                [
                    'diaspora_id' => $diasporaId,
                    'name' => 'Administrator',
                    'phone' => env('ADMIN_PHONE', '+00000000000'),
                    'password' => Hash::make(env('ADMIN_PASSWORD')),
                    'role' => 'superadmin',
                    'status' => 'active',
                    'preferred_locale' => 'ru',
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}
