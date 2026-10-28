<?php

namespace App\Enums;

enum Role: string
{
    case Student = 'student';
    case Verifier = 'verifier';
    case SchoolStaff = 'school_staff';
    case SchoolAdmin = 'school_admin';
    case PlatformAdmin = 'platform_admin';
    case Owner = 'owner';

    public function tier(): int
    {
        return match ($this) {
            self::Owner => 1,self::PlatformAdmin => 2,self::SchoolAdmin => 3,self::SchoolStaff => 4,default => 99
        };
    }

    public function admin(): bool
    {
        return $this->tier() < 99;
    }

    public function label(): string
    {
        return __(match ($this) {
            self::Owner => 'Admin Utama',self::PlatformAdmin => 'Admin Platform',self::SchoolAdmin => 'Admin Sekolah / Kampus',self::SchoolStaff => 'Staf Admin Sekolah / Kampus',self::Verifier => 'Verifikator',default => 'Pelajar / Mahasiswa'
        });
    }
}
