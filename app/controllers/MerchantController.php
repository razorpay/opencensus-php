<?php

use Models\Service\Merchant;

class MerchantController extends BaseController
{
    public function postIndex()
    {
        $input = Input::all();

        $data = Merchant::getNewInstance()->create($input);

        return $data;
    }
}
