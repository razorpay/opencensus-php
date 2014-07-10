<?php

use Models\Merchant;
use Models\Service\BasicAuth;

class MerchantController extends BaseController
{
    public function postIndex()
    {
        $input = Input::all();

        $data = (new Merchant\Service)->register($input);

        return Response::json($data);
    }

    public function getKeys()
    {
        $merchant_id = BasicAuth::getInstance()->getMerchantId();

        return (new Merchant\Service)->fetchKeys($merchant_id);
    }

    public function putKeys($id)
    {
        $input = Input::all();

        $input['merchant_id'] = BasicAuth::getInstance()->getMerchantId();

        $keys = (new Merchant\Service)->updateKey($id, $input);

        return json_encode($keys);
    }
}
