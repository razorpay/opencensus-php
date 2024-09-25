<?php

namespace RZP\Http\Controllers;

use Request;
use RZP\Constants\HyperTrace;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Product;
use RZP\Trace\Tracer;


class ProductConfigController extends Controller
{
    protected $service = Product\Service::class;

    public function fetchConfigForMerchant(string $merchantId, string $merchantProductConfigId)
    {
        return Tracer::inspan(['name' => HyperTrace::GET_PRODUCT_CONFIG], function () use ($merchantId, $merchantProductConfigId) {

            return $this->service()->getConfig($merchantId, $merchantProductConfigId);
        });
    }

    public function updateConfigForMerchant(string $merchantId, string $merchantProductConfigId)
    {
        $input = Request::all();

        return Tracer::inspan(['name' => HyperTrace::UPDATE_PRODUCT_CONFIG], function () use ($merchantId, $merchantProductConfigId, $input) {

            return $this->service()->updateConfig($merchantId, $merchantProductConfigId, $input);
        });
    }

    public function createConfigForMerchant(string $merchantId)
    {
        $input = Request::all();

        return Tracer::inspan(['name' => HyperTrace::CREATE_PRODUCT_CONFIG], function () use ($merchantId, $input) {
            try {
                return $this->service()->createConfig($merchantId, $input);
            } catch (\Exception $e) {
                if($e->getCode()===ErrorCode::BAD_REQUEST_PRODUCT_CONFIG_ALREADY_IN_PROGRESS){
                    return response()->json(['message' => 'Product config create is in process, please retry after some time'], 202, ['retry-after' => 9]);
                }
                throw $e;
            }
        });
    }
}
