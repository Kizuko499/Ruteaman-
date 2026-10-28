<?php
use App\Http\Controllers\{AuthController,ConfirmationController,HazardController,MyReportController,PublicMapController,RouteComparisonController,SavedRouteController,SchoolDashboardController,SchoolDirectoryController,VerificationController};
use Illuminate\Support\Facades\Route;

Route::get('/',[PublicMapController::class,'landing'])->name('home');
Route::get('/fragments/institution-options',[PublicMapController::class,'institutionOptions'])->name('fragments.institution-options');
Route::get('/panduan',\App\Http\Controllers\UserGuideController::class)->name('guide.public');
Route::get('/api/home-gallery',[\App\Http\Controllers\HomeGalleryController::class,'feed'])->middleware('throttle:120,1,home-gallery:')->name('home-gallery.feed');
Route::get('/media/home-gallery/{key}/{token}',[\App\Http\Controllers\HomeGalleryController::class,'media'])->where('key','routes|reporting|followup')->where('token','(admin-[a-f0-9-]{36}|report-[0-9]+)')->middleware('throttle:600,1,home-gallery-media:')->name('home-gallery.media');
Route::get('/map',[PublicMapController::class,'map'])->name('map');
$guestReports=\App\Http\Controllers\GuestReportController::class;
Route::get('/lapor',[$guestReports,'create'])->name('guest-reports.create');
Route::post('/lapor',[$guestReports,'store'])->middleware('throttle:guest-reports')->name('guest-reports.store');
Route::get('/lapor/terkirim',[$guestReports,'receipt'])->name('guest-reports.receipt');
Route::get('/h/{hazard:public_slug}',function(\App\Models\Hazard $hazard){abort_unless(app(\App\Policies\HazardPublicPolicy::class)->view(auth()->user(),$hazard),404);return to_route('conditions.show',$hazard,301)->header('Cache-Control','private, no-store');})->name('hazards.public');
$c=\App\Http\Controllers\ConditionController::class;
Route::get('/kondisi',[$c,'index'])->name('conditions.index');
Route::get('/kondisi/{hazard:public_slug}',[$c,'show'])->name('conditions.show');
Route::post('/kondisi/{hazard:public_slug}/komentar',[\App\Http\Controllers\HazardCommentController::class,'store'])->middleware('throttle:10,1,hazard-comment:')->name('conditions.comments.store');
Route::delete('/kondisi/{hazard:public_slug}/komentar/{comment}',[\App\Http\Controllers\HazardCommentController::class,'destroy'])->middleware('auth')->name('conditions.comments.destroy');
Route::get('/api/conditions',[$c,'api'])->middleware('throttle:60,1,conditions:')->name('conditions.api');
Route::get('/api/conditions/{hazard:public_slug}',[$c,'refresh'])->middleware('throttle:60,1,condition-refresh:');
Route::get('/api/conditions/{hazard:public_slug}/related',[$c,'related'])->middleware('throttle:60,1,condition-related:');
Route::get('/api/hazards/featured',\App\Http\Controllers\FeaturedHazardController::class)->middleware('throttle:60,1,featured:')->name('conditions.featured');
Route::get('/api/hazards/area',\App\Http\Controllers\AreaHazardController::class)->middleware('throttle:60,1,area:')->name('conditions.area');
Route::get('/media/h/{hazard:public_slug}/{index}',[\App\Http\Controllers\ConditionMediaController::class,'photo'])->whereNumber('index')->middleware('throttle:120,1,condition-media:')->name('conditions.media');
Route::get('/media/og/{hazard:public_slug}',[\App\Http\Controllers\ConditionMediaController::class,'og'])->middleware('throttle:60,1,condition-og:')->name('conditions.og');
Route::post('/api/routes/compare',RouteComparisonController::class)->middleware('throttle:30,1,route-compare-v2:')->name('routes.compare');
Route::get('/api/feed/hazards.geojson',[PublicMapController::class,'geojson'])->middleware('throttle:60,1,hazard-feed-v2:')->name('hazards.geojson');
Route::get('/api/routes/saved',SavedRouteController::class)->middleware('throttle:60,1,saved-routes-v2:')->name('routes.saved');
Route::get('/api/status',[PublicMapController::class,'status'])->name('api.status');
Route::post('/api/suggestions',[\App\Http\Controllers\SuggestionController::class,'store'])->middleware('throttle:10,1')->name('suggestions.store');
Route::get('/api/suggestions/check',[\App\Http\Controllers\SuggestionController::class,'check'])->middleware('throttle:20,1')->name('suggestions.check');
Route::get('/api/schools',[PublicMapController::class,'schools'])->middleware('throttle:60,1,schools-index-v2:')->name('schools.index');
Route::get('/api/schools/search',[PublicMapController::class,'schoolSearch'])->middleware('throttle:60,1,schools-search:')->name('schools.search');
Route::prefix('/api/school-directory')->middleware('throttle:60,1,school-directory-v2:')->group(function(){
 Route::get('/provinces',[SchoolDirectoryController::class,'provinces'])->name('school-directory.provinces');
 Route::get('/regencies',[SchoolDirectoryController::class,'regencies'])->name('school-directory.regencies');
 Route::get('/districts',[SchoolDirectoryController::class,'districts'])->name('school-directory.districts');
 Route::get('/schools',[SchoolDirectoryController::class,'schools'])->name('school-directory.schools');
 Route::get('/detail',[SchoolDirectoryController::class,'detail'])->name('school-directory.detail');
 Route::get('/search-nationwide',[SchoolDirectoryController::class,'searchNationwide'])->name('school-directory.search-nationwide');
 Route::get('/resolve-coordinates',[SchoolDirectoryController::class,'resolveCoordinates'])->name('school-directory.resolve-coordinates');
 Route::post('/provision',[SchoolDirectoryController::class,'provision'])->middleware('throttle:10,1')->name('school-directory.provision');
});
Route::middleware('guest')->group(function(){
 Route::get('/login',[AuthController::class,'loginForm'])->name('login');Route::post('/login',[AuthController::class,'login'])->middleware('throttle:6,1,auth-login-v2:');
 Route::get('/register',[AuthController::class,'registerForm'])->name('register');Route::post('/register',[AuthController::class,'register'])->middleware('throttle:6,1,auth-register-v2:');
 Route::get('/forgot-password',[AuthController::class,'forgotForm'])->name('password.request');Route::post('/forgot-password',[AuthController::class,'forgot'])->name('password.email')->middleware('throttle:3,1,auth-forgot-v2:');
 Route::get('/reset-password/{token}',[AuthController::class,'resetForm'])->name('password.reset');Route::post('/reset-password',[AuthController::class,'reset'])->name('password.update');
});
Route::middleware(['auth','active.account'])->group(function(){
 Route::post('/logout',[AuthController::class,'logout'])->name('logout');
 Route::get('/api/hazards/duplicate',[HazardController::class,'duplicate'])->middleware('throttle:60,1,hazard-duplicate-v2:')->name('hazards.duplicate');
 Route::get('/hazards/create',[HazardController::class,'create'])->middleware('school.feature:reporting')->name('hazards.create');Route::post('/hazards',[HazardController::class,'store'])->middleware('throttle:10,1,hazard-store-v2:')->middleware('school.feature:reporting')->name('hazards.store');
 Route::post('/hazards/{hazard}/confirmations',ConfirmationController::class)->middleware('throttle:20,1,hazard-confirm-v2:')->middleware('school.feature:confirmation')->name('hazards.confirm');Route::get('/my/reports',MyReportController::class)->name('reports.mine');
 Route::get('/verifier/queue',[VerificationController::class,'index'])->middleware('permission:verification.decide')->name('verifier.queue');Route::post('/hazards/{hazard}/verifications',[VerificationController::class,'store'])->middleware('permission:verification.decide')->name('hazards.verify');
 Route::middleware('permission:escalations.manage')->group(function(){Route::post('/hazards/{hazard}/escalations',[SchoolDashboardController::class,'escalate'])->name('hazards.escalate');Route::patch('/escalations/{escalation}',[SchoolDashboardController::class,'updateEscalation'])->name('escalations.update');Route::get('/hazards/{hazard}/escalation-package',[SchoolDashboardController::class,'package'])->name('hazards.package');});
});

