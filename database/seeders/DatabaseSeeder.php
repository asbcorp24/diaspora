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
        $i18n = static fn (string $ru, ?string $uz = null): string => json_encode(
            ['ru' => $ru, 'uz' => $uz ?: $ru],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        $field = static function (
            string $name,
            string $label,
            string $type = 'text',
            bool $required = true,
            ?string $placeholder = null,
            array $options = []
        ): array {
            $result = [
                'name' => $name,
                'label' => ['ru' => $label, 'uz' => $label],
                'type' => $type,
                'required' => $required,
            ];
            if ($placeholder) {
                $result['placeholder'] = ['ru' => $placeholder, 'uz' => $placeholder];
            }
            if ($options !== []) {
                $result['options'] = $options;
            }
            return $result;
        };

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

        foreach (['localhost', '127.0.0.1', 'diaspora', 'diaspora.test', 'uz.diaspora.test'] as $domain) {
            DB::table('diaspora_domains')->updateOrInsert(
                ['domain' => $domain],
                [
                    'diaspora_id' => $diasporaId,
                    'is_primary' => $domain === 'diaspora',
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $templates = [
            [
                'slug' => 'salary-claim',
                'category' => 'employment',
                'title' => 'Требование о выплате задолженности по заработной плате',
                'description' => 'Письменное требование работодателю выплатить зарплату и предоставить расчётные документы.',
                'body' => "Кому: {{employer}}\nОт: {{full_name}}\nАдрес/контакт: {{contact}}\n\nТРЕБОВАНИЕ О ВЫПЛАТЕ ЗАРАБОТНОЙ ПЛАТЫ\n\nЯ, {{full_name}}, работал(а) у {{employer}} в должности {{position}}. За период {{period}} мне не выплачена заработная плата в размере {{amount}} руб.\n\nПрошу в срок до {{deadline}}:\n1. Выплатить задолженность по заработной плате.\n2. Предоставить расчётные листки и документы, подтверждающие начисления и удержания.\n3. Письменно сообщить о выполнении настоящего требования.\n\nДата: {{date}}\nПодпись: __________________ / {{full_name}} /",
                'fields' => [
                    $field('employer', 'Работодатель или организация'),
                    $field('full_name', 'ФИО заявителя'),
                    $field('contact', 'Адрес и контактные данные'),
                    $field('position', 'Должность'),
                    $field('period', 'Период задолженности'),
                    $field('amount', 'Сумма задолженности, руб.', 'number'),
                    $field('deadline', 'Срок исполнения', 'date'),
                    $field('date', 'Дата заявления', 'date'),
                ],
            ],
            [
                'slug' => 'employment-documents-request',
                'category' => 'employment',
                'title' => 'Запрос копий документов, связанных с работой',
                'description' => 'Запрос работодателю о выдаче договора, приказов, справок и расчётных документов.',
                'body' => "Кому: {{employer}}\nОт: {{full_name}}\nКонтакт: {{contact}}\n\nЗАЯВЛЕНИЕ О ВЫДАЧЕ ДОКУМЕНТОВ\n\nПрошу предоставить мне заверенные копии следующих документов, связанных с моей работой:\n{{documents}}\n\nДокументы прошу передать способом: {{delivery_method}}.\n\nДата: {{date}}\nПодпись: __________________ / {{full_name}} /",
                'fields' => [
                    $field('employer', 'Работодатель или организация'),
                    $field('full_name', 'ФИО заявителя'),
                    $field('contact', 'Контактные данные'),
                    $field('documents', 'Какие документы нужны', 'textarea', true, 'Например: трудовой договор, приказы, расчётные листки'),
                    $field('delivery_method', 'Способ получения', 'select', true, null, ['Лично', 'Почтой', 'По электронной почте']),
                    $field('date', 'Дата заявления', 'date'),
                ],
            ],
            [
                'slug' => 'registration-assistance-request',
                'category' => 'migration',
                'title' => 'Запрос содействия в оформлении миграционных документов',
                'description' => 'Обращение к работодателю или принимающей стороне о предоставлении документов и сведений для законного оформления.',
                'body' => "Кому: {{recipient}}\nОт: {{full_name}}\nДокумент/идентификатор: {{identity}}\nКонтакт: {{contact}}\n\nЗАЯВЛЕНИЕ\n\nПрошу предоставить документы и сведения, необходимые для законного оформления моего пребывания и/или трудовой деятельности:\n{{documents}}\n\nПрошу предоставить ответ и документы до {{deadline}}.\n\nДата: {{date}}\nПодпись: __________________ / {{full_name}} /",
                'fields' => [
                    $field('recipient', 'Работодатель или принимающая сторона'),
                    $field('full_name', 'ФИО заявителя'),
                    $field('identity', 'Номер документа или иной идентификатор', 'text', false),
                    $field('contact', 'Контактные данные'),
                    $field('documents', 'Какие документы или сведения требуются', 'textarea'),
                    $field('deadline', 'Желаемый срок ответа', 'date'),
                    $field('date', 'Дата заявления', 'date'),
                ],
            ],
            [
                'slug' => 'rental-deposit-return',
                'category' => 'housing',
                'title' => 'Требование о возврате обеспечительного платежа за жильё',
                'description' => 'Претензия арендодателю о возврате залога или депозита после прекращения аренды.',
                'body' => "Кому: {{landlord}}\nОт: {{full_name}}\nКонтакт: {{contact}}\n\nТРЕБОВАНИЕ О ВОЗВРАТЕ ОБЕСПЕЧИТЕЛЬНОГО ПЛАТЕЖА\n\nПо договору аренды от {{agreement_date}} я проживал(а) по адресу: {{property_address}}. При заключении договора был передан обеспечительный платёж в размере {{deposit_amount}} руб. Жильё освобождено {{move_out_date}}.\n\nПрошу вернуть обеспечительный платёж до {{deadline}} по следующим реквизитам:\n{{bank_details}}\n\nПри наличии удержаний прошу предоставить письменный расчёт и подтверждающие документы.\n\nДата: {{date}}\nПодпись: __________________ / {{full_name}} /",
                'fields' => [
                    $field('landlord', 'Арендодатель'),
                    $field('full_name', 'ФИО арендатора'),
                    $field('contact', 'Контактные данные'),
                    $field('agreement_date', 'Дата договора', 'date'),
                    $field('property_address', 'Адрес жилья'),
                    $field('deposit_amount', 'Размер залога, руб.', 'number'),
                    $field('move_out_date', 'Дата освобождения жилья', 'date'),
                    $field('deadline', 'Срок возврата', 'date'),
                    $field('bank_details', 'Реквизиты для возврата', 'textarea'),
                    $field('date', 'Дата требования', 'date'),
                ],
            ],
            [
                'slug' => 'housing-repair-demand',
                'category' => 'housing',
                'title' => 'Требование устранить недостатки арендуемого жилья',
                'description' => 'Письменное уведомление арендодателю о неисправностях и необходимости ремонта.',
                'body' => "Кому: {{landlord}}\nОт: {{full_name}}\nАдрес жилья: {{property_address}}\nКонтакт: {{contact}}\n\nУВЕДОМЛЕНИЕ О НЕДОСТАТКАХ ЖИЛЬЯ\n\nВ арендуемом помещении выявлены следующие недостатки:\n{{defects}}\n\nПрошу организовать осмотр и устранить указанные недостатки до {{deadline}} либо письменно сообщить порядок и сроки ремонта.\n\nДата: {{date}}\nПодпись: __________________ / {{full_name}} /",
                'fields' => [
                    $field('landlord', 'Арендодатель'),
                    $field('full_name', 'ФИО арендатора'),
                    $field('property_address', 'Адрес жилья'),
                    $field('contact', 'Контактные данные'),
                    $field('defects', 'Описание неисправностей', 'textarea'),
                    $field('deadline', 'Срок устранения', 'date'),
                    $field('date', 'Дата уведомления', 'date'),
                ],
            ],
            [
                'slug' => 'consumer-refund-claim',
                'category' => 'consumer',
                'title' => 'Претензия продавцу или исполнителю услуги',
                'description' => 'Претензия по некачественному товару, работе или услуге с выбранным требованием.',
                'body' => "Кому: {{seller}}\nОт: {{full_name}}\nКонтакт: {{contact}}\n\nПРЕТЕНЗИЯ\n\n{{purchase_date}} мной был приобретён товар/заказана услуга: {{product}} стоимостью {{price}} руб.\n\nНедостатки и обстоятельства:\n{{defect}}\n\nМоё требование: {{demand}}.\nПрошу исполнить требование до {{deadline}} и письменно сообщить о принятом решении.\n\nПриложения: {{attachments}}\n\nДата: {{date}}\nПодпись: __________________ / {{full_name}} /",
                'fields' => [
                    $field('seller', 'Продавец или исполнитель'),
                    $field('full_name', 'ФИО заявителя'),
                    $field('contact', 'Контактные данные'),
                    $field('purchase_date', 'Дата покупки или заказа', 'date'),
                    $field('product', 'Товар, работа или услуга'),
                    $field('price', 'Стоимость, руб.', 'number'),
                    $field('defect', 'Описание недостатка', 'textarea'),
                    $field('demand', 'Требование', 'select', true, null, ['Вернуть деньги', 'Заменить товар', 'Устранить недостатки', 'Уменьшить цену', 'Повторно выполнить работу']),
                    $field('deadline', 'Срок исполнения', 'date'),
                    $field('attachments', 'Приложения', 'textarea', false, 'Чек, договор, фотографии и другие документы'),
                    $field('date', 'Дата претензии', 'date'),
                ],
            ],
            [
                'slug' => 'government-appeal',
                'category' => 'government',
                'title' => 'Обращение в государственный орган',
                'description' => 'Универсальный шаблон заявления, жалобы или запроса в государственное учреждение.',
                'body' => "В {{authority}}\nОт: {{full_name}}\nАдрес: {{address}}\nКонтакт: {{contact}}\n\n{{appeal_type}}\n\nОбстоятельства:\n{{facts}}\n\nПрошу:\n{{request_text}}\n\nПриложения:\n{{attachments}}\n\nПрошу направить ответ способом: {{reply_method}}.\n\nДата: {{date}}\nПодпись: __________________ / {{full_name}} /",
                'fields' => [
                    $field('authority', 'Наименование органа или учреждения'),
                    $field('full_name', 'ФИО заявителя'),
                    $field('address', 'Почтовый адрес'),
                    $field('contact', 'Телефон или email'),
                    $field('appeal_type', 'Вид обращения', 'select', true, null, ['Заявление', 'Жалоба', 'Запрос информации', 'Ходатайство']),
                    $field('facts', 'Описание обстоятельств', 'textarea'),
                    $field('request_text', 'Что вы просите сделать', 'textarea'),
                    $field('attachments', 'Перечень приложений', 'textarea', false),
                    $field('reply_method', 'Способ получения ответа', 'select', true, null, ['Почтой', 'По электронной почте', 'Лично']),
                    $field('date', 'Дата обращения', 'date'),
                ],
            ],
            [
                'slug' => 'personal-data-consent-withdrawal',
                'category' => 'documents',
                'title' => 'Отзыв согласия на обработку персональных данных',
                'description' => 'Уведомление организации об отзыве ранее предоставленного согласия.',
                'body' => "Кому: {{organization}}\nОт: {{full_name}}\nКонтакт: {{contact}}\n\nОТЗЫВ СОГЛАСИЯ НА ОБРАБОТКУ ПЕРСОНАЛЬНЫХ ДАННЫХ\n\nЯ, {{full_name}}, отзываю согласие на обработку моих персональных данных, предоставленное {{consent_date}}, в части следующей цели обработки:\n{{processing_purpose}}\n\nПрошу прекратить обработку данных в пределах, допускаемых законом, и сообщить о выполненных действиях способом: {{reply_method}}.\n\nДата: {{date}}\nПодпись: __________________ / {{full_name}} /",
                'fields' => [
                    $field('organization', 'Организация'),
                    $field('full_name', 'ФИО заявителя'),
                    $field('contact', 'Контактные данные'),
                    $field('consent_date', 'Дата согласия', 'date', false),
                    $field('processing_purpose', 'Цель или вид обработки данных', 'textarea'),
                    $field('reply_method', 'Способ получения ответа', 'select', true, null, ['Почтой', 'По электронной почте', 'Лично']),
                    $field('date', 'Дата отзыва', 'date'),
                ],
            ],
        ];

        foreach ($templates as $template) {
            DB::table('letter_templates')->updateOrInsert(
                ['diaspora_id' => $diasporaId, 'slug' => $template['slug']],
                [
                    'category' => $template['category'],
                    'title' => $i18n($template['title']),
                    'description' => $i18n($template['description']),
                    'body_template' => $i18n($template['body']),
                    'fields' => json_encode($template['fields'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        DB::table('safety_articles')->updateOrInsert(
            ['diaspora_id' => $diasporaId, 'slug' => 'police-detention'],
            [
                'category' => 'detention',
                'title' => $i18n('Что делать при задержании полицией', 'Politsiya ushlaganda nima qilish kerak'),
                'summary' => $i18n(
                    'Право узнать причину задержания, получить переводчика, адвоката и уведомить близких.',
                    'Ushlash sababini bilish, tarjimon, advokat va yaqinlarga xabar berish huquqi.'
                ),
                'body' => $i18n(
                    'Сохраняйте спокойствие. Уточните основание задержания. Не подписывайте непонятные документы без переводчика. Попросите адвоката и возможность уведомить близкого человека или консульство. Не оказывайте сопротивления.',
                    'Tinchlikni saqlang. Ushlash sababini aniqlang. Tarjimonsiz tushunarsiz hujjatlarni imzolamang. Advokat va yaqin kishiga yoki konsullikka xabar berish imkonini so‘rang. Qarshilik ko‘rsatmang.'
                ),
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
