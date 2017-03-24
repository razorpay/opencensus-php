<?php

namespace RZP\Gateway\Netbanking\Federal;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Netbanking\Base;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'netbanking_federal';

    protected $bank = 'federal';

    protected $map = [
        RequestFields::AMOUNT     => Base\Entity::AMOUNT,
        RequestFields::PAYMENT_ID => Base\Entity::PAYMENT_ID,
        RequestFields::ITEM_CODE  => Base\Entity::CAPS_PAYMENT_ID
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getRequestData($input);

        $content[RequestFields::RETURN_URL] = $input['callbackUrl'];

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

        $this->assertPaymentId(
            $input['payment']['id'],
            $content[ResponseFields::PAYMENT_ID]
        );

        $this->checkCallbackStatus($content);

        // If callback status was a success, we verify the payment immediately
        $this->verifyCallback($input);

        // Saving callback response only if the above checks pass
        $this->saveCallbackResponse($content);

        return $this->getCallbackResponseData($input);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    /**
     * Verifying the payment after callback response is saved to
     * prevent user tampering with the data while making a payment. We will be
     * making a verify broken request as we have not saved callback response yet
     */
    protected function verifyCallback(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

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
    }

    protected function sendPaymentVerifyRequest(Verify $verify)
    {
        $content = $this->getVerifyRequestData($verify->input);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'gateway'    => $this->gateway,
                'request'    => $request,
                'payment_id' => $verify->input['payment']['id'],
            ]
        );

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'response'   => $response->body,
                'payment_id' => $verify->input['payment']['id'],
            ]
        );

        $verify->verifyResponseContent = $this->parseVerifyResponse($response->body);
    }

    protected function verifyPayment(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

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

    protected function checkApiSuccess(Verify $verify)
    {
        $verify->apiSuccess = true;

        if (($verify->input['payment']['status'] === 'created') or
            ($verify->input['payment']['status'] === 'failed'))
        {
            $verify->apiSuccess = false;
        }
    }

    protected function checkGatewaySuccess(Verify $verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        // content will contain status as either Y or N or S
        if ($content[ResponseFields::STATUS] === Status::SUCCESS)
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function getVerifyRequestData(array $input)
    {
        $data = [
            RequestFields::PAYEE_ID   => $this->getMerchantId(),
            RequestFields::PAYMENT_ID => $input['payment']['id'],
            RequestFields::ITEM_CODE  => strtoupper($input['payment']['id']),
            RequestFields::AMOUNT     => $input['payment']['amount'] / 100,
        ];

        return $data;
    }

    protected function getRequestData(array $input)
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
        ];

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

    protected function saveCallbackResponse(array $content)
    {
        $attributes = [
            Base\Entity::RECEIVED        => true,
            Base\Entity::BANK_PAYMENT_ID => $content[ResponseFields::BANK_PAYMENT_ID],
            Base\Entity::STATUS          => $content[ResponseFields::PAID],
        ];

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
                                    $content[ResponseFields::PAYMENT_ID],
                                    Payment\Action::AUTHORIZE);

        $gatewayPayment->fill($attributes);

        $gatewayPayment->saveOrFail();
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

    protected function getVerifyAttributesToSave(array $content, $gatewayPayment)
    {
        $attributes = [
            Base\Entity::RECEIVED => true,
            Base\Entity::STATUS   => $content[ResponseFields::STATUS]
        ];

        //
        // Saving BID from Verify response only if BID from authorize hasn't been saved
        //
        if ((isset($content[ResponseFields::BANK_PAYMENT_ID]) === true) and
            (empty($gatewayPayment[Base\Entity::BANK_PAYMENT_ID]) === true))
        {
            $attributes[Base\Entity::BANK_PAYMENT_ID] = $content[ResponseFields::BANK_PAYMENT_ID];
        }

        return $attributes;
    }

    protected function parseVerifyResponse(string $body)
    {
        $values = explode('|', $body);

        //
        // Manually setting success to failed for verify response "||||"
        // In the success case, eliminating the \n0000's to clean the data
        //
        if (empty($values[0]) === true)
        {
            $values[4] = Status::NO;
        }
        else
        {
            // Cleaning data
            $values[4] = trim($values[4]);
        }

        $keys = $this->getVerifyResponseKeys();

        return array_combine($keys, $values);
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
        if ($this->mode === Mode::TEST)
        {
            return $this->getTestMerchantId();
        }

        return $this->getLiveMerchantId();
    }
}
