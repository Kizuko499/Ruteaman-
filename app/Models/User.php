<?php
namespace App\Models;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable
{
 use HasFactory,Notifiable;
 protected $fillable=['school_id','faculty','display_name','email','password','parental_consent_at','active','avatar_path','avatar_preset','appearance','locale','notif_seen_at','pending_email','email_verification_token','email_verification_expires_at','email_verified_at','notification_preferences'];
 protected $hidden=['password','remember_token','avatar_path','pending_email','email_verification_token','email_verification_expires_at'];
 protected function casts():array{return ['password'=>'hashed','role'=>Role::class,'parental_consent_at'=>'datetime','active'=>'boolean','email_verified_at'=>'datetime','notif_seen_at'=>'datetime','email_verification_expires_at'=>'datetime','permissions'=>'array','school_scope'=>'array','last_login_at'=>'datetime','must_change_password'=>'boolean','session_version'=>'integer','notification_preferences'=>'array'];}
 public function routeNotificationForMail($notification=null):string{return $this->contact_email?:$this->email;}
 public function getEmailForPasswordReset():string{return $this->email;}
 public function school():BelongsTo{return $this->belongsTo(School::class);}
 public function reports():HasMany{return $this->hasMany(HazardReport::class,'reporter_id');}
 public function confirmations():HasMany{return $this->hasMany(HazardConfirmation::class);}
    /** Preferensi notifikasi email per kategori; default menyala bila belum diatur. */
    public function wantsMail(string $key): bool
    {
        $prefs = $this->notification_preferences ?? [];

        return (bool) ($prefs[$key] ?? true);
    }
}
