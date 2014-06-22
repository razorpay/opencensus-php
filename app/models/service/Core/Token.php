<?php

namespace Models\Service\Core;

use Models\Manager;
use Models\DAL;

class Token
{
    public function create($merchantId, $cardId)
    {
        $input['merchant_id'] = $merchantId;

        $input['card_id'] = $cardId;

        $data = Manager\CardToken::createValidate($input)->getData();

        $token = DAL\CardToken::createOrFail($data);

        return $token;
    }
}
