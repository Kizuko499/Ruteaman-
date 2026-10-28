<?php
namespace App\Http\Controllers;
use App\Services\CampusDirectory;
use Illuminate\Http\Request;
final class CampusDirectoryController extends Controller {
 public function __invoke(Request $request,CampusDirectory $directory){
  $d=$request->validate(['query'=>'required|string|min:3|max:150','country'=>'nullable|string|size:2|regex:/^[A-Za-z]{2}$/','page'=>'nullable|integer|min:1|max:500']);
  try{return response()->json($directory->search(trim($d['query']),$d['country']??null,(int)($d['page']??1)))->header('Cache-Control','private, no-store');}
  catch(\Throwable $e){return response()->json(['error'=>['code'=>'CAMPUS_DIRECTORY_UNAVAILABLE','message'=>'Direktori kampus sementara tidak tersedia. Kamu tetap bisa menentukan pin kampus langsung di peta.']],503);}
 }
}
