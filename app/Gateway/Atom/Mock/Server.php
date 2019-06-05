<?php

namespace RZP\Gateway\Atom\Mock;

use RZP\Constants\Timezone;
use RZP\Exception;
use RZP\Gateway\Atom\AESCrypto;
use RZP\Gateway\Atom\VerifyRefundFields;
use Carbon\Carbon;
use RZP\Gateway\Atom\DateFormat;
use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Atom\Status;
use RZP\Gateway\Atom\AuthRequestFields;
use RZP\Gateway\Atom\AuthResponseFields;
use RZP\Gateway\Atom\VerifyRequestFields;
use RZP\Gateway\Atom\RefundRequestFields;
use RZP\Gateway\Atom\RefundResponseFields;
use RZP\Gateway\Atom\VerifyResponseFields;
use RZP\Gateway\Base\Repository;

class Server extends Base\Mock\Server
{
    public function __construct()
    {
        parent::__construct();

        $this->config = $this->app['config']->get('gateway.atom');
    }

    public function authorize($input)
    {
        $request = [
            'url'     => $this->route->getUrl('mock_atom_payment'),
            'content' => $input,
            'method'  => 'post',
        ];

        $this->request($request);

        return $this->makePostResponse($request);
    }

    public function bank($input)
    {
        parent::authorize($input);

        $this->validateActionInput($input, 'authorize');

        $this->verifySecureHash($input);

        $content = $this->createCallbackResponseArray($input);

        $callbackUrl = $input[AuthRequestFields::RETURN_URL] . '?' .
                       http_build_query($content);

        return $callbackUrl;
    }

    public function refund($input)
    {
        parent::refund($input);

        $this->validateActionInput($input, 'refund');

        $response = $this->getRefundResponseData($input);

        $response = array_flip($response);

        $xml = new \SimpleXMLElement('<Refund/>');

        array_walk_recursive($response, array ($xml, 'addChild'));

        $response = $xml->asXML();

        return $this->makeResponse($response);
    }

    public function verify($input)
    {
        parent::verify($input);

        if (isset($input['login']) === true)
        {
            $this->validateActionInput($input, 'verify_refund');

            $response = $this->getVerifyRefundResponseData($input);

            return $this->makeResponse($response);
        }
        else
        {
            $this->validateActionInput($input, 'verify');

            $response = $this->getVerifyResponseData($input);

            $xml = $this->getVerifyResponseXml($response);

            return $this->makeResponse($xml);
        }
    }

    protected function getRefundResponseData($input)
    {
        $content = [
            RefundResponseFields::MERCHANT_ID    => $input[RefundRequestFields::MERCHANT_ID],
            RefundResponseFields::TRANSACTION_ID => $input[RefundRequestFields::GATEWAY_TRANSACTION_ID],
            RefundResponseFields::AMOUNT         => $input[RefundRequestFields::REFUND_AMOUNT],
            RefundResponseFields::STATUS_CODE    => Status::FULL_REFUND_SUCCESS,
        ];

        // this is a hack to ensure gateway returns invalid date for one scenario and successful
        // for the other, to mimic the case when payment is between 12 to 12 10 AM.
        if ($input[RefundRequestFields::TRANSACTION_DATE] === '2018-11-04')
        {
            $content[RefundResponseFields::STATUS_MESSAGE] = 'Invalid transaction date';
        }
        else
        {
            $content[RefundResponseFields::STATUS_MESSAGE] = 'Full Refund initiated successfully';
        }

        $this->content($content, 'refund');

        return $content;
    }

    protected function verifySecureHash(array $content)
    {
        $actual = $this->getHashValueFromContent($content);

        $generated = $this->generateHash($content);

        $this->compareHashes($actual, $generated);
    }

    protected function createCallbackResponseArray($input)
    {
        $this->action = Action::CALLBACK;

        $format = DateFormat::CALLBACK;

        $date = $input[AuthRequestFields::DATE];

        $timestamp = Carbon::createFromFormat('d/m/Y H:i:s', $date, Timezone::IST)->timestamp;

        $callbackTime = Carbon::createFromTimeStamp($timestamp, Timezone::IST)->format($format);

        $response = [
            AuthResponseFields::GATEWAY_PAYMENT_ID  => (string) mt_rand(1111111, 9999999),
            AuthResponseFields::TRANSACTION_ID      => $input[AuthRequestFields::TRANSACTION_ID],
            AuthResponseFields::AMOUNT              => $input[AuthRequestFields::AMOUNT],
            AuthResponseFields::SURCHARGE           => '0',
            AuthResponseFields::PRODUCT_ID          => $input[AuthRequestFields::PRODUCT_ID],
            AuthResponseFields::DATE                => $callbackTime,
            AuthResponseFields::BANK_TRANSACTION_ID => (string) mt_rand(11111111, 99999999),
            AuthResponseFields::STATUS_CODE         => Status::SUCCESS,
            AuthResponseFields::CLIENT_CODE         => $input[AuthRequestFields::CLIENT_CODE],
            AuthResponseFields::BANK_NAME           => 'Atom Bank',
            AuthResponseFields::DISCRIMINATOR       => 'NB',
        ];

        $this->content($response, 'hash');

        $response[AuthResponseFields::SIGNATURE] = $this->generateHash($response, 'response');

        $this->content($response, 'callback');

        return $response;
    }

    protected function generateHash($content, $type = 'request')
    {
        $hashString = $this->getStringToHash($content, '', $type);

        return $this->getHashOfString($hashString, $type);
    }

