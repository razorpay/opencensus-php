<?php

class Card extends Eloquent {

	/**
	 * Dump of input data that we receive from the merchants.
	 * @var [type]
	 */
	private $data = null;

	private static $attr_essential = array(
		'number',
		'expiry_moth',
		'expiry_year',
		'cvc');

	private static $attr_checks = array(
		'cvc_check',
		'address_line1_check',
		'address_zip_check');

	private static $attr_addr = array(
		'address_line1', 
		'address_line2', 
		'address_city',
		'address_zip',
		'address_country');
	
	private static $attr_card_info = array(
		'cardholder',
		'last4',
		'type',
		'country');

	private static $attr_id = array(
		'id');

	public function transactions()
	{
		return $this->has_many('Transaction');
	}

	public function cardtokens()
	{
		return $this->has_many('CardToken');
	}

	/**
	 * In search of a better function name since
	 * "save" is reserved by laravel.
	 * @param  [type] $card [description]
	 * @return [type]       [description]
	 */
	private function save_to_db()
	{
		$card = $this->data;

		// Essential attributes
		$this->set_attr('number', $card['number']);
		$this->set_attr('expiry_month', $card['expiry_month']);
		$this->set_attr('expiry_year', $card['expiry_year']);
		$this->set_attr('cvv', $card['cvv']);
		
		// optional atributes
		$this->set_opt_attr($card);

		// generated attributes
		$this->set_attr('last4', $card['last4']);
		$this->set_attr('type', $card['type']);
		$this->set_attr('country', $card['country']);

		// optional generated attributes
		$this->set_opt_gen_attr($card);

		try 
		{
			$this->save();
		}
		catch (\Exception $e) 
		{
            var_dump($e);
			return $e->getMessage();
		}
	}

	private function set_attr($key, $value)
	{
		$this->{$key} = $value;
	}

	private function set_opt_attr($card)
	{
		foreach (self::$attr_addr as $key)
		{
			if (isset($card[$key]))
				$this->set_attr($key, $data[$key]);
		}

		if (isset($card['cardholder']))
			$this->set_attr('cardholder', $card['cardholder']);
	}

	private function set_opt_gen_attr($card)
	{
		if (isset($card['address_line1']) and 
			isset($card['address_line1_check']))
			$this->set_attr('address_line1_check', (bool) $card['address_line1_check']);

		if (isset($card['address_zip']) and 
			isset($card['address_zip_check']))
			$this->set_attr('address_zip_check', (bool) $card['address_zip_check']);
	
        if (isset($card['cvv_check']))
            $this->set_attr('cvv_check', (bool) $card['cvv_check']);
        
    }

	/**
	 * Verify credit card attributes
	 * @param  arrray $data [description]
	 * @return array              [description]
	 */
	private function verify()
	{
		$err = null;

		$err = CardVerificationService::attributes_check($this->data);

		if ($err !== null)
			return $err;
	}

	public function verify_and_save($data)
	{
		$this->data = $data;

		$err = $this->verify();

		if ($err !== null)
			return $err;
		
		$this->data['last4'] = substr($data['number'], -4);

		$this->data['type'] = CardType::credit_card($data['number']);

        $this->data['country'] = CardType::country($data['number']);

        $err = $this->save_to_db();

        $this->data['cvv_check'] = CardVerificationService::cvv_check($data, $err);

		return $err;
	}

    public function verify_cvv()
    {
        $err = $this->verify_cvv_attributes();
        if ($err !== ERR::SUCCESS)
            return array(null, $err);
        
        $cvv_attr = array(
            $this->cardholder,
            $this->expiry_month,
            $this->expiry_year,
            $this->cvv);

        list($cvv_check, $err) = CardVerificationService::cvv_check($cvv_attr);

        if ($cvv_check !== null)
        {
            if ($cvv_check == 0 or
                $cvv_check == 1)
            {
                $this->cvv_check = $cvv_check;
                $this->save();
            }
            else if ($err !== ERR::SUCCESS)
            {
                $err = ERR::INTERNAL_SERVER_ERROR;
            }
        }

        return array($cvv_check, $err);
    }

    private function verify_cvv_attributes()
    {
        if (!isset($this->cvv) or
            !isset($this->cardholder) or
            !isset($this->id))
            return ERR::INVALID_PARAMETERS;

        if (isset($this->cvv) and
           (($this->cvv == '0') or
            ($this->cvv == '1')))
        {   
            return ERR::CVV_ALREADY_VERIFIED;
        }

        return ERR::SUCCESS;
    }

}

