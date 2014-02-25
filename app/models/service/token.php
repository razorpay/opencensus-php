<?php 

namespace Service;

use DataMapper\Card as CardDB;
use DomainObject\Card as CardDO;
use DataMapper\CardToken as CardTokenDB;
use DomainObject\CardToken as CardTokenDO;
use ERR;
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
        $input['merchant_id'] = BasicAuth::MerchantId();
        
        $card_token_db = new CardTokenDB();

        $card_token_do = $this->build_token($input);

		$card_token_db->insert($card_token_do);
        
        $flag = CardTokenDO::WITH_OBJECT_FIELD |
                CardTokenDO::WITH_CARD |
                CardTokenDO::ONLY_PUBLIC_FIELDS;

        $token_data = $card_token_do->get_token_data($flag);
        
        return $token_data;
	}

    public function retrieve($token, $merchant_id)
    {
        $card_token_do = new CardTokenDO;
        $card_token_db = new CardTokenDB;

        $card_token_do->set_token($token);
        
        $card_token_do->set_merchant_id($merchant_id);

        $card_token_db->fetch_with_card($card_token_do);

        $flag = CardTokenDO::WITH_OBJECT_FIELD |
                CardTokenDO::WITH_CARD |
                CardTokenDO::ONLY_PUBLIC_FIELDS;
        
        $token_data = $card_token_do->get_token_data($flag);

        return $token_data;
    }

    public function build_token($input)
    {
        $card_token_do = new CardTokenDO();

        $merchant_id = BasicAuth::MerchantId();

        $data['merchant_id'] = $merchant_id;

        $card_token_do->build($input, $data);
        
        return $card_token_do;
    }

}