<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminConditionTypeController;
use App\Http\Controllers\AdminConsoleController as C;
use App\Http\Controllers\AdminGuideController;
use App\Http\Controllers\AdminHomeGalleryController;
use App\Http\Controllers\AdminInvitationController as I;
use App\Http\Controllers\AdminPresentationController;
use App\Http\Controllers\AdminSchoolContactController;
use App\Http\Controllers\AdminSchoolDataController;
use App\Http\Controllers\AdminSchoolPickerController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ForcedPasswordController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\OwnerUserController;
use App\Http\Controllers\PublicMapController;
use App\Http\Controllers\ReportModerationController;
use Illuminate\Support\Facades\Route;

Route::get('/invite/{token}', [I::class, 'show'])->name('admin.invite.show')->middleware('throttle:30,1,invite-view:');
Route::post('/invite/{token}', [I::class, 'store'])->name('admin.invite.accept')->middleware('throttle:6,1,invite-accept:');
Route::prefix('admin/import')->name('admin.import')->middleware(['auth', 'active.account', 'role.min:1'])->group(function () {
 Route::get('/', [\App\Http\Controllers\AdminImportController::class, 'show'])->name('.show');
 Route::post('/preview', [\App\Http\Controllers\AdminImportController::class, 'preview'])->name('.preview');
 Route::post('/process', [\App\Http\Controllers\AdminImportController::class, 'process'])->name('.process');
 Route::get('/export', [\App\Http\Controllers\AdminImportController::class, 'export'])->name('.export');
});
Route::prefix('admin/suggestions')->name('admin.suggestions')->middleware(['auth', 'active.account', 'role.min:1'])->group(function () {
 Route::get('/', [\App\Http\Controllers\AdminSuggestionController::class, 'index'])->name('.index');
 Route::get('/export/csv', [\App\Http\Controllers\AdminSuggestionController::class, 'export'])->name('.export');
 Route::get('/{suggestion}', [\App\Http\Controllers\AdminSuggestionController::class, 'show'])->name('.show');
 Route::patch('/{suggestion}', [\App\Http\Controllers\AdminSuggestionController::class, 'update'])->name('.update');
 Route::post('/{suggestion}/approve', [\App\Http\Controllers\AdminSuggestionController::class, 'approve'])->name('.approve');
});
Route::prefix('admin')->name('admin.')->middleware(['auth', 'active.account', 'role.min:4'])->group(function () {
    Route::get('/', [C::class, 'index'])->name('index');
    Route::get('/reauth', [C::class, 'reauthForm'])->name('reauth');
    Route::post('/reauth', [C::class, 'reauth'])->middleware('throttle:5,1,admin-reauth:');
    Route::get('/roles', [C::class, 'roles'])->name('roles');
    Route::get('/audit', [AuditLogController::class, 'index'])->middleware('permission:audit.view')->name('audit');
    Route::get('/email', [\App\Http\Controllers\AdminEmailController::class, 'index'])->middleware('permission:email.monitor')->name('email.index');
    Route::post('/email/test', [\App\Http\Controllers\AdminEmailController::class, 'test'])->middleware(['permission:email.monitor', 'throttle:5,60,admin-email-test:'])->name('email.test');
    Route::post('/email/{log}/retry', [\App\Http\Controllers\AdminEmailController::class, 'retry'])->middleware(['permission:email.monitor', 'throttle:10,1,admin-email-retry:'])->name('email.retry');
    Route::get('/email/history', [\App\Http\Controllers\AdminEmailController::class, 'history'])->middleware('permission:email.monitor')->name('email.history');
    Route::get('/email/config', [\App\Http\Controllers\AdminEmailController::class, 'config'])->middleware('permission:email.monitor')->name('email.config');
    Route::get('/email/compose', [\App\Http\Controllers\AdminEmailController::class, 'compose'])->middleware('permission:email.manage')->name('email.compose');
    Route::post('/email/compose', [\App\Http\Controllers\AdminEmailController::class, 'sendBroadcast'])->middleware(['permission:email.manage', 'throttle:10,10,admin-email-broadcast:'])->name('email.compose.send');
    Route::get('/email/export', [\App\Http\Controllers\AdminEmailController::class, 'export'])->middleware('permission:email.manage')->name('email.export');
    Route::post('/email/smtp-test', [\App\Http\Controllers\AdminEmailController::class, 'smtpTest'])->middleware(['permission:email.manage', 'throttle:3,10,admin-email-smtp:'])->name('email.smtp-test');
    Route::post('/email/{log}/cancel', [\App\Http\Controllers\AdminEmailController::class, 'cancel'])->middleware(['permission:email.manage', 'throttle:10,1,admin-email-cancel:'])->whereNumber('log')->name('email.cancel');
    Route::get('/email/recipients', [\App\Http\Controllers\AdminNotificationRecipientController::class, 'index'])->middleware('permission:email.recipients')->name('email.recipients.index');
    Route::get('/email/recipients/create', [\App\Http\Controllers\AdminNotificationRecipientController::class, 'create'])->middleware('permission:email.recipients')->name('email.recipients.create');
    Route::post('/email/recipients', [\App\Http\Controllers\AdminNotificationRecipientController::class, 'store'])->middleware(['permission:email.recipients', 'throttle:20,1,admin-recipient-store:'])->name('email.recipients.store');
    Route::get('/email/recipients/{recipient}/edit', [\App\Http\Controllers\AdminNotificationRecipientController::class, 'edit'])->middleware('permission:email.recipients')->whereNumber('recipient')->name('email.recipients.edit');
    Route::patch('/email/recipients/{recipient}', [\App\Http\Controllers\AdminNotificationRecipientController::class, 'update'])->middleware('permission:email.recipients')->whereNumber('recipient')->name('email.recipients.update');
    Route::delete('/email/recipients/{recipient}', [\App\Http\Controllers\AdminNotificationRecipientController::class, 'destroy'])->middleware('permission:email.recipients')->whereNumber('recipient')->name('email.recipients.destroy');
    Route::post('/email/recipients/{recipient}/toggle', [\App\Http\Controllers\AdminNotificationRecipientController::class, 'toggle'])->middleware('permission:email.recipients')->whereNumber('recipient')->name('email.recipients.toggle');
    Route::post('/email/recipients/{recipient}/verify-resend', [\App\Http\Controllers\AdminNotificationRecipientController::class, 'resendVerification'])->middleware(['permission:email.recipients', 'throttle:3,60,admin-recipient-verify:'])->whereNumber('recipient')->name('email.recipients.verify-resend');
    Route::post('/email/recipients/{recipient}/test', [\App\Http\Controllers\AdminNotificationRecipientController::class, 'test'])->middleware(['permission:email.recipients', 'throttle:5,60,admin-recipient-test:'])->whereNumber('recipient')->name('email.recipients.test');
    Route::get('/email/{log}', [\App\Http\Controllers\AdminEmailController::class, 'show'])->middleware('permission:email.monitor')->whereNumber('log')->name('email.show');
    Route::get('/admins', [C::class, 'admins'])->middleware('permission:users.view')->name('admins');
    Route::get('/admins/create', [C::class, 'createAdmin'])->middleware('permission:users.manage_admins')->name('admins.create');
    Route::post('/admins', [C::class, 'storeAdmin'])->middleware(['permission:users.manage_admins', 'reauth', 'throttle:10,1,admin-create:'])->name('admins.store');
    Route::get('/admins/{user}', [C::class, 'showAdmin'])->middleware('permission:users.view')->name('admins.show');
    Route::patch('/admins/{user}', [C::class, 'updateAdmin'])->middleware(['permission:users.manage_admins', 'reauth'])->name('admins.update');
    Route::patch('/admins/{user}/permissions', [C::class, 'updateAdmin'])->middleware(['permission:users.manage_admins', 'reauth'])->name('admins.permissions');
    Route::patch('/admins/{user}/tier', [C::class, 'tier'])->middleware(['permission:users.manage_admins', 'reauth'])->name('admins.tier');
    foreach (['suspend', 'activate', 'revoke-sessions'] as $action) {
        Route::post('/admins/{user}/'.$action, [C::class, 'lifecycle'])->defaults('action', $action)->middleware(['permission:users.manage_admins', 'reauth'])->name('admins.'.$action);
    }
    Route::delete('/admins/{user}', [C::class, 'lifecycle'])->defaults('action', 'delete')->middleware(['permission:users.manage_admins', 'reauth'])->name('admins.delete');
    Route::post('/admins/{user}/invite', [C::class, 'issue'])->middleware(['permission:users.manage_admins', 'reauth', 'throttle:5,1,admin-link:'])->name('admins.invite');
    Route::post('/admins/{user}/reset-link', [C::class, 'issue'])->middleware(['permission:users.manage_admins', 'reauth', 'throttle:5,1,admin-link:'])->name('admins.reset');
    Route::get('/schools', [C::class, 'schools'])->middleware('permission:schools.view')->name('schools');
    Route::get('/schools/create', [C::class, 'createSchool'])->middleware('permission:schools.provision')->name('schools.create');
    Route::get('/schools/duplicates', [C::class, 'schoolDuplicates'])->middleware('permission:schools.view')->name('schools.duplicates');
    Route::get('/schools/search', [C::class, 'schoolSearch'])->middleware('permission:dashboard.view')->name('schools.search');
    Route::post('/schools', [C::class, 'storeSchool'])->middleware(['permission:schools.provision', 'reauth', 'throttle:10,1,school-provision:'])->name('schools.store');
    Route::post('/schools/{school}/account', [C::class, 'storeSchoolAccount'])->middleware(['permission:schools.provision', 'reauth', 'throttle:10,1,school-account:'])->name('schools.account.store');
    Route::get('/schools/{school}', [C::class, 'showSchool'])->middleware('permission:schools.view')->name('schools.show');
    Route::patch('/schools/{school}', [C::class, 'updateSchool'])->middleware(['permission:schools.manage', 'reauth'])->name('schools.update');
    foreach (['pause' => 'paused', 'restore' => 'active', 'archive' => 'archived'] as $action => $status) {
        Route::post('/schools/{school}/'.$action, [C::class, 'schoolStatus'])->defaults('school_status', $status)->middleware(['permission:schools.manage', 'reauth'])->name('schools.'.$action);
    }
    Route::post('/schools/{school}/delete', [C::class, 'schoolDelete'])->middleware(['permission:schools.manage', 'reauth'])->name('schools.delete');
    Route::get('/features', [C::class, 'features'])->middleware('permission:schools.features')->name('features');
    Route::patch('/schools/{school}/features', [C::class, 'updateFeatures'])->middleware(['permission:schools.features', 'reauth'])->name('schools.features');
    Route::patch('/features/general', [C::class, 'generalFeatures'])->middleware(['permission:schools.features', 'reauth', 'throttle:6,1,general-features:'])->name('features.general');
    Route::post('/features/apply-all', [C::class, 'allFeatures'])->middleware(['permission:schools.features', 'reauth'])->name('features.all');
    Route::get('/hazards', [C::class, 'hazards'])->middleware('permission:hazards.view')->name('hazards');
    Route::post('/hazards/{hazard}/reopen', [C::class, 'reopen'])->middleware(['permission:hazards.reopen', 'reauth'])->name('hazards.reopen');
});

