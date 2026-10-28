<?php
namespace App\Mail;
use Illuminate\Mail\Mailable;
class AdminInvitationMail extends Mailable {
 public function __construct(public string $inviteUrl){}
 public function build(){return $this->subject('Setel kata sandi admin RuteAman')->view('admin.mail-invitation');}
}
