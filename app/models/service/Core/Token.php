<?php

namespace Models\Service\Core;

use Models\Manager;
use Models\DAL;

class Token
{
    public function create($input, $merchantId, $cardId)
    {
        $input['merchant_id'] = $merchantId;

        $data = Manager\CardToken::createValidate($input)->getData();

        $data['card_id'] = $cardId;

        $token = DAL\CardToken::createOrFail($data);

        return $token;
    }
}