Route::get('/api/integrations/hazards.geojson', [PublicMapController::class, 'geojson'])->middleware(['school.feature:geojson_feed', 'throttle:30,1,integration-feed:'])->name('integrations.geojson');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'active.account', 'role.min:4'])->group(function () {
    Route::get('/schools/{school}/export', [AdminSchoolDataController::class, 'export'])->middleware('permission:exports.run')->name('schools.export');
    Route::get('/schools/{school}/feed-settings', [AdminSchoolDataController::class, 'settings'])->middleware('permission:feed.manage')->name('schools.feed');
    Route::patch('/schools/{school}/feed-settings', [AdminSchoolDataController::class, 'update'])->middleware(['permission:feed.manage', 'reauth'])->name('schools.feed.update');
    Route::get('/schools/{school}/metrics', [AdminSchoolDataController::class, 'metrics'])->middleware('permission:metrics.view')->name('schools.metrics');
});

Route::prefix('admin/home-gallery')->name('admin.home-gallery')->middleware(['auth', 'active.account', 'role.min:1'])->group(function () {
    $gallery = AdminHomeGalleryController::class;
    Route::get('/', [$gallery, 'index']);
    Route::get('/photos/{photo}/preview', [$gallery, 'preview'])->whereUuid('photo')->name('.preview');
    Route::patch('/groups/{group}', [$gallery, 'settings'])->middleware('reauth')->name('.settings');
    Route::post('/groups/{group}/photos', [$gallery, 'upload'])->middleware(['reauth', 'throttle:20,1,gallery-upload:'])->name('.upload');
    Route::patch('/photos/{photo}', [$gallery, 'photo'])->whereUuid('photo')->middleware('reauth')->name('.photo');
    Route::delete('/photos/{photo}', [$gallery, 'delete'])->whereUuid('photo')->middleware('reauth')->name('.delete');
});

