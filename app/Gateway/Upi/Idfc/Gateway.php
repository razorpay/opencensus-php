<?php

namespace RZP\Gateway\Upi\Idfc;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Constants\Mode;
// use SoapClient as BaseSoapClient;
use RZP\Gateway\Upi\Idfc\SoapClient;

class Gateway extends Base\Gateway
{
    protected $gateway = 'upi_idfc';

    public function generateMerchantDEK(array $input)
    {
        $content = $this->getGenerateMerchantDEKContent($input);

        $request = $this->getStandardSoapRequest($content);

        $response = $this->postRequest($request, 'GenerateMerchantDEK');

        return $response['return'];
    }

    public function merchantProfileCreation(array $input)
    {
        $content = $this->getMerchantProfileCreationContent($input);

        $request = $this->getStandardSoapRequest($content);

        $response = $this->postRequest($request, 'MerchantProfileCreation');

        return $response['return'];
    }

    public function merchantViewRegProfile(array $input)
    {
        $content = $this->getMerchantViewRegProfileContent($input);

        $request = $this->getStandardSoapRequest($content);

        $response = $this->postRequest($request, 'MerchantViewRegProfile');

        return $response['return'];
    }

    public function merchantGenerateOtp(array $input)
    {
        $content = $this->getMerchantGenerateOtpContent($input);

        $request = $this->getStandardSoapRequest($content);

        $response = $this->postRequest($request, 'MerchantGenerateOtp');

        return $response['return'];
    }

    public function merchantAddBank(array $input)
    {
        $content = $this->getMerchantGenerateOtpContent($input);

        $request = $this->getStandardSoapRequest($content);

        $response = $this->postRequest($request, 'MerchantAddBank');

        return $response['return'];
    }

    public function merchantListPublicKeys(array $input)
    {
        $content = $this->getMerchantGenerateOtpContent($input);

        $request = $this->getStandardSoapRequest($content);

        $response = $this->postRequest($request, 'MerchantListPublicKeys');

        return $response['return'];
    }

    public function merchantCheckRegVirAddr(array $input)
    {
        $content = $this->getMerchantCheckRegVirAddrContent($input);

        $request = $this->getStandardSoapRequest($content);

        $response = $this->postRequest($request, 'MerchantCheckRegVirAddr');

        return $response['return'];
    }

    public function merchantViewRegVirAddr(array $input)
    {
        $content = $this->getMerchantViewRegVirAddrContent($input);

        $request = $this->getStandardSoapRequest($content);

        $response = $this->postRequest($request, 'MerchantViewRegVirAddr');

        return $response['return'];
    }

    public function merchantListBankAcc(array $input)
    {
        $content = $this->getMerchantListBankAccContent($input);

        $request = $this->getStandardSoapRequest($content);

        $response = $this->postRequest($request, 'MerchantListBankAcc');

        return $response['return'];
    }

    protected function getMerchantListBankAccContent(array $input)
    {
        $input['gateway']['msg_id'] = upi_uuid(false);

        $content = [
            'req' => [
                'OTP'   => '',
                'CredType' => 'OTP',
                'KeyIndex' => '20150822',
                'KeyCode' => 'NPCI',
                'BankName' => 'RAZR',
                'AddrType' => 'ACCOUNT',
                'PayerCode' => '0000',
                'UPI' => [
                    'TimeStamp'              => time(),
                    'MsgId'                  => $input['gateway']['msg_id'],
                    'DeviceID'               => \Str::random(20),
                    'Channel'                => '06',
                    'MobileNo'               => '8199080070',//$input['customer']['contact'],
                    'PayerType'              => 'PERSON',
                    'OrgId'                  => $this->getOrgId($input),
                    'BankId'                 => $this->getBankId(),
                    // 'Remarks'             => 'Send Money Request',
                    // 'AppVersion'          => '1.0.1',
                    'MerchantID'             => $this->getMerchantId($input['terminal']),
                    'TerminalID'             => $this->getTerminalId($input['terminal']),
                    'SubMerchantID'       => '123456',
                    'MerchantCredentials' => $this->getMerchantCredentials($input)
                ]
            ]
        ];

        return $content;
    }

