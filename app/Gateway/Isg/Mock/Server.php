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
        $request[Field::PRIMARY_ID] = substr($qrCode['id'], 3);

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
        $attributes = [

            Field::PRIMARY_ID                   => 'tobeFilled',
            Field::SECONDARY_ID                 => 'reference_id',
            Field::MERCHANT_PAN                 => '4403844012084006',
            Field::TRANSACTION_ID               => '1817700802564',
            Field::TRANSACTION_DATE_TIME        =>  Carbon:: now()->format('Y-m-d H:i:s'),
            Field::TRANSACTION_AMOUNT           => '1.00',
            Field::AUTH_CODE                    => 'ab3456',
            Field::RRN                          =>  random_int(111111111111,999999999999),
            Field::CONSUMER_PAN                 => '4012001037141112',
            Field::STATUS_CODE                  => '00',
            Field::STATUS_DESC                  => 'Transaction Approved',
        ];

        $attributes[Field::CONSUMER_PAN] =  $this->getEncryptedString($attributes[Field::CONSUMER_PAN]);

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
