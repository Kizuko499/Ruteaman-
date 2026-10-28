<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
class LanguageController extends Controller
{
    public function update(Request $request)
    {
        $message='Pilihan bahasa sudah dihapus. Situs ini hanya menggunakan Bahasa Indonesia.';
        return $request->expectsJson()
            ? response()->json(['message'=>$message],410)
            : response($message,410)->header('Content-Type','text/plain; charset=UTF-8');
    }
}
