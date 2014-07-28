<?php

use Http\ApiResponse;
use Models\Merchant;

class MerchantController extends BaseController
{
    public function postCreateMerchant()
    {
        $input = Input::all();

        $data = (new Merchant\Service)->register($input);

        return ApiResponse::json($data);
    }

    public function getKeys($merchantId)
    {
        $data = (new Merchant\Service)->fetchKeys($merchantId);

        return ApiResponse::json($data);
    }

    public function putKeys($merchantId, $keyId)
    {
        $input = Input::all();

        $keys = (new Merchant\Service)->updateKey($merchantId, $keyId, $input);

        return ApiResponse::json($keys);
    }

    public function postAssignPricingPlan($id)
    {
        $input = Input::all();

        $data = (new Merchant\Service)->assignPricingPlan($id, $input);

        return ApiResponse::json($data);
    }

    public function getPricingPlan($id)
    {
        $data = (new Merchant\Service)->getPricingPlan($id);
    }
}
