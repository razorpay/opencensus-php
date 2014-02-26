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

    protected static $inputRules = array(
        'card_id' : 'numeric',
        'merchant_id': 'required|numeric',
        'expired' : 'sometimes|numeric|digits:1'
        );

    protected static $generators = array('token');

    portected static $appends = array('object');

    private static $TOKEN_LEN = 16;

    private $card_do = null;

    private function generateToken()
    {
        $token = Utility::generate_token(self::$TOKEN_LEN);
        
        $this->attr['token'] = $token;
    }

    private function buildCardDO($input)
    {
        $card_do = new Card();
        
        $card_do->build($input);
    }

    /**
     * @param array $input  Input supplied by the user goes.
     * @param array $data   Data generated and supplied by the application.
     */
    public function build(array $input)
    {
        $card_input_keys = Card::getInputKeys();

        $card_input = array();

        foreach ($card_input_keys as $ix)
        {
            if (isset($input[$ix]))
            {
                $card_input[$ix] = $input[$ix];
                unset($input[$ix]);
            }
        }

        $this->buildCardDO($card_input);

        parent::build();
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
            $this->data['token'] = $token;
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

    public function setCardId($card_id = null)
    {
        if ($card_id !== null)
        {
            $this->attr['card_id'] = $card_id;

            if ($this->card_do !== null)
            {
                $this->card_do->setId($card_id);
            }
        }
        else if ($this->card_do !== null)
        {
            $card_id = $this->$card_do->getId();

            if ($card_id !== null)
            {
                $this->attr['card_id'] = $card_id;
            }
        }
        else
        {
            throw new InvalidArgumentException("Failed to get card_id");
        }
    }

    public function getCardDO()
    {
        return $this->card_do;
    }

    public function setCardDO(Card $card_do)
    {
        if ($this->card_do !== null)
        {
            throw new \InvalidArgumentException('message');
        }
        
        $this->card_do = $card_do;
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
