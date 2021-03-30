<?php

namespace RZP\Gateway\Netbanking\Icici\Mock;

use Razorpay\Api\Request;
use RZP\Exception;
use RZP\Gateway\Base;

use phpseclib\Crypt\AES;
use RZP\Gateway\Netbanking\Icici\Status;
use RZP\Gateway\Netbanking\Icici\Mode;
use RZP\Gateway\Netbanking\Icici\Confirmation;
use RZP\Gateway\Netbanking\Icici\RequestFields;
use RZP\Gateway\Netbanking\Icici\ResponseFields;

class Server extends Base\Mock\Server
{
    const BANK_PAYMENT_ID = 9999999999;

    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        if ($this->isSecondRecurring($input))
        {
            return $this->handleSecondRecurring($input);
        }

        $decryptedData = $this->decryptData($input);

        $this->validateActionInput($decryptedData, 'auth_decrypted');

        $postData = $this->createPostData($decryptedData);

        $this->content($postData, 'auth');

        $content = $this->formatResponseData($postData, $input);

        $callbackUrl = $decryptedData['RU'] . '?' . http_build_query($content);

        return $callbackUrl;
    }

    protected function handleSecondRecurring(array $input)
    {
        $responseArray = [
            ResponseFields::ITEM_CODE       => $input[RequestFields::ITEM_CODE],
            ResponseFields::PAYMENT_ID      => $input[RequestFields::PAYMENT_ID],
            ResponseFields::AMOUNT          => $input[RequestFields::AMOUNT],
            ResponseFields::CURRENCY        => $input[RequestFields::CURRENCY_CODE],
            ResponseFields::SI_REFERENCE_ID => $input[RequestFields::SI_REFERENCE_NUMBER],
            ResponseFields::PAYMENT_DATE    => $input[RequestFields::SI_DEBIT_PAYMENT_DATE],
            ResponseFields::BANK_PAYMENT_ID => self::BANK_PAYMENT_ID,
            ResponseFields::PAID            => Confirmation::YES,
            ResponseFields::STATUS          => Status::SI_SUCCESS
        ];

        $this->content($responseArray, 'second_recurring');

        $response = $this->createXmlResponse($responseArray);

        $this->content($response, 'second_recurring_xml');

        return $this->makeResponse($response);
    }

    protected function isSecondRecurring(array $content)
    {
        return ($content[RequestFields::MODE] === Mode::STANDING_INSTRUCTIONS);
    }

    public function getBankingType($input) : string
    {
        switch ($input[RequestFields::PAYEE_ID])
        {
            case 'random_pid_corp':
                $bankingType = 'corporate';
                break;

            case 'random_payee_id_rec':
                $bankingType = 'recurring';
                break;

            default:
                $bankingType = 'retail';
                break;
        }

        return $bankingType;
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($input);

        $responseArray = $this->createVerifyResponseArray($input);

        //
        // Sending back the SI reference numbers in the response
        //
        if (empty($input[RequestFields::SI_REFERENCE_NUMBER]) === false)
        {
            $responseArray[ResponseFields::SI_REFERENCE_ID] = $input[RequestFields::SI_REFERENCE_NUMBER];
        }
        if (empty($input[RequestFields::SI_AUTO_PAY_AMOUNT]) === false)
        {
            $responseArray[ResponseFields::SI_AUTO_PAY_AMOUNT] = $input[RequestFields::SI_AUTO_PAY_AMOUNT];
        }

        $response = $this->createXmlResponse($responseArray);

        return $this->makeResponse($response);
    }

    protected function createPostData(array $input)
    {
        $response = [
            ResponseFields::PAYMENT_ID    => $input[RequestFields::PAYMENT_ID],
            ResponseFields::ITEM_CODE     => strtoupper($input[RequestFields::ITEM_CODE]),

            // If the amount is not sent, it's a registration-only emandate auth request
            ResponseFields::AMOUNT        => $input[RequestFields::AMOUNT] ?? 'null',
            ResponseFields::CURRENCY_CODE => $input[RequestFields::CURRENCY_CODE],
            ResponseFields::PAID          => Confirmation::YES,
        ];

        if ($input[RequestFields::CONFIRMATION] === Confirmation::YES)
        {
            $response[ResponseFields::BANK_PAYMENT_ID] = 9999999999;
        }

        if ((isset($input[RequestFields::AMOUNT]) === true) and ($input[RequestFields::AMOUNT] === '600'))
        {
            $response[ResponseFields::BANK_PAYMENT_ID] = 'CFL-000001118877-PRO';
        }

        if ((isset($input[RequestFields::SI]) === true) and
            ($input[RequestFields::SI] === Confirmation::YES))
        {
            $response[ResponseFields::SI_SCHEDULE_ID] = uniqid();
            $response[ResponseFields::SI_STATUS]      = Confirmation::YES;
            $response[ResponseFields::SI_MESSAGE]     = 'SUC';

            // For mandate registration-only auth request, the payment status is 'null'
            $response[ResponseFields::PAID]           = 'null';

            if ((isset($input[RequestFields::AMOUNT])) and ($input[RequestFields::AMOUNT] > 0))
            {
                $response[ResponseFields::PAID] = 'Y';

                return $response;
            }
            // Since there's no hot payment, BID won't exist
            unset($response[ResponseFields::BANK_PAYMENT_ID]);
        }

        return $response;
    }

    protected function formatResponseData(array $postData, array $input)
    {
        $httpQuery = http_build_query($postData);

        $bankingType = $this->getBankingType($input);

        $aes = $this->getAesCrypto($input);

        $content['ES'] = base64_encode($aes->encryptString($httpQuery));

        // response as sent back for corporate payment
        if ($bankingType === 'corporate')
        {
            $content['Payopt'] = 'ICI';

            $content['var1']   = 'xyz';
        }

        $this->content($content, 'hash');

        return $content;
    }

    protected function getAesCrypto($input)
    {
        $bankingType = $this->getBankingType($input);

        $masterKey = $this->getGatewayInstance($bankingType)->getSecret();

        return new Base\AESCrypto(AES::MODE_ECB, $masterKey);
    }

    protected function decryptData(array $input)
    {
        $aes = $this->getAesCrypto($input);

        $decryptedString = $aes->decryptString(base64_decode($input['ES']));

        if ($decryptedString === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Encrypted string not decryptable');
        }

        // Removing the %22 tags in the return URL
        $string = str_replace('%22', '', $decryptedString);

        parse_str($string, $decryptedData);

        return $decryptedData;
    }

    protected function createXmlResponse(array $responseArray)
    {
        $this->content($responseArray, 'verify');

        if (is_array($responseArray) === false)
        {
            return $responseArray;
        }

        $responseArray = array_flip($responseArray);

        $xml = new \SimpleXMLElement('<VerifyOutput/>');

        array_walk_recursive($responseArray, array ($xml, 'addAttribute'));

        $response = $xml->asXML();

        return $response;
    }

    protected function createVerifyResponseArray(array $input)
    {
        $bankingType = $this->getBankingType($input);

        if ($bankingType === 'corporate')
        {
            return [
                ResponseFields::BILL_REF_NUM => $input[RequestFields::PAYMENT_ID],
                ResponseFields::PAYMENTID    => '..',
                ResponseFields::CONSUMER_CODE => $input[RequestFields::ITEM_CODE],
                ResponseFields::UC_AMOUNT    => $input[RequestFields::AMOUNT],
                ResponseFields::STATUS       => Status::Y,
                ResponseFields::CURRENCY     => $input[RequestFields::CURRENCY_CODE],
            ];
        }

        $responseArray = [
            ResponseFields::ITEM_CODE    => $input[RequestFields::ITEM_CODE],
            ResponseFields::PAYMENT_ID   => $input[RequestFields::PAYMENT_ID],
            ResponseFields::CURRENCY     => $input[RequestFields::CURRENCY_CODE],
            ResponseFields::PAYMENT_DATE => $input[RequestFields::PAYMENT_DATE],
            ResponseFields::AMOUNT       => $input[RequestFields::AMOUNT],
            ResponseFields::STATUS       => Status::SUCCESS,
        ];

        if ((empty($input[RequestFields::SI]) === false) and
            ($input[RequestFields::SI] = Status::Y))
        {
            $emandateFields = [
                ResponseFields::STATUS          => Status::SI_REGISTRATION_SUCCESS,
                ResponseFields::SI_REFERENCE_ID => 123123123,
            ];

            $responseArray = array_merge($responseArray, $emandateFields);
        }

        return $responseArray;
    }

    public function getCheckerCallbackForPaymentFromBank($payment)
    {
        $url = $this->route->getPublicCallbackUrlWithHash($payment['public_id']);

        $response = [
                ResponseFields::PAYMENT_ID      => $payment['id'],
                ResponseFields::ITEM_CODE       => $payment['id'],

                // If the amount is not sent, it's a registration-only emandate auth request
                ResponseFields::AMOUNT          => $payment['amount'] / 100,
                ResponseFields::PAID            => Confirmation::YES,
                ResponseFields::BANK_PAYMENT_ID => 9999999999
        ];

        $httpQuery = http_build_query($response);

        $masterKey = $this->getGatewayInstance('corporate')->getSecret();

        $aes = new Base\AESCrypto(AES::MODE_ECB, $masterKey);

        $content['ES'] = base64_encode($aes->encryptString($httpQuery));

        return [$content, $url];
    }
}
