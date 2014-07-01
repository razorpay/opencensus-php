<?php

namespace Models\Manager;

use \Utility;

class Transaction extends EntityManager
{
    protected static $createRules = array(
        'merchant_id'   =>  'required|numeric',
        'amount'        =>  'required|numeric|max:500000',
        'currency'      =>  'required|max:3',
        'token'         =>  'required|alpha_num',
        'desc'          =>  'max:1000',
        // 'hold'          =>  'numeric|max:1|digits:1',
        'udf'           =>  'required'
        );

    protected static $udfRules = array(
        'email'         =>  'required|email|max:250',
        'contact'       =>  'required|numeric|digits_between:8,12');

    protected static $generators = array('status');

    protected static $createValidators = array('currency', 'udf');

    /**
     * Validates Udf fields. email and contact is
     * currently compulsory
     *
     * @param  array $input  input array
     * @return void
     */
    protected function validateUdf($input)
    {
        $udf = $input['udf'];

        if (!is_array($udf))
        {
            throw new BadRequestException(
                'Udf should be provided as an array');
        }

        if (count($udf) > 15)
        {
            throw new BadRequestException('Number of fields in udf should be less than or equal to 15');
        }

        $validation = \Validator::make($udf, static::$udfRules);

        if ($validation->fails())
        {
            throw new UdfErrorException($validation->messages());
        }

        foreach ($udf as $key => $value)
        {
            if (is_array($value))
                throw new BadRequestException('Udf values themselves should not be an array');

            if (strlen($value) > 1024)
                throw new BadRequestException('Udf value [' . $value .'] too large!');

            if (strlen($key) > 1024)
                throw new BadRequestException('Udf value [' . $key .'] too large!');
        }
    }

    protected function validateCurrency($input)
    {
        $currency = $input['currency'];

        //
        // Right now only INR is supported.
        //

        if ($currency !== "INR")
        {
            throw new BadRequestException('Invalid currency: '.$currency.'. Only INR supported.');
        }
    }

    public function generateStatus($input)
    {
    	$this->setField('status', 'open');
    }

    public function getStatus()
    {
        return $this->getField('status');
    }

    public function setStatus($status)
    {
        $this->setField('status', $status);
    }

    public static function checkCardKeyExists($input)
    {
        if (array_key_exists('card', $input) === false)
        {
            throw new BadRequestException(
                'Transaction Exception: Card not provided');
        }
    }
}
