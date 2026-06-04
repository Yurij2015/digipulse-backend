<?php

namespace App\Enums;

enum LegalDocumentType: string
{
    case PrivacyPolicy = 'privacy-policy';
    case TermsOfService = 'terms-of-service';

    public function label(): string
    {
        return match ($this) {
            self::PrivacyPolicy => 'Privacy Policy',
            self::TermsOfService => 'Terms of Service',
        };
    }
}
