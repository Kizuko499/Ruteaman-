<?php
namespace App\Services\Routing;
interface RoutingProviderInterface
{
    /** @return array<int,array{id:string,distance_m:int,duration_s:int,geometry:array,steps:array,source:string}> */
    public function routes(array $origin,array $destination,string $profile):array;
    public function healthCheck():bool;
    public function getProviderName():string;
}