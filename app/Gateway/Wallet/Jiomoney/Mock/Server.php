<?php

namespace RZP\Gateway\Wallet\Jiomoney\Mock;

use Carbon\Carbon;

use RZP\Constants\HashAlgo;
use RZP\Gateway\Base;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Gateway\Wallet\Jiomoney;
use RZP\Gateway\Wallet\Jiomoney\TestAmount;
use RZP\Gateway\Wallet\Jiomoney\StatusCode;
use RZP\Gateway\Wallet\Jiomoney\ResponseFields;
use RZP\Gateway\Wallet\Jiomoney\RequestFields;

class Server extends Base\Mock\Server
{
    const TXN_DATE_FORMAT = 'YmdHis';

    public function authorize($input)
    {
        $this->validateActionInput($input, 'authorize');

        $this->verifyAuthorizeHash($input);

        $redirectUrl = $input[RequestFields::CALLBACK_URL];

        if ($input[RequestFields::TRANSACTION . '.' . RequestFields::AMOUNT] === TestAmount::FAIL_PAYMENT_AMOUNT)
        {
            $content = $this->getAuthorizedFailedResponse($input);
        }
        else
        {
            $content = $this->getAuthorizeSuccessResponse($input);
        }

        $content[ResponseFields::CHECKSUM] = $this->generateHash($content);

        $gatewayResponse = [];
        $gatewayResponse['response'] = implode('|', array_values($content));

        $redirectUrl .= '?' . http_build_query($gatewayResponse);

        return \Redirect::to($redirectUrl);
    }

    public function refund($input)
    {
        $input = json_decode($input, true);

        parent::refund($input);

        $this->verifyRefundHash($input);

        $this->validateActionInput($input, 'refund');

        if ($input[RequestFields::TRANSACTION][RequestFields::AMOUNT] === TestAmount::FAIL_REFUND_AMOUNT)
        {
            $content = $this->getRefundFailedResponse($input);
        }
        else
        {
            $content = $this->getRefundSuccessResponse($input);
        }

        $content[ResponseFields::CHECKSUM] = $this->generateHash($content);

        $gatewayResponse = [];
        $gatewayResponse['response'] = implode('|', array_values($content));

        return $this->makeResponse($gatewayResponse);
    }

    public function verify($input)
    {
        parent::verify($input);

        $decodedInput = json_decode($input, true);

        $response = [];

        if ($decodedInput !== null)
        {
            $this->verifyStatusQueryHash($decodedInput);

            $this->validateActionInput($decodedInput, 'status_query');

            $response = $this->getStatusQueryResponse($decodedInput);
        }
        else
        {
            $input = explode('~', $input);

            $checkPaymentStatusApiFields = [
                RequestFields::APINAME,
                RequestFields::MODE,
                RequestFields::REQUEST_ID,
                RequestFields::STARTDATETIME,
                RequestFields::ENDDATETIME,
                RequestFields::MERCHANT_ID,
                RequestFields::PAYMENT_ID,
                RequestFields::CHECKSUM
            ];

            $input = array_combine($checkPaymentStatusApiFields, $input);

            $this->verifyCheckPaymentStatusHash($input);

            $this->validateActionInput($input, 'check_payment_status');

            $response = $this->getCheckPaymentStatusResponse($input);
        }

        return $this->makeResponse($response);
    }

    protected function getAuthorizedFailedResponse(array $input)
    {
        return
        [
            ResponseFields::STATUS_CODE             => StatusCode::INTERNAL_ERROR,
            ResponseFields::CLIENT_ID               => $input[RequestFields::CLIENT_ID],
            ResponseFields::MERCHANT_ID             => $input[RequestFields::MERCHANT_ID],
            ResponseFields::CUSTOMER_ID             => 'NA',
            ResponseFields::PAYMENT_ID              => $input[RequestFields::TRANSACTION . '.' . RequestFields::PAYMENT_ID],
            ResponseFields::GATEWAY_PAYMENT_ID      => $this->getJioMoneyTxnId(),
            ResponseFields::AMOUNT                  => $input[RequestFields::TRANSACTION . '.' . RequestFields::AMOUNT],
            ResponseFields::RESPONSE_CODE           => 'FAILED',
            ResponseFields::RESPONSE_DESCRIPTION    => 'NA',
            ResponseFields::DATE                    => $this->getFormattedTimeStamp(Carbon::now('Asia/Kolkata')->timestamp,
                                                            self::TXN_DATE_FORMAT),
            ResponseFields::CARD_NUMBER             => 'NA',
            ResponseFields::CARD_TYPE               => 'NA',
            ResponseFields::CARD_NETWORK            => 'NA'
        ];
    }

