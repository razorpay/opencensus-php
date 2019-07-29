<?php

namespace RZP\Gateway\Netbanking\Federal;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Netbanking\Base;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Models\Payment\Verify\Action as VerifyAction;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    const CHECKSUM_ATTRIBUTE = ResponseFields::HASH;

    protected $sortRequestContent = false;

    protected $gateway = 'netbanking_federal';

    protected $bank = 'federal';

    const VERIFY_TO_CALLBACK_STATUS = [
        Status::SUCCESS => Status::YES,
        Status::NO      => Status::NO,
        Status::ERROR   => Status::NO,
    ];

    protected $map = [
        RequestFields::AMOUNT     => Base\Entity::AMOUNT,
        RequestFields::PAYMENT_ID => Base\Entity::PAYMENT_ID,
        RequestFields::ITEM_CODE  => Base\Entity::CAPS_PAYMENT_ID
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getAuthorizeRequestData($input);

        $entityAttributes = $this->getEntityAttributes($input);

        $this->createGatewayPaymentEntity($entityAttributes);

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'gateway'          => $this->gateway,
                'gateway_response' => $content,
                'payment_id'       => $input['payment']['id']
            ]
        );

        if ((isset($content[ResponseFields::HASH]) === true) and (empty($this->getTerminalPassword()) === false))
        {
            $this->verifySecureHash($content);
        }

        // If the payment requires TPV
        if (strlen($content[ResponseFields::PAYMENT_ID]) > 14)
        {
            $content[ResponseFields::PAYMENT_ID] = explode('.', $content[ResponseFields::PAYMENT_ID])[0];
        }

        $this->assertPaymentId(
            $input['payment']['id'],
            $content[ResponseFields::PAYMENT_ID]
        );

        $expectedAmount = $this->formatAmount($input['payment']['amount'] / 100);
        $actualAmount   = $this->formatAmount((float) $content[ResponseFields::AMOUNT]);

        $this->assertAmount($expectedAmount, $actualAmount);

        $this->checkCallbackStatus($content);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
                                    $content[ResponseFields::PAYMENT_ID],
                                    Payment\Action::AUTHORIZE);

        // If callback status was a success, we verify the payment immediately
        $this->verifyCallback($gatewayPayment, $input);

        // Saving callback response only if the above checks pass
        $gatewayPayment = $this->saveCallbackResponse($gatewayPayment, $content);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    /**
     * Verifying the payment after callback response is saved to
     * prevent user tampering with the data while making a payment.
     */
    protected function verifyCallback($gatewayPayment, array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $verify->payment = $gatewayPayment;

        $this->sendPaymentVerifyRequest($verify);

        $this->checkGatewaySuccess($verify);

        //
        // If verify returns false, we throw an error as
        // authorize request / response has been tampered with
        //
        if ($verify->gatewaySuccess === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR);
        }

        $expectedAmount = $this->formatAmount($input['payment']['amount'] / 100);

        $actualAmount   = $this->formatAmount((float) $verify->verifyResponseContent[ResponseFields::AMOUNT]);

        $this->assertAmount($expectedAmount, $actualAmount);
    }

    protected function sendPaymentVerifyRequest(Verify $verify)
    {
        $content = $this->getVerifyRequestData($verify);

        // UAT uses a different Url than prod
        if ($this->mode === Mode::TEST)
        {
            $type = $this->action . '_' . $this->mode;
        }
        else
        {
            $type = null;
        }

        $request = $this->getStandardRequestArray($content, 'post', $type);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'gateway'    => $this->gateway,
                'request'    => $request,
                'payment_id' => $verify->input['payment']['id'],
            ]
        );

        $response = $this->sendGatewayRequest($request);

        $verify->verifyResponseContent = $this->parseVerifyResponse($response->body);
    }

    protected function verifyPayment(Verify $verify)
    {
        $status = $this->getVerifyMatchStatus($verify);

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH);

        $verify->payment = $this->saveVerifyContent($verify);
    }

    protected function getVerifyMatchStatus(Verify $verify)
    {
        $status = VerifyResult::STATUS_MATCH;

        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        return $status;
    }

    protected function checkGatewaySuccess(Verify $verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        //
        // Verify response will contain S or N or E, but we have already
        // mapped the S status to Y in parseVerifyResponse
        //
        if ($content[ResponseFields::STATUS] === Status::getAuthSuccessStatus())
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function getVerifyRequestData($verify)
    {
        $input = $verify->input;
        $gatewayEntity = $verify->payment;

        $data = [
            RequestFields::PAYEE_ID   => $this->getMerchantId(),
            RequestFields::PAYMENT_ID => $input['payment']['id'],
            RequestFields::ITEM_CODE  => strtoupper($input['payment']['id']),
            RequestFields::AMOUNT     => $input['payment']['amount'] / 100,
        ];

        if ($gatewayEntity->isTpv() === true)
        {
            $data[RequestFields::PAYMENT_ID] = $input['payment']['id'] . '.' . $gatewayEntity->getAccountNumber();
        }

        return $data;
    }

    protected function getAuthorizeRequestData(array $input)
    {
        $data = [
            RequestFields::ACTION       => Status::YES,
            RequestFields::BANK_ID      => Constants::BANK_ID,
            RequestFields::MODE         => Action::AUTH_MODE,
            RequestFields::PAYEE_ID     => $this->getMerchantId(),
            RequestFields::PAYMENT_ID   => $input['payment']['id'],
            RequestFields::ITEM_CODE    => strtoupper($input['payment']['id']),
            RequestFields::AMOUNT       => $input['payment']['amount'] / 100,
            RequestFields::CURRENCY     => Currency::INR,
            RequestFields::LANGUAGE_ID  => Constants::USER_LANG_ID,
            RequestFields::STATE_FLAG   => Constants::STATE_FLAG,
            RequestFields::USER_TYPE    => Constants::USER_TYPE,
            RequestFields::APP_TYPE     => Constants::APP_TYPE,
            RequestFields::CONFIRMATION => Status::YES,
            RequestFields::RETURN_URL   => $input['callbackUrl']
        ];

        if ($input['merchant']->isTPVRequired())
        {
            $data[RequestFields::PAYMENT_ID] .= '.' . $input['order']['account_number'];
        }

        $hashParams = [
            $data[RequestFields::PAYEE_ID],
            $data[RequestFields::PAYMENT_ID],
            $data[RequestFields::ITEM_CODE],
            $data[RequestFields::AMOUNT],
        ];

        if (empty($this->getTerminalPassword()) === false)
        {
            $data[RequestFields::HASH] = $this->getHashOfArray($hashParams);
        }

        return $data;
    }

    protected function getEntityAttributes(array $input)
    {
        $entityAttributes = [
            RequestFields::AMOUNT     => $input['payment']['amount'] / 100,
            RequestFields::PAYMENT_ID => $input['payment']['id'],
            RequestFields::ITEM_CODE  => strtoupper($input['payment']['id'])
        ];

        return $entityAttributes;
    }

    protected function saveCallbackResponse($gatewayPayment, array $content)
    {
        $attributes = [
            Base\Entity::RECEIVED        => true,
            Base\Entity::BANK_PAYMENT_ID => $content[ResponseFields::BANK_PAYMENT_ID],
            Base\Entity::STATUS          => $content[ResponseFields::PAID],
        ];

        $gatewayPayment->fill($attributes);

        $gatewayPayment->saveOrFail();

        return $gatewayPayment;
    }

    protected function checkCallbackStatus(array $content)
    {
        if ((isset($content[ResponseFields::PAID]) === false) or
            ($content[ResponseFields::PAID] !== Status::YES))
        {
            $this->trace->info(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                [
                    'content' => $content
                ]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    protected function saveVerifyContent(Verify $verify)
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        $attributes = $this->getVerifyAttributesToSave($content, $gatewayPayment);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function getVerifyAttributesToSave(array $content, Base\Entity $gatewayPayment)
    {
        if ($this->shouldStatusBeUpdated($gatewayPayment) === true)
        {
            $attributes[Base\Entity::STATUS] = $content[ResponseFields::STATUS];
        }

        //
        // Saving BID from Verify response only if BID from authorize hasn't been saved
        //
        if (isset($content[ResponseFields::BANK_PAYMENT_ID]) === true)
        {
                if (empty($gatewayPayment[Base\Entity::BANK_PAYMENT_ID]) === true)
                {
                    $attributes[Base\Entity::BANK_PAYMENT_ID] = $content[ResponseFields::BANK_PAYMENT_ID];
                }
                else if ((empty($gatewayPayment[Base\Entity::BANK_PAYMENT_ID]) === false) and
                         ($gatewayPayment[Base\Entity::BANK_PAYMENT_ID] !== $content[ResponseFields::BANK_PAYMENT_ID]))
                {
                    $this->trace->error(
                        TraceCode::GATEWAY_MULTIPLE_BANK_PAYMENT_IDS,
                        [
                            'authorize_bid' => $gatewayPayment[Base\Entity::BANK_PAYMENT_ID],
                            'verify_bid'    => $content[ResponseFields::BANK_PAYMENT_ID]
                        ]);
                }
        }

        return $attributes ?? [];
    }

    protected function getAuthSuccessStatus()
    {
        return Status::getAuthSuccessStatus();
    }

    protected function parseVerifyResponse(string $body)
    {
        // Separating out the rows of the string
        $rows = explode("\n", $body);

        // Removing the 0000's at the end of the string before proceeding
        // The whitespaces don't contain the pip as a separator character
        if (strpos($rows[count($rows) - 1], '|') === false)
        {
            unset($rows[count($rows) - 1]);
        }

        // We get the most relevant row in the response
        $values = $this->getVerifyResponseArray($rows);

        //
        // Manually setting success to failed for verify response "||||"
        //
        if (empty($values[0]) === true)
        {
            $values[4] = Status::NO;
        }

        $keys = $this->getVerifyResponseKeys();

        $content = array_combine($keys, $values);

        $status = self::VERIFY_TO_CALLBACK_STATUS[$content[ResponseFields::STATUS]];

        $content[ResponseFields::STATUS] = $status;

        // Values to be processed being traced here
        $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE_CONTENT, ['content' => $content]);

        return $content;
    }

    /**
     * We go over each row in the table and count the number of success rows.
     * If there's only one success row, we return it. Else we return the first row in the rows by default.
     * If there's more than 1 success row, we throw an exception.
     *
     * @param array $rows
     * @return mixed
     * @throws Exception\PaymentVerificationException
     */
    protected function getVerifyResponseArray(array $rows)
    {
        $data = [
            'response_array' => $rows,
            'payment_id'     => $this->input['payment']['id'],
            'gateway'        => $this->gateway,
        ];

        //
        // Initialize number of success chunks to 0 and
        // chunk to be returned to the first chunk
        //
        $numSuccess = 0;

        $rowToBeReturned = $rows[0];

        foreach ($rows as $row)
        {
            //
            // If we find a chunk with a success status,
            // we increment numSuccess and assign $chunk
            // to $chunkToBeReturned
            //
            $rowArray = explode('|', $row);

            if ($rowArray[4] === Status::SUCCESS)
            {
                $numSuccess++;

                $rowToBeReturned = $row;
            }
        }

        //
        // If numSuccess is greater than 1, we throw an exception
        //
        if ($numSuccess > 1)
        {
            // Adding num success to the data to be traced
            $data['num_sucess'] = $numSuccess;

            $this->trace->error(TraceCode::MULTIPLE_SUCCESS_TABLES_IN_VERIFY_RESPONSE, ['response_data' => $data]);

            throw new Exception\PaymentVerificationException(
                $data,
                null,
                VerifyAction::FINISH,
                ErrorCode::SERVER_ERROR_MULTIPLE_SUCCESS_TRANSACTIONS_IN_VERIFY
            );
        }

        // Returning the correct row in array form
        return explode('|', $rowToBeReturned);
    }

    protected function getVerifyResponseKeys()
    {
        $keys = [
            ResponseFields::PAYMENT_ID,
            ResponseFields::ITEM_CODE,
            ResponseFields::BANK_PAYMENT_ID,
            ResponseFields::AMOUNT,
            ResponseFields::STATUS
        ];

        return $keys;
    }

    protected function getMerchantId()
    {
        $merchantId = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            $merchantId = $this->getTestMerchantId();
        }

        return $merchantId;
    }

    protected function getLiveMerchantId()
    {
        return $this->config['live_merchant_id'];
    }

    public function formatAmount($amount): string
    {
        return number_format($amount , 2, '.', '');
    }

    protected function getStringToHash($content, $glue = '')
    {
        return parent::getStringToHash($content, '|');
    }

    protected function getHashOfString($str)
    {
        $secret = $this->getTerminalPassword();

        return hash_hmac(HashAlgo::SHA256, $str, $secret);
    }

    protected function verifySecureHash(array $content)
    {
        $hashParams = [
            $content[ResponseFields::PAYEE_ID],
            $content[ResponseFields::PAYMENT_ID],
            $content[ResponseFields::ITEM_CODE],
            $content[ResponseFields::AMOUNT],
            $content[ResponseFields::BANK_PAYMENT_ID],
            $content[ResponseFields::PAID],
            ResponseFields::HASH => $content[ResponseFields::HASH],
        ];

        parent::verifySecureHash($hashParams);
    }
}
