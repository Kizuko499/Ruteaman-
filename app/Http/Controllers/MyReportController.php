<?php
namespace App\Http\Controllers;
use Illuminate\View\View;
class MyReportController extends Controller
{
 public function __invoke():View{$reports=request()->user()->reports()->with('hazard.type')->latest()->paginate(12);return view('reports.index',compact('reports'));}
}