<?php

return [
    'admin_tiers' => ['owner', 'school_admin'],
    'password_min' => 12, 'password_uncompromised' => (bool) env('ADMIN_PASSWORD_UNCOMPROMISED', false),
    'alias_domain' => env('ALIAS_EMAIL_DOMAIN', 'ruteaman.my.id'), 'owner_max' => (int) env('OWNER_MAX_COUNT', 3),
    'invite_hours' => (int) env('INVITE_EXPIRES_HOURS', 48), 'reauth_minutes' => 10, 'idle_minutes' => (int) env('ADMIN_IDLE_MINUTES', 20),
    'reserved' => ['admin', 'owner', 'root', 'support', 'ruteaman', 'postmaster', 'abuse', 'security', 'system'],
    'features' => ['reporting' => true, 'require_guest_review' => false, 'require_account_review' => false, 'confirmation' => true, 'verification' => true, 'escalation' => true, 'geojson_feed' => false, 'cross_school_public_reports' => true, 'shared_layer' => true, 'trend' => false, 'avatar_upload' => false],
    'feature_labels' => ['reporting' => 'Pelaporan', 'require_guest_review' => 'Wajib verifikasi laporan tamu', 'require_account_review' => 'Wajib verifikasi laporan pengguna login', 'confirmation' => 'Konfirmasi', 'verification' => 'Verifikasi', 'escalation' => 'Eskalasi', 'geojson_feed' => 'Feed GeoJSON', 'cross_school_public_reports' => 'Laporan publik lintas sekolah / kampus di peta dan rute', 'shared_layer' => 'Layer bersama dalam wilayah cakupan', 'trend' => 'Tren', 'avatar_upload' => 'Unggah avatar'],
];
