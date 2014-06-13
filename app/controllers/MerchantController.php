<?php

use Models\Service\Merchant;
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
        $merchant_id = BasicAuth::getInstance()->MerchantId();

        return Merchant::getNewInstance()->fetchKeys($merchant_id);
    }

    public function updateKeys()
    {
        $input = Input::all();

        $input['merchant_id'] = BasicAuth::getInstance()->MerchantId();

        $status = Merchant::getNewInstance()->updateKey($input);

        return $status;
    }
}
