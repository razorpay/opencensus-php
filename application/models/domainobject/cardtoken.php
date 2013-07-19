<?php 
namespace DomainObject;

use ERR;
use Utility;

class CardToken
{
    private $token = array(
        'id'          => null,
        'token'       => null,

        'card_id'     => null,
        'merchant_id' => null,

        'expired'     => 0,
        'created_at'  => null,
        'updated_at'  => null
        );

    private static $input_attributes = array(
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
        
        $this->token['token'] = $token;
    }

    private function build_card_do()
    {
        $card_do = new Card();
        
        $err = $card_do->build($this->input);
        
        if ($err === ERR::SUCCESS)
        {
            $this->card_do = $card_do;
        }

        return $err;
    }

    private function verify_data()
    {
        $data = $this->data;

        foreach($this->data as $key=>$value)
        {
            if (! in_array($key, self::$input_attributes, true))
            {
                echo $key . "should not be in this list.";
                return ERR::INVALID_PARAMETERS;
            }
        }

        $exception = false;

        if ($data === null)
        {
            $exception = true;
        }
        else if ((!isset($data['merchant_id'])) or 
                 (empty($data['merchant_id'])) or 
                 (!is_numeric($data['merchant_id'])))
        {
            $exception = true;
        }
        
        if ($exception === true)
        {
            throw new \InvalidArgumentException("Invalid arguments");
        }

        if (array_key_exists('expired', $data))
        {
            if (!is_numeric($data['expired']))
                throw new InvalidArgumentException('Key "expired" can only be 0 or 1');
            $expired = (int) $data['expired'];
            if (($expired != 0) or 
                ($expired != 1))
                throw new InvalidArgumentException('Key "expired" can only be 0 or 1');
        }

        return ERR::SUCCESS;
    }

    /**
     * @param array $input  Input supplied by the user goes.
     * @param array $data   Data generated and supplied by the application.
     * @return ERR  ERR::SUCCESS on success or proper error code on error.
     */
    public function build(array $input, array $data)
    {
        $this->input = $input;

        $this->data = $data;

        $err = $this->build_card_do();

        if ($err !== ERR::SUCCESS)
        {
            return $err;
        }

        $err = $this->verify_data();

        if ($err !== ERR::SUCCESS)
        {
            return $err;
        }
        
        $this->set();

        $this->generate();

        return ERR::SUCCESS;
    }

    /**
     * @param array $data   The data provided here is added as the 
     *                      properties of this domain object.
     */
    public function set($data = null)
    {
        if ($data === null)
        {
            if ($this->data === null)
            {
                echo "No data present which can be set.";
                return ERR::INVALID_PARAMETERS;
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
        return $this->token['id'];
    }

    public function set_id($id)
    {
        $this->token['id'] = $id;
    }

    public function get_token()
    {
        return $this->token['token'];
    }

    public function set_token(/*string*/ $token)
    {
        if (($token !== null) and
            (strlen($token) === self::$TOKEN_LEN) and
            (ctype_alnum($token)))
        {
            $this->token['token'] = $token;
            return ERR::SUCCESS;
        }
        else
            return ERR::INVALID_PARAMETERS;
    }

    public function get_card_id()
    {
        return $this->token['card_id'];
    }

    public function set_card_id($card_id = null)
    {
        if ($card_id !== null)
        {
            $this->token['card_id'] = $card_id;

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
                $this->token['card_id'] = $card_id;
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
        $this->token['merchant_id'] = $merchant_id;
        return ERR::SUCCESS;
    }

    public function get_merchant_id()
    {
        $this->token['merchant_id'];
    }

    private function set_attr($key)
    {
        if ((array_key_exists($key, $this->data)) and
            (array_key_exists($key, $this->token)))
        {
            $this->token[$key] = $this->data[$key];
        }
        else if ($key === 'card_token_id')
        {
            $this->set_id($this->data[$key]);
        }
    }

    public static function input_keys_for_new_token()
    {
        return Card::input_keys_for_new_card();
    }

    const FLAG_DEFAULT          = 0x0;
    const WITH_CARD             = 0x1;
    const WITH_OBJECT_FIELD     = 0x2;
    const ONLY_PUBLIC_FIELDS    = 0x4;

    public function get_token_data($flag = 0x0)
    {
        $token = $this->token;

        if ($flag & self::ONLY_PUBLIC_FIELDS)
        {
            unset(
                $token['id'],
                $token['card_id'],
                $token['merchant_id']);
        }

        if ($flag & self::WITH_OBJECT_FIELD)
            $token['object'] = 'token';

        if ($flag & self::WITH_CARD)
        {
            $card_do_flag = 0x0;

            if ($flag & self::WITH_OBJECT_FIELD)
                $card_do_flag |= Card::WITH_OBJECT_FIELD;

            if ($flag & self::ONLY_PUBLIC_FIELDS)
                $card_do_flag |= Card::ONLY_PUBLIC_FIELDS;

            $card_do_flag |= Card::NO_CHECK_FIELDS;
            $card = $this->card_do->get_card_data($card_do_flag);

            $token['card'] = $card;
        }

        return $token;
    }

}
