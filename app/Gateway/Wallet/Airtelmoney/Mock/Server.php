<?php

namespace RZP\Gateway\Wallet\Airtelmoney\Mock;

use Carbon\Carbon;


use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Wallet\Airtelmoney\ErrorCodes;
use RZP\Gateway\Wallet\Airtelmoney\Status;
use RZP\Gateway\Wallet\Airtelmoney\AuthFields;
use RZP\Gateway\Wallet\Airtelmoney\RefundFields;
use RZP\Gateway\Wallet\Airtelmoney\VerifyFields;
use RZP\Gateway\Wallet\Airtelmoney\ResponseCode;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateActionInput($input, 'authorize');

        $this->verifyHash($input);

        if ($input[AuthFields::AMOUNT] === TestAmount::FAIL_PAYMENT_AMOUNT)
        {
            $redirectUrl = $input[AuthFields::FAILURE_URL];

            $content = [
                AuthFields::STATUS                   => Status::FAILED,
                AuthFields::CODE                     => Constants::INVALID_MERCHANT_ID,
                AuthFields::MSG                      => ResponseCode::getResponseMessage('902'),
                AuthFields::TRANSACTION_REFERENCE_NO => $input[AuthFields::TRANSACTION_REFERENCE_NO],
            ];
        }
        else
        {
            $redirectUrl = $input[AuthFields::SUCCESS_URL];

            $content = [
                AuthFields::STATUS               => Status::SUCCESS,
                AuthFields::MSG                  => Constants::DUMMY_MSG,
                AuthFields::TRANSACTION_CURRENCY => 'INR',
                AuthFields::CODE                 => ErrorCodes::SUCCESS,
            ];
        }

        $hashContent = [
            AuthFields::MERCHANT_ID              => $input[AuthFields::MERCHANT_ID],
            AuthFields::TRANSACTION_ID           => $this->getArtlTxnId(),
            AuthFields::TRANSACTION_REFERENCE_NO => $input[AuthFields::TRANSACTION_REFERENCE_NO],
            AuthFields::TRANSACTION_AMOUNT       => number_format($input[AuthFields::AMOUNT], 2, '.', ''),
            AuthFields::TRANSACTION_DATE         => $this->getFormattedDate(
                Carbon::now(),
                Constants::TIME_FORMAT),
        ];

        $this->content($hashContent, 'callback');

        $content = array_merge($content, $hashContent);

        $content[AuthFields::HASH] = $this->generateHash($content, 'response');

        $this->content($hashContent, 'hash');

        $content = array_merge($content, $hashContent);

        $params = http_build_query($content);

        return \Redirect::to($redirectUrl . '?' . $params);
    }

    protected function getCallbackResponseHashArray($content)
    {
        if ($content[AuthFields::STATUS] === Status::SUCCESS)
        {
            $hashArray = [
                $content[AuthFields::MERCHANT_ID],
                $content[AuthFields::TRANSACTION_ID],
                $content[AuthFields::TRANSACTION_REFERENCE_NO],
                $content[AuthFields::TRANSACTION_AMOUNT],
                $content[AuthFields::TRANSACTION_DATE],
                $this->getGatewayInstance()->getSecret()
            ];
        }
        else
        {
            $hashArray = [
                $content[AuthFields::MERCHANT_ID],
                $content[AuthFields::TRANSACTION_REFERENCE_NO],
                $content[AuthFields::TRANSACTION_AMOUNT],
                $this->getGatewayInstance()->getSecret(),
                $content[AuthFields::CODE],
                $content[AuthFields::STATUS]
            ];
        }

        return $hashArray;
    }

    public function verify($input)
    {
        parent::verify($input);

        $request = json_decode($input, true);

        $this->validateActionInput($request);

        $this->verifySecureHash($request);

        $response = $this->getVerifyResponse($request);

        return $this->makeResponse($response);
    }

    protected function getVerifyResponse($input)
    {
        $merchantId = $input[VerifyFields::MERCHANT_ID];

        $verifyArray = [
            VerifyFields::STATUS             => Status::SUCCESS,
            VerifyFields::TRANSACTION_ID     => $this->getArtlTxnId(),
            VerifyFields::TRANSACTION_DATE   => $input[VerifyFields::TRANSACTION_DATE],
            VerifyFields::TRANSACTION_AMOUNT => $input[VerifyFields::AMOUNT],
        ];

        if ($input[VerifyFields::AMOUNT] === TestAmount::FAIL_VERIFY_AMOUNT)
        {
            $response = [
                VerifyFields::MERCHANT_ID              => $merchantId,
                VerifyFields::TRANSACTION              => null,
                VerifyFields::TRANSACTION_REFERENCE_NO => $input[VerifyFields::TRANSACTION_REFERENCE_NO],
                VerifyFields::MESSAGE_TEXT             => 'Transaction not present',
                VerifyFields::CODE                     => '1',
                VerifyFields::ERROR_CODE               => '910'
            ];
        }
        else
        {
            $response = [
                VerifyFields::MERCHANT_ID              => $merchantId,
                VerifyFields::TRANSACTION              => array($verifyArray),
                VerifyFields::TRANSACTION_REFERENCE_NO => $input[VerifyFields::TRANSACTION_REFERENCE_NO],
                VerifyFields::MESSAGE_TEXT             => 'Success',
                VerifyFields::CODE                     => '0',
                VerifyFields::ERROR_CODE               => ErrorCodes::SUCCESS,
            ];
        }

        $this->content($response);
        $response[VerifyFields::HASH] = $this->generateHash($response, 'response');

        return json_encode($response);
    }

    protected function getVerifyRequestHashArray($data)
    {
        $hashArray = [
            $data[VerifyFields::MERCHANT_ID],
            $data[VerifyFields::TRANSACTION_REFERENCE_NO],
            $data[VerifyFields::AMOUNT],
            $data[VerifyFields::TRANSACTION_DATE],
            $this->getGatewayInstance()->getSecret()
        ];

        return $hashArray;
    }

    protected function verifySecureHash(array $content)
    {
        $actual = $this->getHashValueFromContent($content);

        $generated = $this->generateHash($content);

        $this->compareHashes($actual, $generated);
    }

    protected function generateHash($content, $type = 'request')
    {
        $hashString = $this->getStringToHash($content, '#', $type);

        return $this->getHashOfString($hashString);
    }

    protected function compareHashes($actual, $generated)
    {
        if (hash_equals($actual, $generated) === false)
        {
            throw new Exception\RuntimeException('Failed checksum verification');
        }
    }

    protected function getStringToHash($data, $glue = '', $type = 'request')
    {
        switch ($this->action)
        {
            case Action::AUTHORIZE:
                $data = ($type === 'response') ? $this->getCallbackResponseHashArray($data) :
                    $this->getCallbackRequestHashArray($data);
                break;

            case Action::VERIFY:
                $data = ($type === 'response') ? $this->getVerifyResponseHashArray($data) :
                    $this->getVerifyRequestHashArray($data);
                break;

            case Action::REFUND:
                $data = ($type === 'response') ? $this->getRefundResponseHashArray($data) :
                    $this->getRefundRequestHashArray($data);
                break;

            default:
                throw new Exception\RuntimeException('Action not set correctly');
        }

        return implode($glue, $data);
    }

    protected function getVerifyResponseHashArray($content)
    {
        $merchantId = $content[VerifyFields::MERCHANT_ID];

        if ($content[VerifyFields::TRANSACTION] === [])
        {
            $json = '[]';
        }
        else
        {
            $verifyArray = $content[VerifyFields::TRANSACTION][0];

            $json = '[' . json_encode($verifyArray) . ']';
        }

        $hashArray = [
            $merchantId,
            $json,
            $content[VerifyFields::ERROR_CODE],
            $this->getGatewayInstance()->getSecret()
        ];
        return $hashArray;
    }

    protected function getHashValueFromContent(array $content)
    {
        switch ($this->action)
        {
            case Action::AUTHORIZE:
                return $content[AuthFields::HASH];

            case Action::VERIFY:
                return $content[VerifyFields::HASH];

            case Action::REFUND:
                return $content[RefundFields::HASH];

            default:
                throw new Exception\RuntimeException('Action not set correctly');
        }
    }

    public function refund($input)
    {
        parent::refund($input);

        $request = json_decode($input, true);

        $this->validateActionInput($request);

        $this->verifySecureHash($request);

        $response = $this->createRefundResponse($request);

        return $this->makeResponse($response);
    }

    protected function getArtlTxnId()
    {
        return uniqid();
    }

    protected function verifyHash(array $content)
    {
        $hashArray = [
            $content['MID'],
            $content['TXN_REF_NO'],
            $content['AMT'],
            $content['DATE'],
            $content['service'],
            $this->getSecret(),
        ];

        $hashString = implode('#', $hashArray);

        $hash = $this->getHashOfString($hashString);

        assertTrue(hash_equals($hash, $content['HASH']));
    }

    protected function getHashOfString($hashString)
    {
        return hash(HashAlgo::SHA512, $hashString, false);
    }

    protected function makeResponse($json)
    {
        $response = parent::makeResponse($json);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }

    protected function makeXmlResponse($json)
    {
        $response = parent::makeResponse($json);

        $response->headers->set('Content-Type', 'text/xml; charset=UTF-8');

        return $response;
    }

    protected function getFormattedDate($date, $format)
    {
        return $date->format($format);
    }

    protected function generateXMLResponse($content)
    {
        $content = array_flip($content);
        $xml = new \SimpleXMLElement('<wallet/>');
        array_walk_recursive($content, array($xml, 'addChild'));
        return ($xml->asXML());
    }

    protected function getRefundRequestHashArray($data)
    {
        $hashArray = [
            $data[RefundFields::MERCHANT_ID],
            $data[RefundFields::TRANSACTION_ID],
            $data[RefundFields::AMOUNT],
            $data[RefundFields::TRANSACTION_DATE],
            $this->getGatewayInstance()->getSecret()
        ];

        return $hashArray;
    }

    protected function createRefundResponse($request)
    {
        $date = Carbon::createFromFormat(Constants::TIME_FORMAT,
                                         $request[VerifyFields::TRANSACTION_DATE])->toDateTimeString();

        if($request[RefundFields::AMOUNT] === TestAmount::FAIL_REFUND_AMOUNT)
        {
            $data = [
                RefundFields::MERCHANT_ID      => $request[RefundFields::MERCHANT_ID],
                RefundFields::ERROR_CODE       => Constants::MERCHANT_ID_NOT_FOUND,
                RefundFields::AMOUNT           => $request[RefundFields::AMOUNT],
                RefundFields::STATUS           => Status::FAILED,
                RefundFields::SESSION_ID       => $request[RefundFields::SESSION_ID],
                RefundFields::TRANSACTION_DATE => $date,
                RefundFields::MESSAGE_TEXT     => 'Airtel Payment Bank Transaction Id not found for given Merchant Id',
                RefundFields::CODE             => '1'
            ];

            $this->content($data);

            $data[RefundFields::HASH] = $this->generateHash($data, 'response');

            unset($data[RefundFields::AMOUNT]);
            unset($data[RefundFields::TRANSACTION_DATE]);
        }
        else
        {
            $data = [
                RefundFields::MERCHANT_ID      => $request[RefundFields::MERCHANT_ID],
                RefundFields::ERROR_CODE       => Constants::SUCCESS,
                RefundFields::AMOUNT           => $request[RefundFields::AMOUNT],
                RefundFields::TRANSACTION_ID   => $this->getArtlTxnId(),
                RefundFields::TRANSACTION_DATE => $date,
                RefundFields::STATUS           => Status::SUCCESS,
                RefundFields::SESSION_ID       => $request[RefundFields::SESSION_ID],
                RefundFields::MESSAGE_TEXT     => 'Transaction Created Successfully',
                RefundFields::CODE             => '0'
            ];

            $this->content($data);

            $data[RefundFields::HASH] = $this->generateHash($data, 'response');
        }

        return json_encode($data);
    }

    protected function getRefundResponseHashArray($content)
    {
        if( isset($content[RefundFields::TRANSACTION_ID]) === true)
        {
            $hashArray = [
                $content[RefundFields::MERCHANT_ID],
                $content[RefundFields::ERROR_CODE],
                $content[RefundFields::AMOUNT],
                $content[RefundFields::TRANSACTION_ID],
                $content[RefundFields::TRANSACTION_DATE],
                $content[RefundFields::STATUS],
                $this->getGatewayInstance()->getSecret()
            ];

        }
        else
        {
            $hashArray = [
                $content[RefundFields::MERCHANT_ID],
                $content[RefundFields::ERROR_CODE],
                $content[RefundFields::AMOUNT],
                $content[RefundFields::TRANSACTION_DATE],
                $content[RefundFields::STATUS],
                $this->getGatewayInstance()->getSecret()
            ];

        }
        return $hashArray;
    }
}
