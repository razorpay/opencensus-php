<?php 
namespace DomainObject;

use ERR;
use Utility;

class CardToken extends DomainObject
{
    private $attr = array(
        'id'          => null,
        'token'       => null,

        'card_id'     => null,
        'merchant_id' => null,

        'expired'     => 0,
        'created_at'  => null,
        'updated_at'  => null
        );

    private static $input_keys = array(
        'card_id',
        'merchant_id',
        'expired'
        );

    private static $TOKEN_LEN = 16;

    private $card_do = null;

    private $data = null;

    private $input = null;

    private function generate()
    {
        $token = Utility::generate_token(self::$TOKEN_LEN);
        
        $this->attr['token'] = $token;
    }

    private function build_card_do($input)
    {
        $card_do = new Card();
        
        $err = $card_do->build($input);
        
        if ($err === ERR::SUCCESS)
        {
            $this->card_do = $card_do;
        }

        return $err;
    }

    private function verify_input()
    {
        $input = $this->input;

        if ($input === null)
        {
            return invalid_parameter('Input is null');
        }

        $token_keys = array_keys($input);
        $invalid_keys = array_diff($token_keys, self::$input_keys);

        if (count($invalid_keys) !== 0)
        {
            return ERR::invalid_keys($invalid_keys);
        }

        if ((!isset($input['merchant_id'])) or 
            (empty($input['merchant_id'])) or 
            (!is_numeric($input['merchant_id'])))
        {
            throw new \InvalidArgumentException("merchant id is not set");
        }

        if (array_key_exists('expired', $input))
        {
            if (($expired !== '0') or 
                ($expired !== '1'))
                throw new \InvalidArgumentException('Key "expired" can only be 0 or 1');
        }

        return ERR::SUCCESS;
    }

    /**
     * @param array $input  Input supplied by the user goes.
     * @param array $data   Data generated and supplied by the application.
     * @return ERR  ERR::SUCCESS on success or proper error code on error.
     */
    public function build(array $input = null)
    {
        $card_input_keys = Card::input_keys();

        $card_input = array();
        foreach ($card_input_keys as $ix)
        {
            if (isset($input[$ix]))
            {
                $card_input[$ix] = $input[$ix];
                unset($input[$ix]);
            }
        }

        $this->input = $input;

        $err = $this->build_card_do($card_input);

        if ($err !== ERR::SUCCESS)
        {
            return $err;
        }

        $err = $this->verify_input();

        if ($err !== ERR::SUCCESS)
        {
            return $err;
        }
        
        $this->data = $input;

        $this->set();

        $this->generate();

        return ERR::SUCCESS;
    }

    /**
     * @param array $data   The data provided here is added as the 
     *                      properties of this domain object.
     */
    public function set(array $data = null)
    {
        if ($data === null)
        {
            if ($this->data === null)
            {
                throw new \InvalidArgumentException('No "data" provided');
            }
            else $data = $this->data;
        }
        else 
            $this->data = $data;

        foreach ($this->data as $key=>$value)
        {
            $this->set_attr($key);
        }

        return ERR::SUCCESS;
    }

    public function get_id()
    {
        return $this->attr['id'];
    }

    public function set_id($id)
    {
        $this->attr['id'] = $id;
    }

    public function get_token()
    {
        return $this->attr['token'];
    }

    public function set_token(/*string*/ $token)
    {
        if (($token !== null) and
            (strlen($token) === self::$TOKEN_LEN) and
            (ctype_alnum($token)))
        {
            $this->attr['token'] = $token;
            return ERR::SUCCESS;
        }
        else
            return ERR::INVALID_PARAMETERS;
    }

    public function get_card_id()
    {
        return $this->attr['card_id'];
    }

    public function set_card_id($card_id = null)
    {
        if ($card_id !== null)
        {
            $this->attr['card_id'] = $card_id;

            if ($this->card_do !== null)
            {
                $this->card_do->set_id($card_id);
            }
        }
        else if ($this->card_do !== null)
        {
            $card_id = $this->$card_do->get_id();

            if ($card_id !== null)
            {
                $this->attr['card_id'] = $card_id;
            }
        }
        else
        {
            throw new InvalidArgumentException("Failed to get card_id");
        }

        return ERR::SUCCESS;
    }

    public function get_card_do()
    {
        return $this->card_do;
    }

    public function set_card_do(Card $card_do)
    {
        if ($this->card_do !== null)
            return ERR::INVALID_PARAMETERS;
        
        $this->card_do = $card_do;

        return ERR::SUCCESS;
    }

    public function set_merchant_id($merchant_id)
    {
        $this->attr['merchant_id'] = $merchant_id;
        return ERR::SUCCESS;
    }

    public function get_merchant_id()
    {
        $this->attr['merchant_id'];
    }

    private function set_attr($key)
    {
        if ((array_key_exists($key, $this->data)) and
            (array_key_exists($key, $this->attr)))
        {
            $this->attr[$key] = $this->data[$key];
        }
        else if ($key === 'card_token_id')
        {
            $this->set_id($this->data[$key]);
        }
    }

    public static function input_keys()
    {
        return array_merge(Card::input_keys(), self::$input_keys);
    }

    const FLAG_DEFAULT          = 0x0;
    const WITH_CARD             = 0x1;
    const WITH_OBJECT_FIELD     = 0x2;
    const ONLY_PUBLIC_FIELDS    = 0x4;

    public function get_token_data($flag = 0x0)
    {
        $data = $this->attr;

        if ($flag & self::ONLY_PUBLIC_FIELDS)
        {
            unset(
                $data['id'],
                $data['card_id'],
                $data['merchant_id']);
        }

        if ($flag & self::WITH_OBJECT_FIELD)
            $data['object'] = 'token';

        if ($flag & self::WITH_CARD)
        {
            $card_do_flag = 0x0;

            if ($flag & self::WITH_OBJECT_FIELD)
                $card_do_flag |= Card::WITH_OBJECT_FIELD;

            if ($flag & self::ONLY_PUBLIC_FIELDS)
                $card_do_flag |= Card::ONLY_PUBLIC_FIELDS;

            $card_do_flag |= Card::NO_CHECK_FIELDS;
            $card = $this->card_do->get_card_data($card_do_flag);

            $data['card'] = $card;
        }

        return $data;
    }

}
