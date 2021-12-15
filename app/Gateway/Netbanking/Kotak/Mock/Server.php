<?php

namespace RZP\Gateway\Netbanking\Kotak\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking;
use RZP\Gateway\Paytm;
use RZP\Gateway\Netbanking\Kotak\AESCrypto;

class Server extends Base\Mock\Server
{
    public function __construct()
    {
        parent::__construct();

        $this->config = $this->app['config']->get('gateway.netbanking_kotak');
    }

    public function authorize($input)
    {
        if (isset($input['grant_type']))
        {
            $dt =  [
                "access_token" => "3e2b68f8-7294-4c79-8dcb-6773fc99c96a",
                "token_type" => "Bearer",
                "expires_in" => 300,
                "scope" => "oob"
            ];

            return $this->makeResponse($dt);
        }

        //fot test only
        $content = explode('|',$input['msg']);

        $this->encryptedFlow = true;

        $masterKey = $this->getDecryptionKey();

        $encryptor = $this->getGatewayInstance()->getRsaCrypter($masterKey);

        $decryptedData = $encryptor->decryptString($content[0]);

        $content = explode('|',$decryptedData);

        $featureFlag = "nb_netbanking_kotak_api_merchant_whitelisting";

        $variant = $this->app->razorx->getTreatment('10000000000000', $featureFlag, 'test');

        if (sizeof($content) === 10)
        {
            $variant = Gateway::newIntegration;
        }

        if ($variant !== Gateway::newIntegration)
        {
            unset($content[7]);
        }

        $input['msg'] = implode('|',$content);
        $input = $this->getContentFromInput($input, 'authorize', $variant);

        parent::authorize($input);

        if ($variant !== Gateway::newIntegration)
        {
            $this->validateAuthorizeInput($input);
        }

        $content = array(
            'MessageCode'         => $input['MessageCode'],
            'DateTimeInGMT'       => $input['DateTimeInGMT'],
            'MerchantId'          => $input['MerchantId'],
            'TraceNumber'         => $input['TraceNumber'],
            'Amount'              => $input['Amount'],
            'AuthorizationStatus' => 'Y',
            'BankReference'       => '123456',
        );

        $this->content($content, Base\Action::CALLBACK);

        $msg = $this->getMessageStringWithHash($content);

        $masterKey = $this->getEncryptionSecret();

        $encryptor = $this->getGatewayInstance()->getRsaCrypter($masterKey);

        $msg = $encryptor->encryptString($msg);

        $callbackUrl = $this->route->getUrl('gateway_payment_callback_kotak');

        $request = array(
            'url' => $callbackUrl,
            'content' => ['msg' => $msg],
            'method' => 'get',
        );

        return $this->makePostResponse($request);
    }

    public function verify($input)
    {

        if (isset($input['grant_type']))
        {
            $dt = [
                "access_token" => "3e2b68f8-7294-4c79-8dcb-6773fc99c96a",
                "token_type" => "Bearer",
                "expires_in" => 300,
                "scope" => "oob"
            ];

            return $this->makeResponse($dt);
        }

        parent::verify($input);

        $input = $this->decrypt($input);

        $input = $this->getContentFromInput($input, Base\Action::VERIFY);

//        $this->validateActionInput($input,'verify');
        $id = $input['TraceNumber'];

        $payment = (new Netbanking\Base\Repository)->findByTraceIdAndAction(
            $id, Base\Action::AUTHORIZE);

        if ($payment === null)
        {
            $payment = (new Netbanking\Base\Repository)->findByVerificationIdAndAction(
                $id, Base\Action::AUTHORIZE);
        }

        $content = array(
            'MessageCode'         => $input['MessageCode'],
            'DateTimeInGMT'       => $input['DateTimeInGMT'],
            'MerchantId'          => $input['MerchantId'],
            'TraceNumber'         => $input['TraceNumber'],
            'Amount'              => $payment['Amount'],
            'AuthorizationStatus' => 'Y',
            'BankReference'       => '123456',
        );

        $this->content($content, Base\Action::VERIFY);

        $content = $this->getMessageStringWithHash($content);

        $content = $this->encrypt($content);

        $this->content($content, 'verify_action');

        return $this->makeResponse($content);
    }

    protected function getContentFromInput($input, $action = null, $variant = 'control')
    {
        if ($action === null)
        {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $action = $trace[1]['function'];
        }

        $fields = $this->getGatewayInstance()->getFields($action, 'request', $variant);

        $msg = $input;

        if (gettype($input) === "array")
        {
            $msg = $input['msg'];
        }

        $content = explode('|', $msg);
        $input = array_combine($fields, $content);

        return $input;
    }

    protected function encrypt($str)
    {
        $masterKey = $this->getEncryptionSecret();

        $crypto = new AESCrypto($masterKey);

        return $crypto->encryptString($str);
    }

    protected function decrypt($str)
    {
        $masterKey = $this->getEncryptionSecret();

        $crypto = new AESCrypto($masterKey);

        return $crypto->decryptString($str);
    }

    protected function getEncryptionSecret()
    {
        if ($this->action === 'verify')
        {
            return $this->config['test_encrypt_hash_secret'];
        }
        elseif ($this->action === 'authorize')
        {
            return $this->config['kotak_encrypt_secret'];
        }
    }

    protected function getDecryptionKey()
    {
            return $this->config['kotak_decrypt_secret'];
    }

    protected function getStringToHash($content, $glue = '')
    {
        return implode($glue, $content);
    }

    protected function getHashOfString($str)
    {
        $secret = null;

        if ($this->action === 'verify')
        {
            $secret = $this->config['test_verify_hash_secret'];

            $str = $str . '|';

            return (hash_hmac('sha256', $str, $secret));
        }

        $secret = $this->config['test_hash_secret'];

        return (strtoupper(hash_hmac('sha256', $str, $secret)));
    }

    public function getMessageStringWithHash($content)
    {
        $str = $this->getStringToHash($content, '|');

        return $str . '|' . $this->getHashOfString($str);
    }
}
