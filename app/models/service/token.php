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
		list($card_input, $card_token_input) = Manager\CardToken::separateTokenAndCardCreateInput($input);

        $card_data = Manager\Card::createValidate($card_input)->getData();

        $card = DAL\Card::create($card_data);
        
        $card_token_data = Manager\CardToken::createValidate($card_token_input)->getData();

        $card_token_data['card_id'] = $card->getId();

		$token = DAL\CardToken::create($card_token_data);

        // $token_data = $card_token_do->toArray();
        
        return $token;
	}

    public function retrieve($token, $merchant_id)
    {
        $cardtoken = DAL\CardToken::findByTokenAndMerchantId($token, $merchant_id);
        $err = false;
        $token_data = false;

        if($cardtoken) {
            $token_data = $cardtoken->toArray();
        } else {
            $err = true;
        }

        return array($token_data, $err);
    }
}