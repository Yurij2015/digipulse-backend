<?php

use App\Enums\LegalDocumentType;
use App\Models\LegalDocument;
use Database\Seeders\LegalDocumentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->frontendKey = config('app.frontend_key');
});

test('legal api returns published privacy policy', function () {
    $this->seed(LegalDocumentSeeder::class);

    $this->getJson(route('v1.legal.privacy-policy'), ['X-Frontend-Key' => $this->frontendKey])
        ->assertOk()
        ->assertJsonPath('data.type', 'privacy-policy')
        ->assertJsonStructure([
            'data' => ['type', 'title', 'content', 'published_at', 'updated_at'],
        ])
        ->assertJsonPath('data.title.en', 'Privacy Policy');
});

test('legal api returns published terms of service', function () {
    $this->seed(LegalDocumentSeeder::class);

    $this->getJson(route('v1.legal.terms-of-service'), ['X-Frontend-Key' => $this->frontendKey])
        ->assertOk()
        ->assertJsonPath('data.type', 'terms-of-service')
        ->assertJsonPath('data.title.en', 'Terms of Service');
});

test('legal api returns 404 for unpublished document', function () {
    LegalDocument::create([
        'type' => LegalDocumentType::PrivacyPolicy,
        'title' => ['en' => 'Privacy Policy'],
        'content' => ['en' => '<p>Draft</p>'],
        'published_at' => null,
    ]);

    $this->getJson(route('v1.legal.privacy-policy'), ['X-Frontend-Key' => $this->frontendKey])
        ->assertNotFound();
});

test('legal api requires frontend key', function () {
    $this->seed(LegalDocumentSeeder::class);

    $this->getJson(route('v1.legal.privacy-policy'))
        ->assertUnauthorized();
});