    protected function getAuthorizeSuccessResponse(array $input)
    {
        return
        [
            ResponseFields::STATUS_CODE             => StatusCode::SUCCESS,
            ResponseFields::CLIENT_ID               => $input[RequestFields::CLIENT_ID],
            ResponseFields::MERCHANT_ID             => $input[RequestFields::MERCHANT_ID],
            ResponseFields::CUSTOMER_ID             => 'NA',
            ResponseFields::PAYMENT_ID              => $input[RequestFields::TRANSACTION . '.' . RequestFields::PAYMENT_ID],
            ResponseFields::GATEWAY_PAYMENT_ID      => $this->getJioMoneyTxnId(),
            ResponseFields::AMOUNT                  => $input[RequestFields::TRANSACTION . '.' . RequestFields::AMOUNT],
            ResponseFields::RESPONSE_CODE           => 'SUCCESS',
            ResponseFields::RESPONSE_DESCRIPTION    => 'APPROVED',
            ResponseFields::DATE                    => $this->getFormattedTimeStamp(Carbon::now('Asia/Kolkata')->timestamp,
                                                            self::TXN_DATE_FORMAT),
            ResponseFields::CARD_NUMBER             => 'NA',
            ResponseFields::CARD_TYPE               => 'JM',
            ResponseFields::CARD_NETWORK            => 'NA'
        ];
    }

    protected function getRefundFailedResponse(array $input)
    {
        return
        [
            ResponseFields::STATUS_CODE             => StatusCode::INTERNAL_ERROR,
            ResponseFields::CLIENT_ID               => $input[RequestFields::CLIENT_ID],
            ResponseFields::MERCHANT_ID             => $input[RequestFields::MERCHANT_ID],
            ResponseFields::CUSTOMER_ID             => 'NA',
            ResponseFields::PAYMENT_ID              => $input[RequestFields::TRANSACTION][RequestFields::PAYMENT_ID],
            ResponseFields::GATEWAY_PAYMENT_ID      => $this->getJioMoneyTxnId(),
            ResponseFields::AMOUNT                  => $input[RequestFields::TRANSACTION][RequestFields::AMOUNT],
            ResponseFields::RESPONSE_CODE           => 'FAILED',
            ResponseFields::RESPONSE_DESCRIPTION    => 'NA',
            ResponseFields::DATE                    => $this->getFormattedTimeStamp(Carbon::now('Asia/Kolkata')->timestamp,
                                                            self::TXN_DATE_FORMAT),
            ResponseFields::CARD_NUMBER             => 'NA',
            ResponseFields::CARD_TYPE               => 'JM',
            ResponseFields::CARD_NETWORK            => 'NA'
        ];
    }

    protected function getRefundSuccessResponse(array $input)
    {
        return
        [
            ResponseFields::STATUS_CODE             => StatusCode::SUCCESS,
            ResponseFields::CLIENT_ID               => $input[RequestFields::CLIENT_ID],
            ResponseFields::MERCHANT_ID             => $input[RequestFields::MERCHANT_ID],
            ResponseFields::CUSTOMER_ID             => 'NA',
            ResponseFields::PAYMENT_ID              => $input[RequestFields::TRANSACTION][RequestFields::PAYMENT_ID],
            ResponseFields::GATEWAY_PAYMENT_ID      => $this->getJioMoneyTxnId(),
            ResponseFields::AMOUNT                  => $input[RequestFields::TRANSACTION][RequestFields::AMOUNT],
            ResponseFields::RESPONSE_CODE           => 'SUCCESS',
            ResponseFields::RESPONSE_DESCRIPTION    => 'APPROVED',
            ResponseFields::DATE                    => $this->getFormattedTimeStamp(Carbon::now('Asia/Kolkata')->timestamp,
                                                            self::TXN_DATE_FORMAT),
            ResponseFields::CARD_NUMBER             => 'NA',
            ResponseFields::CARD_TYPE               => 'JM',
            ResponseFields::CARD_NETWORK            => 'NA'
        ];
    }

    protected function getCheckPaymentStatusResponse(array $input)
    {
        return
        [
            'RESPONSE' => [
                'RESPONSE_HEADER' => [
                    'STATUS' => 'SUCCESS',
                ],
                'CHECKPAYMENTSTATUS' => [
                    'MID' => $input[RequestFields::MERCHANT_ID],
                    'TRAN_REF_NO' => $input[RequestFields::PAYMENT_ID],
                    'JM_TRAN_REF_NO' => '100',
                    'TXN_TIME_STAMP' => $this->getFormattedTimeStamp(Carbon::now('Asia/Kolkata')->timestamp,
                                            self::TXN_DATE_FORMAT),
                    'CARD_NO' => 'NA',
                    'TXN_TYPE' => 'JM',
                    'TXN_STATUS' => 'SUCCESS',
                    'ERROR_CODE' => '000'
                ]
            ]
        ];
    }