Route::prefix('admin/condition-types')->name('admin.condition-types')->middleware(['auth', 'active.account', 'role.min:1'])->group(function () {
    $types = AdminConditionTypeController::class;
    Route::get('/', [$types, 'index']);
    Route::post('/', [$types, 'store'])->middleware(['reauth', 'throttle:20,1,condition-types:'])->name('.store');
    Route::patch('/{type}', [$types, 'update'])->whereNumber('type')->middleware(['reauth', 'throttle:30,1,condition-types-update:'])->name('.update');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'active.account', 'role.min:4'])->group(function () {
    Route::get('/guide', AdminGuideController::class)->name('guide');
    $account = AccountController::class;
    foreach (['profile', 'settings', 'security', 'privacy'] as $section) {
        Route::get('/account/'.$section, [$account, 'show'])->defaults('section', $section)->name('account.'.$section);
    }
    Route::get('/account/notifications', [$account, 'notifications'])->name('account.notifications');
    Route::get('/school-contacts', [AdminSchoolContactController::class, 'index'])->name('school-contacts');
    Route::patch('/schools/{school}/help-contact', [AdminSchoolContactController::class, 'update'])->middleware(['reauth', 'throttle:20,1,school-contact:'])->name('school-contacts.update');
});
Route::prefix('admin/presentation')->name('admin.presentation')->middleware(['auth', 'active.account', 'role.min:1'])->group(function () {
    $site = AdminPresentationController::class;
    Route::get('/', [$site, 'index']);
    Route::post('/photo', [$site, 'photo'])->middleware(['reauth', 'throttle:10,1,login-photo:'])->name('.photo');
    Route::delete('/photo', [$site, 'reset'])->middleware('reauth')->name('.reset');
    Route::post('/register-photo', [$site, 'registerPhoto'])->middleware(['reauth', 'throttle:10,1,register-photo:'])->name('.register-photo');
    Route::delete('/register-photo', [$site, 'resetRegister'])->middleware('reauth')->name('.register-photo.reset');
    Route::patch('/contact', [$site, 'contact'])->middleware('reauth')->name('.contact');
});