// Account routes never expose a public avatar URL.
Route::view('/help','pages.help')->name('help');
Route::view('/privacy-policy','pages.privacy-policy')->name('privacy.policy');
Route::view('/terms','pages.terms')->name('terms');
Route::middleware(['auth','active.account','auth.session'])->group(function(){
 Route::get('/account/guide',\App\Http\Controllers\UserGuideController::class)->name('account.guide');
 $c=\App\Http\Controllers\AccountController::class;
 Route::get('/api/account',[$c,'api'])->name('account.api');
 foreach(['profile','settings','security','privacy'] as $section)Route::get('/account/'.$section,[$c,'show'])->defaults('section',$section)->name('account.'.$section);
 Route::patch('/account/profile',[$c,'profile'])->middleware('throttle:15,1,account-profile:')->name('account.profile.update');
 Route::post('/account/avatar',[$c,'avatar'])->middleware('throttle:5,60,account-avatar:')->middleware('school.feature:avatar_upload')->name('account.avatar.store');
 Route::patch('/account/avatar-preset',[$c,'preset'])->middleware('throttle:15,1,account-preset:')->name('account.avatar.preset');
 Route::delete('/account/avatar',[$c,'removeAvatar'])->name('account.avatar.destroy');
 Route::get('/account/avatar/{user}',[$c,'avatarImage'])->name('account.avatar.show');
 Route::patch('/account/email',[$c,'email'])->middleware('throttle:3,60,account-email:')->name('account.email.update');
 Route::post('/email/verification-notification',[$c,'resend'])->middleware('throttle:3,60,account-verify:')->name('account.email.resend');
 Route::get('/account/verify-email/{user}/{token}',[$c,'verify'])->middleware('signed')->name('account.email.verify');
 Route::patch('/account/preferences',[$c,'preferences'])->name('account.preferences');
 Route::put('/account/password',[$c,'password'])->middleware('throttle:5,60,account-password:')->name('account.password');
 Route::get('/account/sessions',[$c,'sessions'])->name('account.sessions');
 Route::delete('/account/sessions',[$c,'logoutSessions'])->middleware('throttle:5,60,account-sessions:')->name('account.sessions.destroy');
 Route::get('/account/notifications',[$c,'notifications'])->name('account.notifications');
 Route::post('/account/notifications/seen',[$c,'seen'])->name('account.notifications.seen');
 Route::get('/account/export',[$c,'export'])->middleware('throttle:5,60,account-export:')->name('account.export');
 Route::delete('/account',[$c,'destroy'])->middleware('throttle:3,60,account-delete:')->name('account.destroy');
 Route::middleware('permission:users.view')->group(function(){
  Route::get('/school/users',[\App\Http\Controllers\SchoolUserController::class,'index'])->name('school.users');
  Route::patch('/school/users/{user}',[\App\Http\Controllers\SchoolUserController::class,'update'])->name('school.users.update');
 });
});
Route::get('/school/dashboard',[SchoolDashboardController::class,'index'])->middleware(['auth','active.account','permission:dashboard.view'])->name('school.dashboard');

