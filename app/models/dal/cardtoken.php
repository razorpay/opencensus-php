<?php 

namespace Models\DAL;

use DB;
use ERR;
use Models\DO;

class CardToken extends DataMapper {

	const table = 'cardtokens';

	protected static $primaryKey = 'id';

	protected static $primaryAutoGenerate = true;

	protected static $timestamps = true;

    protected static $attr_db = array(
        'id' => 'db',
        'token' => 'db|insert_req',
        'card_id' => 'db|insert_req',
        'merchant_id' => 'db|insert_req',
        'expired' => 'db|insert_req|update_req',
        'created_at' => 'db',
        'updated_at' => 'db'
        );

    private $card_token_do;

	public function insert(DO\CardToken $card_token_do)
	{

        /* 
        @todo: Decide whether to use transactions given that we need to insert
            both the token row and the card row in the table.
        */

        $this->card_token_do = $card_token_do;

        $this->insertCard();

        parent::insert($card_token_do);
	}

    private function insertCard()
    {
        $card_do = $this->card_token_do->getCard();

        $this->card_db = Card::persist($card_do);

        $this->card_token_do->setCardId($card_do->getId());
    }

    public function fetchWithCard($token, $merchant_id)
    {
        $token = $card_token_do->getToken();

        if ($token === null)
        {
            throw new \InvalidArgumentException("token is null");
        }

        $merchant_id = $card_token_do->getMerchantId();
        
        $card_table = Card::table;
        $card_token_table = self::table;

        $card_tokens_cols = array('expired');

        try
        {
            $token = DB::table($card_token_table)
                        ->join($card_table, $card_token_table.'.card_id', '=', $card_table.'.id')
                        ->where('token', '=', $token);

            if ($merchant_id !== null)
                $token = $token->where('merchant_id', '=', $merchant_id);
            
            $card_id = $card_table + '.id' + ' AS ' + 'card_id';
            $token_id = $card_token_table + '.id' + ' AS ' + 'card_token_id';

            $token = $token->select('*', $card_id, $token_id);

            $token = $token->first();

            $card_token_do->set($token);

            $card_do = new CardDO();
            $card_do->set($token);

            $card_token_do->setCardDO($card_do);
        }
        catch (\Exception $e)
        {
            var_dump($e);
        }
    }
}
