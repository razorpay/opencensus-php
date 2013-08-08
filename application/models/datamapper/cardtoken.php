<?php 

namespace DataMapper;

use DB;
use ERR;
use DomainObject\CardToken as CardTokenDO;
use DomainObject\Card as CardDO;

class CardToken extends DataMapper {

	const table = 'cardtokens';

	/**
     * attributes which can be set by us in db.
     */
    private static $attr_insert = array(
        'token',
        'card_id',
        'merchant_id',
        'expired');

    private static $attr_required = array(
        'token',
        'card_id',
        'merchant_id',
        'expired');

    private static $attr_update = array(
        'expired'
        );

    private static $attr_db = array(
        'id',
        'token',

        'card_id',
        'merchant_id',

        'expired',
        'created_at',
        'updated_at'
        );

    private $card_token_do;

	private $row = array();

	public function insert(CardTokenDO $card_token_do)
	{

        /* 
        @todo: Decide whether to use transactions given that we need to insert
            both the token row and the card row in the table.
        */

        $this->card_token_do = $card_token_do;

        $err = $this->insert_card();

        if ($err !== ERR::SUCCESS)
            return $err;

		$data = $card_token_do->get_token_data();

        foreach ($data as $key=>$value)
        {
            if (!in_array($key, static::$attr_insert))
            {
                continue;
            }

            if (($value === null) or 
                ($value === ""))
            {
                if (in_array($key, static::$attr_required))
                {
                    throw new \InvalidArgumentException($key);
                }
            }
            else
            {
                $this->row[$key] = $value;
            }
        }

        try
        {
            $id = DB::table(self::table)
                    ->insert_get_id($this->row);

            $card_token_do->set_id($id);
        }
        catch(Exception $e)
        {
            var_dump($e);
            return ERR::DB_PROBLEM;
        }

        return ERR::SUCCESS;
	}

    private function insert_card()
    {
        $card_token_do = $this->card_token_do;

        $card_do = $card_token_do->get_card_do();

        $card_id = $card_token_do->get_card_id();

        if ($card_do !== null)
        {
            if ($card_id !== null)
            {
                $card_id_tok = $card_token_do->get_card_id();
                if($card_id !== $card_id_tok)
                    throw new \LogicException("Card Id in token and card don't match");
            }
            else 
            {
                $card_db = new Card();

                $err = $card_db->insert($card_do);

                if ($err !== ERR::SUCCESS)
                    return $err;

                $this->card_db = $card_db;

                $card_id = $card_do->get_id();

                $this->card_token_do->set_card_id($card_id);
            }
        }
        else if ($card_id === null)
        {
            throw new \InvalidArgumentException('card_id not set.');
        }

        return ERR::SUCCESS;
    }

    public function fetch_with_card(CardTokenDO $card_token_do)
    {
        $token = $card_token_do->get_token();

        if ($token === null)
            throw new \InvalidArgumentException("token is null");

        $merchant_id = $card_token_do->get_merchant_id();
        
        $card_table = Card::table;
        $card_token_table = self::table;

        $card_tokens_cols = array('expired');

        // $card_cols = array()

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

            $err = $card_token_do->set($token);

            if ($err !== ERR::SUCCESS)
                return $err;

            $card_do = new CardDO();
            $err = $card_do->set($token);

            if ($err !== ERR::SUCCESS)
                return $err;

            $card_token_do->set_card_do($card_do);
        }
        catch (\Exception $e)
        {
            var_dump($e);
        }

        return ERR::SUCCESS;
    }
}
