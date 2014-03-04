<?php 
namespace Models\DO;

use ERR;
use Utility;

class CardToken extends DomainObject
{
    protected static $fields = array(
        'id',
        'token',
        'card_id',
        'merchant_id',
        'expired',
        'created_at',
        'updated_at'
        );

    protected static $createRules = array(
        'card_id' 	  : 'required|numeric',
        'merchant_id' : 'required|numeric',
        'expired' 	  : 'sometimes|numeric|digits:1'
        );

    protected static $do = array(
    	'one' => array('Card'),
    	'many' => array());

    protected static $generators = array('token');

    portected static $appends = array('object');

    private static $TOKEN_LEN = 16;

    private function generateToken()
    {
        $token = Utility::generate_token(self::$TOKEN_LEN);
        
        $this->attr['token'] = $token;
    }

    /**
     * @param array $input  Input supplied by the user goes.
     * @param array $data   Data generated and supplied by the application.
     */
    public function build(array $input)
    {
        $card_input_keys = Card::getCreateInputKeys();

        $card_input = array();

		list($token_input, $card_input) = break_assoc_array(
			$input, 
			self::getCreateInputKeys(), 
			Card::getCreateInputKeys());

        $card = Card::create($card_input);

        $this->setCard('Card', $card)

        parent::build($input);
    }

    public function getId()
    {
        return $this->getField('id');
    }

    public function setId($id)
    {
        $this->setField('id', $id);
    }

    public function getToken()
    {
        return $this->getField('token');
    }

    public function setTokenField(/*string*/ $token)
    {
        if ((strlen($token) === self::$TOKEN_LEN) and
            (ctype_alnum($token)))
        {
            $this->setField('token', $token);
        }
        else
        {
        	throw new \InvalidArgumentException('invalid parameter');
        }
    }

    public function getCardId()
    {
        return $this->getField('card_id');
    }

    public function setCardId($card_id)
    {
    	$this->setField('card_id', $card_id);

    	$card = $this->getObject('Card');

        if ($card !== null)
        {
            $card->setPrimary($card_id);
        }
    }

    public function getCard()
    {
        return $this->getObject('Card');
    }

    public function setCard(Card $card)
    {
    	$this->setObject('Card', $card)
    }

    public function setMerchantId($merchant_id)
    {
        $this->setField('merchant_id', $merchant_id)
    }

    public function getMerchantId()
    {
        $this->getField('merchant_id');
    }

    public static function getInputKeys()
    {
        return array_merge(Card::input_keys(), self::$input_keys);
    }

    public function getObjectField()
    {
    	return 'token';
    }

    const WITH_CARD             = 0x1024;

    public function toArray($flag = 0x0)
    {
    	$array = parent::toArray($flag);

        if ($flag & self::WITH_CARD)
        {
            $card_do_flag = 0x0;

            $card_do_flag |= Card::NO_CHECK_FIELDS;
        
            $card = $this->card_do->toArray($card_do_flag);

            $array['card'] = $card;
        }

        return $array;
    }

}
