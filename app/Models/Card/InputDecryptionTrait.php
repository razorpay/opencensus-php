<?php

namespace RZP\Models\Card;

use RZP\Encryption;
use RZP\Models\Card;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use phpseclib\Crypt\AES;
use RZP\Encryption\AESEncryption;


/*                                                            *\
|-------------------------------------------------------------|
| This trait adds decryption functionality to a card payment. |
| request, currently its supported only for flipkart, going   |
| it will be move to payments-card 							  |
|-------------------------------------------------------------|
\*                                                            */

trait InputDecryptionTrait
{
	public function decryptCardNumberIfApplicable(& $input)
    {
        if (empty($input['encrypted_number']) === true)
        {
            return;
        }

        $this->trace->info(TraceCode::CARD_NUMBER_DECRYPTION, [$input['encrypted_number']]);

        try
        {
            $params = [
                AESEncryption::MODE => AES::MODE_CBC,
                AESEncryption::IV => $this->app['config']->get('applications.tokenisation.flipkart_secure_IV'),
                AESEncryption::SECRET => $this->app['config']->get('applications.tokenisation.flipkart_secure_key'),
            ];

            $cipher = base64_decode($input['encrypted_number']);

            $Decryptor = new Encryption\AESEncryption($params);

            $plainText = $Decryptor->decrypt($cipher);

        }
        catch (\Exception $e)
        {
            throw new \Exception(ErrorCode::BAD_REQUEST_DECRYPTION_FAILED);
        }

        if (empty($plainText) === true)
        {
            throw new \Exception(ErrorCode::BAD_REQUEST_INPUT_VALIDATION_FAILURE);
        }

        unset($input['encrypted_number']);

        $input['number'] = $plainText;
    }

}