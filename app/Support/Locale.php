<?php

namespace Vanguard\Support;

class Locale
{
    public const AVAILABLE_LOCALES = ['en', 'de', 'sr'];

    public static function flagUrl(string $locale): ?string
    {
        return match ($locale) {
            'en' => url('/flags/GB.png'),
            'de' => url('/flags/DE.png'),
            'sr' => url('/flags/RS.png'),
            default => null,
        };
    }

    public static function validateLocale(string $locale): bool
    {
        return in_array($locale, self::AVAILABLE_LOCALES);
    }
}
