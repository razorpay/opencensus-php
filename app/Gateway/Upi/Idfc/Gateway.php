<?php

namespace RZP\Gateway\Upi\Idfc;

class Gateway
{
    public function __construct()
    {

    }

    public function makeRequest(string $type, $params)
    {
        $requestParams = RequestFields::getRequestTemplate($type);
        $hmac = RequestFields::requiresHMAC($type);

        array_merge_recursive($requestParams, $params)
    }

    public function convertToXML(array $attribs, string &$str = '')
    {
        foreach ($attribs as $key => $value)
        {
            $str .= "<java:$key>";

            if (is_array($value))
            {
                $str .= $this->convertToXML($value, $str);
            }
            else
            {
                $str .= $value;
            }

            $str .= "</java:$key>";
        }

        return $str;
    }

    protected function getDefaults()
    {
        return [
            'BankId'            => '401613',
            'OrgId'             => '400054',
            'MerchantID'        => '12345',
            'TerminalID'        => '01',
            'channelId'         => '06',
            'SubMerchantID'     => '123456',
            'password'          => '11111',
            'PayerType'         => 'PERSON',
        ];
    }
}
