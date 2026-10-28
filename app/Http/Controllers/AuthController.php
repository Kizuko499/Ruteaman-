<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\AuditLogger;
use App\Services\DapodikSchoolDirectory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AuthController extends Controller
{
    public function loginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (! \App\Services\TurnstileService::verify($request->input('cf-turnstile-response'), $request->ip())) { return back()->withErrors(['email' => 'Verifikasi keamanan gagal. Silakan coba lagi.'])->onlyInput('email'); }
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $known = User::where('email', mb_strtolower($credentials['email']))->first();
            if ($known?->role->admin()) {
                app(AuditLogger::class)->record(null, 'auth.login_failed', 'user', $known->id, ['attempted_alias' => $known->email], $known->school_id, [], null, 'failed');
            }

            return back()->withErrors(['email' => 'Email atau kata sandi tidak sesuai.'])->onlyInput('email');
        }
        if (! Auth::user()->active || Auth::user()->status !== 'active') {
            Auth::logout();

            return back()->withErrors(['email' => 'Akun sedang dinonaktifkan.']);
        }
        $request->session()->regenerate();
        $request->session()->put('admin.session_version', Auth::user()->session_version);
        $request->session()->put('admin.last_seen', time());
        Auth::user()->forceFill(['last_login_at' => now()])->save();
        if (Auth::user()->role->admin()) {
            app(AuditLogger::class)->record(Auth::user(), 'auth.login', 'user', Auth::id(), [], Auth::user()->school_id);
        }if (Auth::user()->must_change_password) {
            return to_route('password.force.show');
        }

        return redirect()->intended(route(Auth::user()->role->admin() ? 'admin.index' : 'map'));
    }

    public function registerForm(): View
    {
        return view('auth.register',['campuses'=>School::where('institution_type','campus')->where('status','active')->orderBy('country_code')->orderBy('name')->get(['id','name','country_code','campus_code'])]);
    }

    public function register(Request $request, DapodikSchoolDirectory $directory): RedirectResponse
    {
        if (! \App\Services\TurnstileService::verify($request->input('cf-turnstile-response'), $request->ip())) { return back()->withErrors(['email' => 'Verifikasi keamanan gagal. Silakan coba lagi.'])->withInput(); }

        // Public identifiers are only a recovery input, not trusted school records: import() still verifies NPSN/region upstream.
        $primary = $request->input('school_npsn');
        if (! is_string($primary) || trim($primary) === '') {
            foreach ([$request->input('selected_school_npsn'), $request->input('school_query')] as $candidate) {
                if (is_string($candidate) && preg_match('/^[A-Za-z0-9]{8}$/', trim($candidate))) {
                    $request->merge(['school_npsn' => strtoupper(trim($candidate))]);
                    break;
                }
            }
        }
        $kind=$request->validate(['institution_type'=>['sometimes',Rule::in(['school','campus'])]])['institution_type']??'school';
        $campus=$kind==='campus';
        $schoolRule=$campus?'exclude':'required';
        $data=$request->validate(['display_name'=>['required','string','min:2','max:30'],
            'campus_id'=>[$campus?'required':'exclude','integer',Rule::exists('schools','id')->where(fn($q)=>$q->where('institution_type','campus')->where('status','active'))],
            'province_code'=>[$schoolRule,'regex:/^\d{2}$/'],'regency_code'=>[$schoolRule,'regex:/^\d{2}\.\d{2}$/'],'district_code'=>[$schoolRule,'regex:/^\d{2}\.\d{2}\.\d{2}$/'],
            'school_npsn'=>[$schoolRule,'regex:/^[A-Za-z0-9]{8}$/'],'school_name'=>['nullable','string','max:255'],
            'email'=>['required','email','max:255','unique:users,email'],'password'=>['required','confirmed',PasswordRule::min(8)],'parental_consent'=>['accepted'],
            'faculty'=>[$campus?'nullable':'exclude','string','max:150']],
            ['campus_id.required'=>'Pilih kampus yang terdaftar.','campus_id.exists'=>'Kampus tidak tersedia untuk pendaftaran.','school_npsn.required'=>'Pilihan sekolah belum tersimpan. Pilih sekolah dari hasil pencarian atau masukkan NPSN yang lengkap.','province_code.required'=>'Pilih provinsi sekolah.','regency_code.required'=>'Pilih kabupaten/kota sekolah.','district_code.required'=>'Pilih kecamatan sekolah.','password.confirmed'=>'Ulangi kata sandi harus sama dengan kata sandi.']);
        if($campus){$school=School::where('institution_type','campus')->where('status','active')->findOrFail($data['campus_id']);}
        else{
            try{$school=$directory->import(strtoupper($data['school_npsn']),$data['province_code'],$data['regency_code'],$data['district_code']);}
            catch(Throwable $error){report($error);throw ValidationException::withMessages(['school_npsn'=>'Sekolah tidak dapat diverifikasi dari direktori nasional. Pilih kembali sekolah atau coba beberapa saat lagi.']);}
            if($school->status==='archived')throw ValidationException::withMessages(['school_npsn'=>'Sekolah ini sudah diarsipkan dan tidak menerima pendaftaran baru.']);
        }
        $user=DB::transaction(function()use($school,$data,$campus){
            $s=School::whereKey($school->id)->lockForUpdate()->firstOrFail();
            if($campus&&($s->institution_type!=='campus'||$s->status!=='active'))throw ValidationException::withMessages(['campus_id'=>'Kampus tidak lagi menerima pendaftaran.']);
            return User::query()->create(['display_name'=>$data['display_name'],'school_id'=>$s->id,'faculty'=>$campus?($data['faculty']??null):null,'email'=>mb_strtolower(trim($data['email'])),'password'=>$data['password'],'parental_consent_at'=>now(),'locale'=>app()->getLocale(),'active'=>true]);
        });
        Auth::login($user);
        $request->session()->regenerate();

        return to_route('map', ['school' => $school->id])->with('success', $campus?'Akun berhasil dibuat. Kampus tujuan sudah dipilih.':'Akun berhasil dibuat. Sekolah tujuan sudah dipilih.');
    }

    public function forgotForm(): View
    {
        return view('auth.forgot');
    }

    public function forgot(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        if (! User::where('email', $request->input('email'))->first()?->role->admin()) {
            Password::sendResetLink($request->only('email'));
        }

        return back()->with('status', 'Jika email terdaftar, tautan reset telah dikirim.');
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('auth.reset', ['token' => $token, 'email' => $request->email]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate(['token' => ['required'], 'email' => ['required', 'email'], 'password' => ['required', 'confirmed', PasswordRule::min(8)]]);
        $target = User::where('email', $data['email'])->first();
        if ($target?->role->admin()) {
            throw ValidationException::withMessages(['email' => 'Minta tautan reset dari pembuat atau atasan admin.']);
        }$status = Password::reset($data, fn (User $user, string $password) => $user->forceFill(['password' => Hash::make($password)])->save());

        return $status === Password::PASSWORD_RESET ? to_route('login')->with('status', __($status)) : back()->withErrors(['email' => __($status)]);
    }

    public function logout(Request $request): RedirectResponse
    {
        if ($request->user()?->role->admin()) {
            app(AuditLogger::class)->record($request->user(), 'auth.logout', 'user', $request->user()->id, [], $request->user()->school_id);
        }Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home');
    }
}
