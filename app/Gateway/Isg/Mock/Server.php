<?php

namespace RZP\Gateway\Isg\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use phpseclib\Crypt\AES;
use RZP\Gateway\Base\AESCrypto;
use RZP\Gateway\Isg\ResponseField;

class Server extends Base\Mock\Server
{
	public function fillBharatQrCallback(& $request , $qrCode)
	{
		$request[ResponseField::PRIMARY_ID] = substr($qrCode['id'], 3);

		$encryptedCardNumber =  $this->getEncryptedString($request[ResponseField::CONSUMER_PAN]);

		$request[ResponseField::CONSUMER_PAN] = $encryptedCardNumber;
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
			ResponseField::PRIMARY_ID                   => 'tobeFilled',
			ResponseField::SECONDARY_ID                 => 'reference_id',
			ResponseField::MERCHANT_PAN                 => '4287346823986423',
			ResponseField::TRANSACTION_ID               => 'abcde12345678910',
			ResponseField::TRANSACTION_DATE_TIME        =>  Carbon::now()->format('Y-m-d H:i:s'),
			ResponseField::TRANSACTION_AMOUNT           => '100',
			ResponseField::AUTH_CODE                    => 'ab3456',
			ResponseField::RRN                          =>  random_int(111111111111,999999999999),
			ResponseField::CONSUMER_PAN                 => '4126989019190088',
			ResponseField::STATUS_CODE                  => '00',
			ResponseField::STATUS_DESC                  => 'Transaction Approved',
		];

		$this->content($attributes, $this->action);

		return $attributes;
	}

	protected function getEncryptedString($string)
	{
		$masterKey = $this->getGatewayInstance()->getSecret();

		$aes = new AESCrypto(AES::MODE_ECB, $masterKey);

		return base64_encode($aes->encryptString($string));
	}
}
