<?php

namespace RZP\Gateway\Wallet\Jiomoney\Mock;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Constants\HashAlgo;
use RZP\Gateway\Base;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Gateway\Wallet\Jiomoney;
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

        $content = $this->getAuthorizeResponse($input);

        $this->content($content);

        $gatewayResponse = $this->getGatewayResponse($content);

        $redirectUrl .= '?' . http_build_query($gatewayResponse);

        return \Redirect::to($redirectUrl);
    }

    public function refund($input)
    {
        $input = json_decode($input, true);

        parent::refund($input);

        $this->verifyRefundHash($input);

        $this->validateActionInput($input, 'refund');

        $this->content($input, 'validateRefund');

        $content = $this->getRefundResponse($input);

        $this->content($content, 'refund');

        $gatewayResponse = $this->getGatewayResponse($content);

        return $this->makeResponse($gatewayResponse);
    }

    public function verify($input)
    {
        parent::verify($input);

        $decodedInput = json_decode($input, true);

        $response = [];

        /**
         * CHECKPAYMENTSTATUS API has a string request so json_decode returns
         * null in this case.
         */
        if ($decodedInput !== null)
        {
            $this->verifyStatusQueryHash($decodedInput);

            $this->validateActionInput($decodedInput, 'status_query');

            $response = $this->getStatusQueryResponse($decodedInput);

            $this->content($response, 'status_query');
        }
        else
        {
            $input = explode('~', $input);

            $checkTxnStatusRequestFields = [
                RequestFields::APINAME,
                RequestFields::MODE,
                RequestFields::REQUEST_ID,
                RequestFields::STARTDATETIME,
                RequestFields::ENDDATETIME,
                RequestFields::MERCHANT_ID,
                RequestFields::PAYMENT_ID,
                RequestFields::CHECKSUM
            ];

            $input = array_combine($checkTxnStatusRequestFields, $input);

            $this->verifyCheckTxnStatusRequestHash($input);

            $this->validateActionInput($input, 'check_txn_status');

            $response = $this->getCheckTxnStatusResponse($input);

            $this->content($response, 'checkpaymentstatus');
        }

        return $this->makeResponse($response);
    }

    protected function getGatewayResponse(array $content): array
    {
        if ($content[ResponseFields::RESPONSE_DESCRIPTION] !== 'BAD_REQUEST')
        {
            $content[ResponseFields::CHECKSUM] = $this->generateHash($content);
        }

        $gatewayResponse = [];
        $gatewayResponse['response'] = implode('|', array_values($content));

        return $gatewayResponse;
    }

    protected function getAuthorizeResponse(array $input)
    {
        $date = $this->getFormattedTimeStamp(
                        Carbon::now()->getTimestamp(),
                        self::TXN_DATE_FORMAT);

        $paymentId = $input[RequestFields::getFormatted(RequestFields::TRANSACTION, RequestFields::PAYMENT_ID)];

        $amount = $input[RequestFields::getFormatted(RequestFields::TRANSACTION, RequestFields::AMOUNT)];

        return [
            ResponseFields::STATUS_CODE             => StatusCode::SUCCESS,
            ResponseFields::CLIENT_ID               => $input[RequestFields::CLIENT_ID],
            ResponseFields::MERCHANT_ID             => $input[RequestFields::MERCHANT_ID],
            ResponseFields::CUSTOMER_ID             => 'NA',
            ResponseFields::PAYMENT_ID              => $paymentId,
            ResponseFields::GATEWAY_PAYMENT_ID      => $this->getJioMoneyTxnId(),
            ResponseFields::AMOUNT                  => $amount,
            ResponseFields::RESPONSE_CODE           => 'SUCCESS',
            ResponseFields::RESPONSE_DESCRIPTION    => 'APPROVED',
            ResponseFields::DATE                    => $date,
            ResponseFields::CARD_NUMBER             => 'NA',
            ResponseFields::CARD_TYPE               => 'JM',
            ResponseFields::CARD_NETWORK            => 'NA'
        ];
    }

    protected function getRefundResponse(array $input)
    {
        $date = $this->getFormattedTimeStamp(
                        Carbon::now()->getTimestamp(),
                        self::TXN_DATE_FORMAT);

        return [
            ResponseFields::STATUS_CODE             => StatusCode::SUCCESS,
            ResponseFields::CLIENT_ID               => $input[RequestFields::CLIENT_ID],
            ResponseFields::MERCHANT_ID             => $input[RequestFields::MERCHANT_ID],
            ResponseFields::CUSTOMER_ID             => 'NA',
            ResponseFields::PAYMENT_ID              => $input[RequestFields::TRANSACTION][RequestFields::PAYMENT_ID],
            ResponseFields::GATEWAY_PAYMENT_ID      => $this->getJioMoneyTxnId(),
            ResponseFields::AMOUNT                  => $input[RequestFields::TRANSACTION][RequestFields::AMOUNT],
            ResponseFields::RESPONSE_CODE           => 'SUCCESS',
            ResponseFields::RESPONSE_DESCRIPTION    => 'APPROVED',
            ResponseFields::DATE                    => $date,
            ResponseFields::CARD_NUMBER             => 'NA',
            ResponseFields::CARD_TYPE               => 'JM',
            ResponseFields::CARD_NETWORK            => 'NA'
        ];
    }

    protected function getCheckTxnStatusResponse(array $input): array
    {
        $date = $this->getFormattedTimeStamp(
                        Carbon::now()->getTimestamp(),
                        self::TXN_DATE_FORMAT);

        if ($input[RequestFields::APINAME] === 'CHECKPAYMENTSTATUS')
        {
            return $this->getCheckPaymentStatusResponse($input);
        }

        return $this->getVerifyRefundResponse($input);
    }

    protected function getVerifyRefundResponse(array $input): array
    {
        return [
            'RESPONSE' => [
                'RESPONSE_HEADER' => [
                    'STATUS' => 'SUCCESS',
                ],
                'GETREQUESTSTATUS' => [
                    'JM_TRAN_REF_NO' => '1001',
                    'TRAN_REF_NO'    => NULL,
                    'TXN_STATUS'     => 'SUCCESS',
                    'TXN_AMOUNT'     => '50000',
                    'REFUND_AMOUNT'  => '50000',
                    'REQUEST_TYPE'   => 'REFUND',
                    'ERROR_CODE'     => '000'
                ]
            ]
        ];
    }

    protected function getCheckPaymentStatusResponse(array $input): array
    {
        $date = $this->getFormattedTimeStamp(
                        Carbon::now()->getTimestamp(),
                        self::TXN_DATE_FORMAT);

        return [
            'RESPONSE' => [
                'RESPONSE_HEADER' => [
                    'STATUS' => 'SUCCESS',
                ],
                'CHECKPAYMENTSTATUS' => [
                    'MID'            => $input[RequestFields::MERCHANT_ID],
                    'TRAN_REF_NO'    => $input[RequestFields::PAYMENT_ID],
                    'JM_TRAN_REF_NO' => '100',
                    'TXN_AMOUNT'     => 50000,
                    'TXN_TIME_STAMP' => $date,
                    'CARD_NO'        => 'NA',
                    'TXN_TYPE'       => 'JM',
                    'TXN_STATUS'     => 'SUCCESS',
                    'ERROR_CODE'     => '000'
                ]
            ]
        ];
    }

    protected function getStatusQueryResponse(array $input)
    {
        return [
            'response_header' => [
                'version'    => '1.0',
                'api_name'   => 'STATUSQUERY',
                'api_status' => '1',
                'api_msg'    => 'Transaction Fetched Successfully'
            ],
            'payload_data' => [
                'client_id'      => $input['payload_data']['client_id'],
                'merchant_id'    => $input['payload_data']['merchant_id'],
                'tran_ref_no'    => $input['payload_data']['tran_ref_no'],
                'jm_tran_ref_no' => $this->getJioMoneyTxnId(),
                'txn_amount'     => '500.00',
                'txn_type'       => 'JM',
                'txn_status'     => 'SUCCESS'
            ]
        ];
    }

    protected function getJioMoneyTxnId()
    {
        return uniqid();
    }

    protected function getFormattedTimeStamp($timestamp, $format)
    {
        return Carbon::createFromTimestamp($timestamp, Timezone::IST)->format($format);
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

    protected function verifyCheckTxnStatusRequestHash($content)
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
