<?php

namespace Models\Service;

use Models\Manager;
use Models\DAL;

class Token extends Service
{
    /**
     * Generates a new token referencing credit card info provided.
     * @param  array $data  [description]
     * @return array/null $error        [description]
     */
    public function generate($input)
    {
        list($card_input, $token_input) = Manager\Token::separateTokenAndCardCreateInput($input);

        $card_data = Manager\Card::createValidate($card_input)->getData();

        $card = DAL\Card::create($card_data);

        $token_data = Manager\Token::createValidate($token_input)->getData();

        $token_data['card_id'] = $card->getId();

        $token = DAL\Token::create($token_data);

        // $token_data = $card_token_do->toArray();

        return $token;
    }
}