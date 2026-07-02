<?php

namespace App\Service\Locale;

use App\Entity\Pays;
use App\Entity\User;

final class LocaleResolver
{
    /**
     * @param array<string, string> $countryLocales  pays code or libelle (uppercase) => locale
     * @param array<string, string> $regionLocales   region libelle (uppercase) => locale
     */
    public function __construct(
        private readonly array $countryLocales,
        private readonly array $regionLocales,
    ) {
    }

    public function resolveForUser(User $user): string
    {
        if ($user->getLocale()) {
            return SupportedLocales::normalize($user->getLocale());
        }

        return $this->resolveFromCountry($user->getPays());
    }

    public function resolveFromCountry(?Pays $pays): string
    {
        if (null === $pays) {
            return SupportedLocales::FR;
        }

        $code = strtoupper(trim((string) $pays->getCode()));
        if ($code !== '' && isset($this->countryLocales[$code])) {
            return SupportedLocales::normalize($this->countryLocales[$code]);
        }

        $libelle = strtoupper(trim((string) $pays->getLibelle()));
        if ($libelle !== '' && isset($this->countryLocales[$libelle])) {
            return SupportedLocales::normalize($this->countryLocales[$libelle]);
        }

        $regionLibelle = strtoupper(trim((string) $pays->getRegion()?->getLibelle()));
        if ($regionLibelle !== '' && isset($this->regionLocales[$regionLibelle])) {
            return SupportedLocales::normalize($this->regionLocales[$regionLibelle]);
        }

        return SupportedLocales::FR;
    }
}
