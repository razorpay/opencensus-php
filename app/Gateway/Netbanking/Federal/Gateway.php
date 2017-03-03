<?php

namespace RZP\Gateway\Netbanking\Federal;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Verify;
use RZP\Models\Payment\Action;
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

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK,
                            ['gateway_response' => $content,
                             'payment_id'       => $input['payment']['id']]);

        $this->assertPaymentId($input['payment']['id'],
                               $content[ResponseFields::PAYMENT_ID]);

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

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'response' => $response->body
            ]);

        $verify->verifyResponseContent = $this->parseVerifyResponse($response->body);
    }

    protected function verifyPayment(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        $status = $this->getVerifyMatchStatus($verify);

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH) ? true : false;

        $verify->payment = $this->saveVerifyContent($verify);
    }

    protected function getVerifyMatchStatus(Verify $verify)
    {
        $status = VerifyResult::STATUS_MISMATCH;

        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        if ($verify->gatewaySuccess === $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MATCH;
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

        // content will contain status as either Y or N
        if ($content[ResponseFields::STATUS] === Constants::CONFIRMATION)
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function getVerifyRequestData(array $input)
    {
        $data = $this->getRequestData($input);

        $payment = $this->repo->findByPaymentId($input['payment']['id'])
                              ->first();

        $data[RequestFields::MODE]            = Constants::VERIFY_MODE;
        $data[RequestFields::BANK_PAYMENT_ID] = $payment[Base\Entity::BANK_PAYMENT_ID];

        return $data;
    }

    protected function getRequestData(array $input)
    {
        $data = [
            RequestFields::ACTION       => Constants::CONFIRMATION,
            RequestFields::BANK_ID      => Constants::BANK_ID,
            RequestFields::MODE         => Constants::AUTH_MODE,
            RequestFields::PAYEE_ID     => $this->getMerchantId(),
            RequestFields::PAYMENT_ID   => $input['payment']['id'],
            RequestFields::ITEM_CODE    => strtoupper($input['payment']['id']),
            RequestFields::AMOUNT       => $input['payment']['amount'] / 100,
            RequestFields::CURRENCY     => Currency::INR,
            RequestFields::LANGUAGE_ID  => Constants::USER_LANG_ID,
            RequestFields::STATE_FLAG   => Constants::STATE_FLAG,
            RequestFields::USER_TYPE    => Constants::USER_TYPE,
            RequestFields::APP_TYPE     => Constants::APP_TYPE,
            RequestFields::CONFIRMATION => Constants::CONFIRMATION,
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
                                    Action::AUTHORIZE);

        $gatewayPayment->fill($attributes);

        $gatewayPayment->saveOrFail();
    }

    protected function checkCallbackStatus(array $content)
    {
        if ((isset($content[ResponseFields::PAID]) === false) or
            ($content[ResponseFields::PAID] !== Constants::CONFIRMATION))
        {
            $this->trace->error(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                ['content' => $content]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    protected function saveVerifyContent(Verify $verify)
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        $attributes = [
            Base\Entity::RECEIVED => true,
            Base\Entity::STATUS   => $content[ResponseFields::STATUS]
        ];

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function parseVerifyResponse(string $body)
    {
        $status = (array) simplexml_load_string($body);

        // Verify response is Y or N, so adding a key for the response
        return [
            ResponseFields::STATUS => trim($status[ResponseFields::VERIFY_BODY])
        ];
    }

    /**
     * Overriding the parent method
     **/
    protected function getUrl($type = null)
    {
        $url = $this->getUrlDomain();

        //
        // If the BID was never saved, verify request
        // needs to go to a different url
        //
        if ((empty($content[RequestFields::BANK_PAYMENT_ID]) === true) and
            ($this->action === Action::VERIFY))
        {
            $type = Constants::VERIFY_BROKEN;
        }
        else
        {
            $type = $this->action;
        }

        $type = strtoupper($type);

        $url .= $this->getRelativeUrl($type);

        return $url;
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
