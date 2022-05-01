<?php

namespace RZP\Models\FundTransfer;

class Redaction
{
    //keys to be redacted
    protected static $maskingKeys = [
        'beneficiary_name',
        'beneficiaryAccountNo',
        'debitAccountNo',
        'beneficiaryName',
        'beneficiaryContact',
        'credentials',
        'handle',
        'username',
        'beneficiary_mobile',
        'beneficiary_email',
        'account_number',
        'name',
    ];

    //RedactData will redact all keys from maskingkeys array on passed input.
    public function redactData($input)
    {
        if (!empty($input))
        {
            foreach ($input as $key => $value)
            {
                if (in_array($key, self::$maskingKeys, true) === true)
                {
                    $input[$key] = $this->maskData($value);
                }
                else if (is_array($value) === true)
                {
                    $input[$key] = $this->redactData($value);
                }
            }
        }

        return $input;
    }

    //Maskdata function masks every key value on given input.
    protected function maskData($input)
    {
        if (is_array($input) === true)
        {
            foreach ($input as $key => $val)
            {
                $input[$key] = $this->maskData($val);
            }
        }
        else
        {
            if (is_string($input) === true)
            {
                $input = str_repeat('*', strlen($input));
            }
        }

        return $input;
    }
}