Route::get('/admin/magic-login/{token}', [\App\Http\Controllers\MagicLoginController::class, 'login'])->name('admin.magic-login')->middleware('throttle:10,1');
Route::get('/notification-recipients/verify/{recipient}', \App\Http\Controllers\NotificationRecipientVerificationController::class)->middleware(['signed', 'throttle:10,1'])->name('notification-recipients.verify');
require __DIR__.'/admin.php';

Route::get('/school/metrics',[SchoolDashboardController::class,'metrics'])->middleware(['auth','active.account','permission:metrics.view'])->name('school.metrics');

Route::get('/api/map/conditions',[\App\Http\Controllers\MapConditionController::class,'index'])->middleware('throttle:120,1,map-conditions:')->name('map.conditions');

Route::get('/media/condition-types/{type}',[\App\Http\Controllers\AdminConditionTypeController::class,'icon'])->whereNumber('type')->middleware('throttle:600,1,condition-icons:')->name('condition-types.icon');

Route::get('/media/site/login-photo',[\App\Http\Controllers\AdminPresentationController::class,'publicPhoto'])->middleware('throttle:120,1,site-photo:')->name('site.login-photo');
Route::get('/media/site/register-photo',[\App\Http\Controllers\AdminPresentationController::class,'publicRegisterPhoto'])->middleware('throttle:120,1,site-photo:')->name('site.register-photo');

Route::get('/api/campus-directory',\App\Http\Controllers\CampusDirectoryController::class)->middleware('throttle:30,1,campus-directory:')->name('campus-directory.search');

Route::get('/api/register-campus-search',[\App\Http\Controllers\RegisterCampusController::class,'search'])->middleware('throttle:30,1,register-campus:')->name('register-campus.search');
Route::post('/api/register-campus-provision',[\App\Http\Controllers\RegisterCampusController::class,'provision'])->middleware('throttle:10,1,register-campus-provision:')->name('register-campus.provision');

Route::get('/kampus', [\App\Http\Controllers\CampusCatalogController::class,'index'])->middleware('throttle:60,1,campus-catalog:')->name('campus-catalog.index');
Route::get('/api/campus-catalog', [\App\Http\Controllers\CampusCatalogController::class,'index'])->middleware('throttle:60,1,campus-catalog-api:')->name('campus-catalog.api');

// Separate endpoint cannot silently fall back to national school search.
Route::get('/api/reporting-campuses', [\App\Http\Controllers\GuestReportController::class, 'campuses'])->middleware('throttle:60,1')->name('guest-reports.campuses');
