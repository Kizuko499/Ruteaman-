<?php
namespace App\Http\Controllers;
use App\Models\Hazard;
use App\Services\HazardEvidenceService;
use Illuminate\Http\{RedirectResponse,Request};
class ConfirmationController extends Controller {
 public function __invoke(Request $request,Hazard $hazard,HazardEvidenceService $evidence):RedirectResponse {
  $d=$request->validate(['vote'=>'required|in:still_there,gone']);
  $evidence->confirm($request->user(),$hazard,$d['vote']);
  return to_route('hazards.public',$hazard)->with('success','Konfirmasi dicatat satu kali per pengguna.');
 }
}