    protected function getStatusQueryResponse(array $input)
    {
        $txnNotFound = $input['request_header']['txn_not_found'] ?? false;

        if ($txnNotFound === true)
        {
            return $this->getTxnNotFoundResponse();
        }

        return $this->getTxnFoundResponse($input);
    }

    protected function getTxnNotFoundResponse()
    {
        return
        [
            'response_header' => [
                'version' => '1.0',
                'api_name' => 'STATUSQUERY',
                'api_status' => '0',
                'api_msg' => 'Transaction not found'
            ],
            'payload_data' => [
                'client_id' => null,
                'merchant_id' => null,
                'tran_ref_no' => null,
                'jm_tran_ref_no' => null,
                'txn_amount' => null,
                'txn_type' => null,
                'txn_status' => null
            ]
        ];
    }

    protected function getTxnFoundResponse(array $input)
    {
        return
        [
            'response_header' => [
                'version' => '1.0',
                'api_name' => 'STATUSQUERY',
                'api_status' => '1',
                'api_msg' => 'Transaction Fetched Successfully'
            ],
            'payload_data' => [
                'client_id' => $input['payload_data']['client_id'],
                'merchant_id' => $input['payload_data']['merchant_id'],
                'tran_ref_no' => $input['payload_data']['tran_ref_no'],
                'jm_tran_ref_no' => $this->getJioMoneyTxnId(),
                'txn_amount' => '5.00',
                'txn_type' => 'JM',
                'txn_status' => 'SUCCESS'
            ]
        ];
    }

    protected function getJioMoneyTxnId()
    {
        return uniqid();
    }

    protected function getFormattedTimeStamp($timestamp, $format)
    {
        return Carbon::createFromTimestamp($timestamp, 'Asia/Kolkata')->format($format);
    }

    protected function verifyAuthorizeHash($content)
    {
        $hashArray = [
            $content[RequestFields::CLIENT_ID],
            $content[RequestFields::TRANSACTION . '.' . RequestFields::AMOUNT],
            $content[RequestFields::TRANSACTION. '.' . RequestFields::PAYMENT_ID],
            $content[RequestFields::CHANNEL],
            $content[RequestFields::MERCHANT_ID],
            $content[RequestFields::TOKEN],
            $content[RequestFields::CALLBACK_URL],
            $content[RequestFields::TRANSACTION . '.' .RequestFields::TIMESTAMP],
            $content[RequestFields::TRANSACTION . '.' . RequestFields::TXN_TYPE]
        ];

        $hash = $this->generateHash($hashArray);

        assertTrue(hash_equals($hash, $content[RequestFields::CHECKSUM]));
    }

    protected function verifyRefundHash($content)
    {
        $hashArray = [
            $content[RequestFields::CLIENT_ID],
            $content[RequestFields::TRANSACTION][RequestFields::AMOUNT],
            $content[RequestFields::TRANSACTION][RequestFields::PAYMENT_ID],
            $content[RequestFields::CHANNEL],
            $content[RequestFields::MERCHANT_ID],
            $content[RequestFields::TOKEN],
            $content[RequestFields::CALLBACK_URL],
            $content[RequestFields::TRANSACTION][RequestFields::TIMESTAMP],
            $content[RequestFields::TRANSACTION][RequestFields::TXN_TYPE]
        ];

        $hash = $this->generateHash($hashArray);

        assertTrue(hash_equals($hash, $content[RequestFields::CHECKSUM]));
    }

    protected function verifyStatusQueryHash($content)
    {
        $hashArray = [
            $content['payload_data']['client_id'],
            $content['payload_data']['merchant_id'],
            $content['request_header']['api_name'],
            $content['payload_data']['tran_ref_no']
        ];

        $hash = $this->generateHash($hashArray);

        assertTrue(hash_equals($hash, $content['checksum']));
    }

    protected function verifyCheckPaymentStatusHash($content)
    {
        $hashArray = [
            $content[RequestFields::APINAME],
            $content[RequestFields::MODE],
            $content[RequestFields::REQUEST_ID],
            $content[RequestFields::STARTDATETIME],
            $content[RequestFields::ENDDATETIME],
            $content[RequestFields::MERCHANT_ID],
            $content[RequestFields::PAYMENT_ID]
        ];

        $hashString = $this->getGatewayInstance()->getStringToHash($hashArray, '~');

        $hash = $this->getGatewayInstance()->getHashOfString($hashString);

        assertTrue(hash_equals($hash, $content[RequestFields::CHECKSUM]));
    }
}
