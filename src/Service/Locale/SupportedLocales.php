<?php

namespace App\Service\Locale;

final class SupportedLocales
{
    public const FR = 'fr';
    public const EN = 'en';
    public const AR = 'ar';

    /** Locales available in phase 1 UI */
    public const ACTIVE = [self::FR, self::EN];

    public const ALL = [self::FR, self::EN, self::AR];

    public static function normalize(?string $locale): string
    {
        if (\in_array($locale, self::ACTIVE, true)) {
            return $locale;
        }

        // Arabic reserved for a later phase — fallback to French for now
        if ($locale === self::AR) {
            return self::FR;
        }

        return self::FR;
    }

    /**
     * @return array<string, string> code => label key
     */
    public static function activeChoices(): array
    {
        return [
            'locale.fr' => self::FR,
            'locale.en' => self::EN,
        ];
    }
}
