<?php

namespace Database\Seeders;

use App\Enums\LegalDocumentType;
use App\Models\LegalDocument;
use Illuminate\Database\Seeder;

class LegalDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        LegalDocument::updateOrCreate(
            ['type' => LegalDocumentType::PrivacyPolicy],
            [
                'title' => [
                    'en' => 'Privacy Policy',
                    'uk' => 'Політика конфіденційності',
                    'pl' => 'Polityka prywatności',
                ],
                'content' => [
                    'en' => '<p>Edit this document in the admin panel under Legal Documents. Content is served via the public API at <code>/api/v1/legal/privacy-policy</code>.</p>',
                    'uk' => '<p>Редагуйте цей документ в адмін-панелі (Legal Documents). Контент доступний через API <code>/api/v1/legal/privacy-policy</code>.</p>',
                    'pl' => '<p>Edytuj ten dokument w panelu administracyjnym (Legal Documents). Treść jest dostępna przez API <code>/api/v1/legal/privacy-policy</code>.</p>',
                ],
                'published_at' => $now,
            ],
        );

        LegalDocument::updateOrCreate(
            ['type' => LegalDocumentType::TermsOfService],
            [
                'title' => [
                    'en' => 'Terms of Service',
                    'uk' => 'Умови використання',
                    'pl' => 'Regulamin',
                ],
                'content' => [
                    'en' => '<p>Edit this document in the admin panel under Legal Documents. Content is served via the public API at <code>/api/v1/legal/terms-of-service</code>.</p>',
                    'uk' => '<p>Редагуйте цей документ в адмін-панелі (Legal Documents). Контент доступний через API <code>/api/v1/legal/terms-of-service</code>.</p>',
                    'pl' => '<p>Edytuj ten dokument w panelu administracyjnym (Legal Documents). Treść jest dostępna przez API <code>/api/v1/legal/terms-of-service</code>.</p>',
                ],
                'published_at' => $now,
            ],
        );
    }
}
