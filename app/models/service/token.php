<?php 

namespace Models\Service;

use Models\DO;
use Models\DAL;
use Utility;

class Token
{
    /**
     * Generates a new token referencing credit card info provided.
     * @param  array $data  [description]
     * @return array/null $error        [description]
     */
	public function generate($input)
	{
        $card_token_do = DO\CardToken::create($input);

		DAL\CardToken::persist($card_token_do);

        // $token_data = $card_token_do->toArray();
        
        return $card_token_do;
	}

    public function retrieve($token, $merchant_id)
    {
        $card_token_do = new CardTokenDO;
        $card_token_db = new CardTokenDB;

        $card_token_do->set_token($token);
        
        $card_token_do->set_merchant_id($merchant_id);

        $card_token_db->fetchWithCard($card_token_do);

        $token_data = $card_token_do->toArray();

        return $token_data;
    }
}