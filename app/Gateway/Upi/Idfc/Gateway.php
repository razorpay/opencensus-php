<?php

namespace RZP\Gateway\Upi\Idfc;

class Gateway
{
    public function makeRequest(string $type, array $params)
    {

        $requestParams = RequestFields::getRequestTemplate($type);

        $hmac = RequestFields::requiresHMAC($type);

        $params = array_replace_recursive($requestParams, $params);

        // We don't want to add extra fields from the defaults
        array_walk_recursive($params, function (&$value, $key, $defaults) {
            if (array_key_exists($key, $defaults))
            {
                $value = $defaults[$key];
            }
        }, $this->getDefaults());

        $this->setMerchantCreds($params);

        $req = $this->getUPIReq($params);

        // TODO: Sign it if $hmac===true

        return $this->createSoapRequestBody($type, $req);
    }

    protected function setMerchantCreds(array &$params)
    {
        if (array_key_exists('MerchantCredentials', $params['UPI'])
        {
            $params['UPI']['MerchantCredentials'] = $this->generateMerchantCredentials($params['UPI']['MsgId']);
        }
    }

    protected function generateMerchantCredentials(string $msgId)
    {
        // Need to rewrite from java to PHP
    }

    protected function createSoapRequestBody(string $type, string $upiReq)
    {
        return <<<EOT
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:upi="http://com/fss/upi" xmlns:java="java:com.fss.upi.req">
   <soapenv:Header/>
   <soapenv:Body>
      <upi:$type>
        $upiReq
      </upi:$type>
   </soapenv:Body>
</soapenv:Envelope>
EOT;
    }

    public function getUPIReq(array $attribs, string &$str = '')
    {
        foreach ($attribs as $key => $value)
        {
            $str .= "<java:$key>";

            if (is_array($value))
            {
                $str .= $this->getUPIReq($value, $str);
            }
            else
            {
                $str .= $value;
            }

            $str .= "</java:$key>";
        }

        return "<upi:req>$str</upi:req>";
    }

    protected function getDefaults()
    {
        return [
            // TODO: Do this properly
            // 'UPI'   =>  [
                'BankId'            => '401613',
                'OrgId'             => '400054',
                'MerchantID'        => '12345',
                'TerminalID'        => '01',
                'Channel'           => '06',
                'SubMerchantID'     => '123456',
                'password'          => '11111',
                'PayerType'         => 'PERSON',
                'TimeStamp'         => time(),
                'MsgId'             => $this->generateMsgId(),
            // ]
        ];
    }

    protected function generateMsgId()
    {
        $uuid = sprintf('%04x%04x%04x%04x%04x%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );

        return strtoupper($uuid);
    }
}
