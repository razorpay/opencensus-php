<?php

use Models\Merchant\Service as Merchant;
use Models\Service\BasicAuth;

class MerchantController extends BaseController
{
    public function postIndex()
    {
        $input = Input::all();

        $data = Merchant::getNewInstance()->create($input);

        return $data;
    }

    public function getKeys()
    {
        $merchant_id = BasicAuth::getInstance()->getMerchantId();

        return Merchant::getNewInstance()->fetchKeys($merchant_id);
    }

    public function updateKeys()
    {
        $input = Input::all();

        $input['merchant_id'] = BasicAuth::getInstance()->getMerchantId();

        $status = Merchant::getNewInstance()->updateKey($input);

        return $status;
    }
}
