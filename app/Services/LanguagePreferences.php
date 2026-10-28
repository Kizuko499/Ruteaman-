<?php
namespace App\Services;
final class LanguagePreferences
{
    public static function options(): array { return ['id'=>['label'=>'Bahasa Indonesia','direction'=>'ltr','carbon'=>'id']]; }
    public static function supported(): array { return ['id']; }
    public static function valid(mixed $locale): bool { return $locale === 'id'; }
    public static function direction(): string { return 'ltr'; }
    public static function carbonLocale(): string { return 'id'; }
    public static function dictionary(): array { return []; }
}