    protected function getMerchantCheckRegVirAddrContent(array $input)
    {
        $input['gateway']['msg_id'] = upi_uuid(false);

        $content = [
            'req' => [
                'VirAdddr'   => 'crimson@razor',
                'UPI' => [
                    'TimeStamp'              => time(),
                    'MsgId'                  => $input['gateway']['msg_id'],
                    'DeviceID'               => \Str::random(20),
                    'Channel'                => '06',
                    'MobileNo'               => '8199080070',//$input['customer']['contact'],
                    'PayerType'              => 'PERSON',
                    'OrgId'                  => $this->getOrgId($input),
                    'BankId'                 => $this->getBankId(),
                    // 'Remarks'             => 'Send Money Request',
                    // 'AppVersion'          => '1.0.1',
                    'MerchantID'             => $this->getMerchantId($input['terminal']),
                    'TerminalID'             => $this->getTerminalId($input['terminal']),
                    'SubMerchantID'       => '123456',
                    'MerchantCredentials' => $this->getMerchantCredentials($input)
                ]
            ]
        ];

        return $content;
    }

    protected function getMerchantListPublicKeysContent(array $input)
    {
        $input['gateway']['msg_id'] = upi_uuid(false);

        $content = [
            'req' => [
                'CredType' => 'challenge',
                'CredSubType' => 'initial',
                'KeyCode' => 'NPCI',
                'TxnType' => 'ListKeys',
                'KeyIndex' => '20150822',
                'UPI' => [
                    'TimeStamp'              => time(),
                    'MsgId'                  => $input['gateway']['msg_id'],
                    'DeviceID'               => \Str::random(20),
                    'Channel'                => '06',
                    'MobileNo'               => '8199080070',//$input['customer']['contact'],
                    'PayerType'              => 'PERSON',
                    'OrgId'                  => $this->getOrgId($input),
                    'BankId'                 => $this->getBankId(),
                    // 'Remarks'             => 'Send Money Request',
                    // 'AppVersion'          => '1.0.1',
                    'MerchantID'             => $this->getMerchantId($input['terminal']),
                    'TerminalID'             => $this->getTerminalId($input['terminal']),
                    'SubMerchantID'          => '123456',
                    'MerchantCredentials'    => $this->getMerchantCredentials($input),
                ]
            ]
        ];

        return $content;
    }

    protected function getMerchantAddBankContent(array $input)
    {
        $input['gateway']['msg_id'] = upi_uuid(false);

        $content = [
            'req' => [
                'UPI' => [
                    'TimeStamp'              => time(),
                    'MsgId'                  => $input['gateway']['msg_id'],
                    'DeviceID'               => \Str::random(20),
                    'Channel'                => '06',
                    'MobileNo'               => '8199080070',//$input['customer']['contact'],
                    'PayerType'              => 'PERSON',
                    'OrgId'                  => $this->getOrgId($input),
                    'BankId'                 => $this->getBankId(),
                    // 'Remarks'             => 'Send Money Request',
                    // 'AppVersion'          => '1.0.1',
                    'MerchantID'             => $this->getMerchantId($input['terminal']),
                    'TerminalID'             => $this->getTerminalId($input['terminal']),
                    'SubMerchantID'          => '123456',
                    'MerchantCredentials'    => $this->getMerchantCredentials($input),
                ]
            ]
        ];

        return $content;
    }

    protected function getMerchantGenerateOtpContent(array $input)
    {
        $input['gateway']['msg_id'] = upi_uuid(false);

        $content = [
            'req' => [
                'UPI' => [
                    'TimeStamp'              => time(),
                    'MsgId'                  => $input['gateway']['msg_id'],
                    'DeviceID'               => \Str::random(20),
                    'Channel'                => '06',
                    'MobileNo'               => '8199080070',//$input['customer']['contact'],
                    'PayerType'              => 'PERSON',
                    'OrgId'                  => $this->getOrgId($input),
                    'BankId'                 => $this->getBankId(),
                    // 'Remarks'             => 'Send Money Request',
                    // 'AppVersion'          => '1.0.1',
                    'MerchantID'             => $this->getMerchantId($input['terminal']),
                    'TerminalID'             => $this->getTerminalId($input['terminal']),
                    'SubMerchantID'          => '123456',
                    'MerchantCredentials'    => $this->getMerchantCredentials($input),
                    'Message'                => 'activate+869649022152494'
                ]
            ]
        ];

        return $content;
    }

