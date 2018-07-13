<?php

namespace RZP\Gateway\Isg\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use phpseclib\Crypt\AES;
use RZP\Gateway\Base\AESCrypto;
use RZP\Gateway\Isg\Field;

class Server extends Base\Mock\Server
{
    public function fillBharatQrCallback(& $request , $qrCode)
    {
        $request[Field::PRIMARY_ID] = strtoupper(substr($qrCode['id'], 3));

        $encryptedCardNumber =  $this->getEncryptedString($request[Field::CONSUMER_PAN]);

        $request[Field::CONSUMER_PAN] = $encryptedCardNumber;
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($input);

        $response  = $this->getVerifyResponse();

        return $this->makeResponse($response);
    }

    protected function getVerifyResponse()
    {
        $attributes = [];

        $this->content($attributes, $this->action);

        return $attributes;
    }

    protected function getEncryptedString($string)
    {
        $masterKey = hex2bin($this->getGatewayInstance()->getSecret());

        $aes = new AESCrypto(AES::MODE_ECB, $masterKey);

        return bin2hex($aes->encryptString($string));
    }
}
