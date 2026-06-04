<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\LegalDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Resources\LegalDocumentResource;
use App\Models\LegalDocument;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class LegalDocumentController extends Controller
{
    public function privacyPolicy(): LegalDocumentResource|JsonResponse
    {
        return $this->show(LegalDocumentType::PrivacyPolicy);
    }

    public function termsOfService(): LegalDocumentResource|JsonResponse
    {
        return $this->show(LegalDocumentType::TermsOfService);
    }

    private function show(LegalDocumentType $type): LegalDocumentResource|JsonResponse
    {
        $document = LegalDocument::published()
            ->where('type', $type)
            ->first();

        if (! $document) {
            return response()->json(['message' => 'Not found.'], Response::HTTP_NOT_FOUND);
        }

        return new LegalDocumentResource($document);
    }
}
