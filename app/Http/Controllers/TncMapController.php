<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Constants\HyperTrace;
use RZP\Models\Merchant\Product\TncMap;
use RZP\Http\Controllers\Traits\HasCrudMethods;
use RZP\Trace\Tracer;

class TncMapController extends Controller
{
    use HasCrudMethods;

    protected $service = TncMap\Service::class;

    public function delete(string $id)
    {

    }

    public function fetchTncForBusinessUnit(string $businessUnit)
    {
        return Tracer::inspan(['name' => HyperTrace::FETCH_BU_TNC], function () use ($businessUnit) {

            return $this->service()->fetchTncForBU($businessUnit);
        });
    }
}