    protected function getStringToHash($data, $glue = '', $type = 'request')
    {
        switch ($this->action)
        {
            case Action::AUTHORIZE:
                $data = $this->getCallbackRequestHashArray($data);
                break;

            case Action::CALLBACK:
                $data = $this->getCallbackResponseHashArray($data);
                break;

            default:
                throw new Exception\RuntimeException('Action not set correctly');
        }

        return implode($glue, $data);
    }

    public function getCallbackResponseHashArray($response)
    {
        $hashArray = [
            $response[AuthResponseFields::GATEWAY_PAYMENT_ID],
            $response[AuthResponseFields::TRANSACTION_ID],
            $response[AuthResponseFields::STATUS_CODE],
            $response[AuthResponseFields::PRODUCT_ID],
            $response[AuthResponseFields::DISCRIMINATOR],
            $response[AuthResponseFields::AMOUNT],
            $response[AuthResponseFields::BANK_TRANSACTION_ID],
        ];

        return $hashArray;
    }

    public function getHashOfString($string, $type = 'request')
    {
        $secret = $this->getSecret($type);

        return hash_hmac(HashAlgo::SHA512, $string, $secret);
    }

    public function getSecret()
    {
        if ($this->action === Action::AUTHORIZE)
        {
            $secret = $this->config['test_authorize_hash_secret'];
        }
        else
        {
            $secret = $this->config['test_callback_hash_secret'];
        }

        return $secret;
    }

    protected function getCallbackRequestHashArray($content)
    {
        $hashArray = [
            $content[AuthRequestFields::LOGIN],
            $content[AuthRequestFields::PASSWORD],
            $content[AuthRequestFields::TRANSACTION_TYPE],
            $content[AuthRequestFields::PRODUCT_ID],
            $content[AuthRequestFields::TRANSACTION_ID],
            $content[AuthRequestFields::AMOUNT],
            $content[AuthRequestFields::TRANSACTION_CURRENCY],
        ];

        return $hashArray;
    }


    protected function compareHashes($actual, $generated)
    {
        if (hash_equals($actual, $generated) === false)
        {
            throw new Exception\RuntimeException('Failed checksum verification');
        }
    }

    protected function getHashValueFromContent(array $content)
    {
        switch ($this->action)
        {
            case Action::AUTHORIZE:
                return $content[AuthRequestFields::SIGNATURE];

            default:
                throw new Exception\RuntimeException('Action not set correctly');
        }
    }

    protected function makeResponse($xml)
    {
        $response = \Response::make($xml);

        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        if ($xml === 'status-code = 421')
        {
            $response = \Response::make($xml);

            $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
            $response->headers->set('Cache-Control', 'no-cache');
            $response->setStatusCode(421);
        }

        return $response;
    }

    public function getVerifyResponseData($input)
    {
        $response = [
            VerifyResponseFields::MERCHANT_ID            => $input[VerifyRequestFields::MERCHANT_ID],
            VerifyResponseFields::TRANSACTION_ID         => $input[VerifyRequestFields::TRANSACTION_ID],
            VerifyResponseFields::AMOUNT                 => $input[VerifyRequestFields::AMOUNT],
            VerifyResponseFields::STATUS                 => 'SUCCESS',
            VerifyResponseFields::BANK_TRANSACTION_ID    => (string) mt_rand(11111111, 99999999),
            VerifyResponseFields::BANK_NAME              => 'random_bank_name',
            VerifyResponseFields::GATEWAY_TRANSACTION_ID => (string) mt_rand(1111111, 9999999),
        ];

        $this->content($response);

        return $response;
    }

    public function getVerifyRefundResponseData($input)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
            <REFUNDSTATUS>
            <ERRORCODE>00</ERRORCODE>
            <MESSAGE>Refund Found</MESSAGE>
            <DETAILS>
            <REFUND>
            <TXNID>100001470088</TXNID>
            <PRODUCT>NSE</PRODUCT>
            <REFUNDAMOUNT>1.0000</REFUNDAMOUNT>
            <REFUNDINITIATEDATE>2018-07-26</REFUNDINITIATEDATE>
            <REFUNDPROCESSDATE></REFUNDPROCESSDATE>
            <REMARKS></REMARKS>
            <MEREFUNDREF>test1</MEREFUNDREF>
            </REFUND>
            </DETAILS>
            </REFUNDSTATUS>';

        $this->content($xml, 'verify_refund');

        if ($xml === 'status-code = 421')
        {
            return $xml;
        }

        $crypto = $this->getEncryptor();

        $data = $crypto->encryptString($xml);

        return $data;
    }

    public function getEncryptor()
    {
        $masterKey = $this->config['test_response_encryption_key'];

        $salt = $this->config['test_merchant_id'];

        return new AESCrypto($masterKey, $salt);
    }

    public function getVerifyResponseXml($response)
    {
        $xml = '
        <?xml version="1.0" encoding="UTF-8" ?>
            <VerifyOutput
                MerchantID="' . $response[VerifyResponseFields::MERCHANT_ID] . '"
                MerchantTxnID="' . $response[VerifyResponseFields::TRANSACTION_ID] . '"
                AMT="' . $response[VerifyResponseFields::AMOUNT] . '"
                VERIFIED="' . $response[VerifyResponseFields::STATUS] . '"
                BID="' . $response[VerifyResponseFields::BANK_TRANSACTION_ID] . '"
                bankname="' . $response[VerifyResponseFields::BANK_NAME] . '"
                atomtxnId="' . $response[VerifyResponseFields::GATEWAY_TRANSACTION_ID] . '"
            />';

        return $xml;
    }
}