    protected function getMerchantViewRegVirAddrContent(array $input)
    {
        $input['gateway']['msg_id'] = upi_uuid(false);

        $content = [
            'req' => [
                'VirAdddr'   => 'crimson@razor',
                'UPI' => [
                    'TimeStamp'              => time(),
                    'MsgId'                  => $input['gateway']['msg_id'],
                    'DeviceID'               => \Str::random(20),
                    'Channel'                => '06',
                    'MobileNo'               => '8199080070',//$input['customer']['contact'],
                    'PayerType'              => 'PERSON',
                    'OrgId'                  => $this->getOrgId($input),
                    'BankId'                 => $this->getBankId(),
                    // 'Remarks'             => 'Send Money Request',
                    // 'AppVersion'          => '1.0.1',
                    'MerchantID'             => $this->getMerchantId($input['terminal']),
                    'TerminalID'             => $this->getTerminalId($input['terminal']),
                    'SubMerchantID'       => '123456',
                    'MerchantCredentials' => $this->getMerchantCredentials($input)
                ]
            ]
        ];

        return $content;
    }

    protected function getMerchantViewRegProfileContent(array $input)
    {
        $input['gateway']['msg_id'] = upi_uuid(false);

        $content = [
            'req' => [
                'UPI' => [
                    'TimeStamp'              => time(),
                    'MsgId'                  => $input['gateway']['msg_id'],
                    'DeviceID'               => \Str::random(20),
                    'Channel'                => '06',
                    'MobileNo'               => '8199080070',//$input['customer']['contact'],
                    'PayerType'              => 'PERSON',
                    'OrgId'                  => $this->getOrgId($input),
                    'BankId'                 => $this->getBankId(),
                    // 'Remarks'             => 'Send Money Request',
                    // 'AppVersion'          => '1.0.1',
                    'MerchantID'             => $this->getMerchantId($input['terminal']),
                    'TerminalID'             => $this->getTerminalId($input['terminal']),
                    'SubMerchantID'       => '123456',
                    'MerchantCredentials' => $this->getMerchantCredentials($input)
                ]
            ]
        ];

        return $content;
    }

    protected function getMerchantProfileCreationContent(array $input)
    {
        $input['gateway']['msg_id'] = upi_uuid(false);

        $content = [
            'req' => [
                'AdhaarNo'   => '1466249796',
                'QuestionId' => '1234',
                'Answer'     => '1234',
                'DOB'        => '23031988',
                'Email'      => 'vivek@razorpay.com',
                'FirstName'  => 'Vivek',
                'LastName'   => 'Kumar',
                'UserName'   => 'crimson',
                'Gender'     => 'Male',
                'devName'    => 'MOBILE',
                'devModel'   => 'LATEST',
                'os'         => 'Android',
                'osVersion'  => '1.0.0',
                'appName'    => 'com.razorpay',
                'appVersion' => '1.0.0',
                'AppPwd'     => 'random',
                'GcmID'      => '1222222',
                'VirAdddr'   => 'crimson@razor',
                'UPI' => [
                    'TimeStamp'              => time(),
                    'MsgId'                  => $input['gateway']['msg_id'],
                    'DeviceID'               => \Str::random(20),
                    'Channel'                => '06',
                    'MobileNo'               => '8199080070',//$input['customer']['contact'],
                    'PayerType'              => 'PERSON',
                    'OrgId'                  => $this->getOrgId($input),
                    'BankId'                 => $this->getBankId(),
                    // 'Remarks'             => 'Send Money Request',
                    // 'AppVersion'          => '1.0.1',
                    'MerchantID'             => $this->getMerchantId($input['terminal']),
                    'TerminalID'             => $this->getTerminalId($input['terminal']),
                    'SubMerchantID'       => '123456',
                    'MerchantCredentials' => $this->getMerchantCredentials($input)
                ]
            ]
        ];

        return $content;
    }

    protected function getGenerateMerchantDEKContent(array $input)
    {
        $content = [
            'req' => [
                'UPI' => [
                    'TimeStamp'              => time(),
                    'MsgId'                  => upi_uuid(false),//$input['gateway']['msg_id'],
                    'DeviceID'               => \Str::random(20),
                    'Channel'                => '06',
                    'MobileNo'               => '8199080070',//$input['customer']['contact'],
                    'PayerType'              => 'PERSON',
                    'OrgId'                  => $this->getOrgId($input),
                    'BankId'                 => $this->getBankId(),
                    // 'Remarks'             => 'Send Money Request',
                    // 'AppVersion'          => '1.0.1',
                    'MerchantID'             => $this->getMerchantId($input['terminal']),
                    'TerminalID'             => $this->getTerminalId($input['terminal']),
                    // 'SubMerchantID'       => '123456',
                    // 'MerchantCredentials' => $this->getMerchantCredentials($input['terminal'])
                ]
            ]
        ];

        return $content;
    }