Route::middleware(['auth', 'active.account'])->group(function () {
    Route::get('/password/required', [ForcedPasswordController::class, 'show'])->name('password.force.show');
    Route::post('/password/required', [ForcedPasswordController::class, 'update'])->middleware('throttle:5,1,password-force:')->name('password.force.update');
});
Route::prefix('admin/users')->name('admin.users.')->middleware(['auth', 'active.account', 'role.min:1'])->group(function () {
    $c = OwnerUserController::class;
    Route::get('/', [$c, 'index'])->name('index');
    Route::get('/create', [$c, 'create'])->name('create');
    Route::post('/generate-password', [$c, 'generated'])->middleware(['reauth', 'throttle:10,1,password-generate:'])->name('generate-password');
    Route::post('/', [$c, 'store'])->middleware(['reauth', 'throttle:10,1,owner-users-create:'])->name('store');
    Route::get('/{user}', [$c, 'show'])->whereNumber('user')->name('show');
    Route::patch('/{user}', [$c, 'update'])->middleware(['reauth', 'throttle:20,1,owner-users-edit:'])->name('update');
    Route::patch('/{user}/permissions', [$c, 'permissions'])->middleware(['reauth', 'throttle:20,1,owner-users-permissions:'])->name('permissions');
    Route::patch('/{user}/role', [$c, 'role'])->middleware(['reauth', 'throttle:10,1,owner-users-role:'])->name('role');
    Route::put('/{user}/password', [$c, 'password'])->middleware(['reauth', 'throttle:5,1,owner-password-set:'])->name('password');
    Route::post('/{user}/reset-link', [$c, 'reset'])->middleware(['reauth', 'throttle:5,1,owner-reset:'])->name('reset');
    foreach (['revoke-sessions', 'suspend', 'activate'] as $action) {
        Route::post('/{user}/'.$action, [$c, 'lifecycle'])->defaults('action', $action)->middleware(['reauth', 'throttle:10,1,owner-users-state:'])->name($action);
    }
    Route::delete('/{user}/confirm-delete', [$c, 'quickDestroy'])->middleware('throttle:5,1,owner-users-quick-delete:')->name('quick-destroy');
    Route::delete('/{user}', [$c, 'destroy'])->middleware(['reauth', 'throttle:5,1,owner-users-delete:'])->name('destroy');
});

Route::prefix('admin/school-picker')->name('admin.school-picker.')->middleware(['auth', 'active.account', 'role.min:1'])->group(function () {
    Route::get('/search', [AdminSchoolPickerController::class, 'search'])->middleware('throttle:30,1,owner-school-search:')->name('search');
    Route::post('/register', [AdminSchoolPickerController::class, 'register'])->middleware(['reauth', 'throttle:10,1,owner-school-register:'])->name('register');
});

Route::post('/admin/hazards/{hazard}/moderation/{action}', [ReportModerationController::class, 'update'])->where('action', 'suspend|remove|restore')->middleware(['auth', 'active.account', 'role.min:4', 'reauth', 'throttle:20,1,report-moderation:'])->name('admin.hazards.moderation');

Route::post('/language', [LanguageController::class, 'update'])->middleware('throttle:60,1')->name('language.update');

Route::prefix('admin')->name('admin.')->middleware(['auth','active.account','role.min:4'])->group(function(){
 Route::get('/campus-catalog/{catalog}/connect',[\App\Http\Controllers\AdminCampusCatalogLinkController::class,'create'])->middleware('permission:schools.provision')->whereNumber('catalog')->name('campus-catalog.connect');
 Route::post('/campus-catalog/{catalog}/connect',[\App\Http\Controllers\AdminCampusCatalogLinkController::class,'store'])->middleware(['permission:schools.provision','reauth','throttle:10,1,campus-catalog-link:'])->whereNumber('catalog')->name('campus-catalog.connect.store');
});
