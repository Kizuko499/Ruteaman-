<?php
namespace App\Mail;
use Illuminate\Mail\Mailable;
class AccountVerificationMail extends Mailable {
 public function __construct(public string $verificationUrl){}
 public function build():static{return $this->subject('Verifikasi email RuteAman')->view('emails.account-verification');}
}