    protected function getOrgId(array $input)
    {
        // todo: remove this
        return '400054';

        $orgId = $input['terminal']['gateway_merchant_id2'];

        if ($this->mode === Mode::TEST)
        {
            $orgId = $this->config['gateway_merchant_id2'];
        }

        return $orgId;
    }

    protected function getBankId()
    {
        // todo: remove this
        return '401613';

        $bankId = $this->config['bank_id'];

        return $bankId;
    }

    protected function getMerchantId(array $terminal)
    {
        return '12345';
        $merchantId = $input['terminal']['gateway_merchant_id'];

        if ($this->mode === Mode::TEST)
        {
            $merchantId = $this->config['gateway_merchant_id'];
        }

        return $merchantId;
    }

    protected function getTerminalId(array $terminal)
    {
        return '01';
        $terminalId = $input['terminal']['gateway_terminal_id'];

        if ($this->mode === Mode::TEST)
        {
            $terminalId = $this->config['gateway_terminal_id'];
        }

        return $terminalId;
    }

    protected function getMerchantCredentials(array $input)
    {
        $password = '11111';

        $msgId = $input['gateway']['msg_id'];

        $str = $msgId . '#' . $password;
        $key = '474B3064685565424C487374314379334F7450793579522B2F55524F486A646E6F634D614C562B575A646F3D';

        return $this->encrypt($key, $str);
    }

    protected function encrypt($key, $message)
    {
         $padded = pkcs5_pad($message, mcrypt_get_block_size(MCRYPT_3DES, MCRYPT_MODE_CBC));

         $encrypted = mcrypt_encrypt(MCRYPT_3DES, $key, $padded, MCRYPT_MODE_CBC);

        return $encrypted;
    }

    protected function getSoapClientObject($request)
    {
        $soapClient = new SoapClient($request['wsdl'], $request['options']);

        // $headers = $this->getSoapHeader($request);
        // $soapClient->__setSoapHeaders($headers);

        return $soapClient;
    }

    protected function getWsdlFile()
    {
        $file = __DIR__ . '/Wsdl/UPIMerchantService.wsdl-2.xml';

        if ($this->mode === Mode::TEST)
        {
            $file = __DIR__ . '/Wsdl/UPIMerchantService.wsdl-2.xml';
        }

        return $file;
    }

    protected function getStandardSoapRequest($content = [])
    {
        $request = [
            'wsdl'    => $this->getWsdlFile(),
            'content' => $content,
            'options' => [
                'encoding'           => 'UTF-8',
                'exception'          => true,
                'connection_timeout' => self::TIMEOUT
            ],
        ];

        return $request;
    }

    protected function postRequest($request, $operation)
    {
        $soapClient = $this->getSoapClientObject($request);

        $response = $soapClient->$operation($request['content']);

        // Hack to convert object to array recursively
        return json_decode(json_encode($response), true);
    }

    public function makeRequest(string $type, array $params)
    {
        $requestParams = RequestFields::getRequestTemplate($type);

        $hmac = RequestFields::requiresHMAC($type);

        $params = array_replace_recursive($requestParams, $params);

        $defaultParams = $this->getDefaults();

        // We don't want to add extra fields from the defaults
        array_walk_recursive(
            $params,
            function (&$value, $key, $defaults)
            {
                if (array_key_exists($key, $defaults))
                {
                    $value = $defaults[$key];
                }
            },
            $defaultParams);

        $this->setMerchantCreds($params);

        $req = $this->getUpiReq($params);

        // TODO: Sign it if $hmac===true

        return $this->createSoapRequestBody($type, $req);
    }

    protected function setMerchantCreds(array &$params)
    {
        if (array_key_exists('MerchantCredentials', $params['UPI']))
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

    public function getUpiReq(array $attribs, string &$str = '')
    {
        foreach ($attribs as $key => $value)
        {
            $str .= "<java:$key>";

            if (is_array($value))
            {
                $str .= $this->getUpiReq($value, $str);
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
            // TODO: Do this properly
        return [
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

    /**
     * This just generates a UUID.
     * TODO: replace with a crypto-safe version
     * This is prone to collisions
     * @link http://stackoverflow.com/a/2040279/368328
     * @return string UUID
     */
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
