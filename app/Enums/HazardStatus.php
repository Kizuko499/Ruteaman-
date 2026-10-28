<?php
namespace App\Enums;
enum HazardStatus:string { case New='new'; case Confirmed='confirmed'; case Verified='verified'; case Disputed='disputed'; case Resolved='resolved'; case Expired='expired'; }