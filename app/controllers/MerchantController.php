<?php

use Http\ApiResponse;
use Models\Merchant;
use Models\Key;

class MerchantController extends BaseController
{
    public function postCreateMerchant()
    {
        $input = Input::all();

        $data = (new Merchant\Service)->create($input);

        return ApiResponse::json($data);
    }

    public function getMerchant($id)
    {
        $data = (new Merchant\Service)->fetch($id);

        return ApiResponse::json($data);
    }

    public function getMerchants()
    {
        $input = Input::all();

        $data = (new Merchant\Service)->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function postCreateKeys($merchantId)
    {
        $data = (new Merchant\Service)->createKey($merchantId);

        return ApiResponse::json($data);
    }

    public function getKeys($merchantId)
    {
        $data = (new Merchant\Service)->fetchKeys($merchantId);

        return ApiResponse::json($data);
    }

    public function getKeySecret($keyId)
    {
        $data = (new Key\Core)->getKeySecret($keyId);

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

        return ApiResponse::json($data);
    }

    public function postCreateTerminal($id)
    {
        $input = Input::all();

        $data = (new Merchant\Service)->createTerminal($id, $input);

        return ApiResponse::json($data);
    }

    public function getTerminals($mid)
    {
        $data = (new Merchant\Service)->getTerminals($mid);

        return ApiResponse::json($data);
    }

    public function getTerminal($mid, $tid)
    {
        $data = (new Merchant\Service)->getTerminal($mid, $tid);

        return ApiResponse::json($data);
    }

    public function deleteTermianl($mid, $tid)
    {
        $data = (new Merchant\Serivce)->deleteTerminal($mid, $tid);

        return ApiRespones::json($data);
    }

    public function postActivate($id)
    {
        $data = (new Merchant\Service)->activate($id);

        return ApiResponse::json($data);
    }

    public function postLiveEnable($id)
    {
        $data = (new Merchant\Service)->liveEnable($id);

        return ApiResponse::json($data);
    }

    public function postLiveDisable($id)
    {
        $data = (new Merchant\Service)->liveDisable($id);

        return ApiResponse::json($data);
    }

    public function postBankAccount($id)
    {
        $input = Input::all();

        $data = (new Merchant\Service)->addBankAccount($id, $input);

        return ApiResponse::json($data);
    }

    public function getBankAccount($id)
    {
        $data = (new Merchant\Service)->getBankAccount($id);

        return ApiResponse::json($data);
    }

    public function getBanksPublic()
    {
        $data = (new Merchant\Service)->getEnabledBanks();

        return ApiResponse::json($data);
    }

    public function getBanks($id)
    {
        $data = (new Merchant\Service)->getBanks($id);

        return ApiResponse::json($data);
    }

    public function setBanks($id)
    {
        $input = Input::all();

        $data = (new Merchant\Service)->setPaymentBanks($id, $input);

        return ApiResponse::json($data);
    }

    public function getBalance($id)
    {
        $data = (new Merchant\Service)->fetchBalance($id);

        return ApiResponse::json($data);
    }
}
