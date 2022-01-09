<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Merchant\Product\TncMap;
use RZP\Http\Controllers\Traits\HasCrudMethods;

class TncMapController extends Controller
{
    use HasCrudMethods;

    protected $service = TncMap\Service::class;

    public function delete(string $id)
    {

    }

    public function fetchTncForBusinessUnit(string $businessUnit)
    {
        return $this->service()->fetchTncForBU($businessUnit);
    }
}
