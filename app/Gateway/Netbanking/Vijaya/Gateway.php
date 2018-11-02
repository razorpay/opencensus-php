<?php

namespace RZP\Gateway\Netbanking\Vijaya;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Netbanking\Base;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Models\Payment\Entity as Payment;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'netbanking_vijaya';

    protected $bank = 'vijaya';

    protected $map = [
        RequestFields::AMOUNT                   => NetbankingEntity::AMOUNT,
        ResponseFields::STATUS                  => NetbankingEntity::STATUS,
        ResponseFields::BANK_REFERENCE_NUMBER   => NetbankingEntity::BANK_PAYMENT_ID,
        NetbankingEntity::RECEIVED              => NetbankingEntity::RECEIVED,
    ];

    public function authorize(array $input): array
    {
        parent::authorize($input);

        $request = $this->getAuthorizeRequest($input);

        $contentToSave = $this->getContentToSave($input['payment']);

        $this->createGatewayPaymentEntity($contentToSave);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'gateway'            => $this->gateway,
                'gateway_response'   => $input['gateway'],
                'payment_id'         => $input['payment']['id']
            ]
        );

        $this->assertPaymentId($content[ResponseFields::PAYMENT_ID], $input['payment']['id']);

        $this->assertAmount($this->formatAmount($input['payment']['amount']), $content[ResponseFields::AMOUNT]);

        $gatewayPayment = $this->saveCallbackResponse($content, $input);

        $this->checkCallbackStatus($content);

        $this->verifyCallback($input, $gatewayPayment);

        return $this->getCallbackResponseData($input);
    }

    protected function verifyCallback(array $input, $gatewayPayment)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $verify->payment = $gatewayPayment;

        $this->sendPaymentVerifyRequest($verify);

        $this->checkVerifyGatewaySuccess($verify);

        if ($verify->gatewaySuccess === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR);
        }
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    // -------------------- Auth helper methods-------------------------

    protected function getAuthorizeRequest($input): array
    {
        $payment = $input['payment'];

        $content = [
            RequestFields::MERCHANT_CONSTANT => Constants::MERCHANT_CONSTANT,
            RequestFields::AMOUNT            => $this->formatAmount($payment[Payment::AMOUNT]),
            RequestFields::MERCHANT_NAME     => $input['merchant']['billing_label'],
            RequestFields::MERCHANT_ID       => Constants::MERCHANT_CONSTANT,//$this->getMerchantId(),
            RequestFields::ITEM_CODE         => Constants::ITEM_CODE,
            RequestFields::CURRENCY          => Constants::INDIAN_CURRENCY,
            RequestFields::PAYMENT_ID        => $payment[Payment::ID],
            RequestFields::RETURN_URL        => $input['callbackUrl'],
        ];

        $request = $this->getStandardRequestArray($content, 'post');

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'gateway'    => $this->gateway,
                'content'    => $content,
                'payment_id' => $payment[Payment::ID],
                'request'    => $request
            ]);

        return $request;
    }

    protected function getContentToSave($payment): array
    {
        return [
            RequestFields::AMOUNT => $payment[Payment::AMOUNT]
        ];
    }

    // -------------------- Auth helper methods end----------------------

    // -------------------- Callback helper methods----------------------

    protected function checkCallbackStatus(array $content)
    {
        if ($this->isGatewaySuccess($content) === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    protected function saveCallbackResponse(array $content, array $input)
    {
        $content[NetbankingEntity::RECEIVED] = true;

        $gatewayPayment = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'],
            Action::AUTHORIZE);

        $this->updateGatewayPaymentEntity($gatewayPayment, $content);

        return $gatewayPayment;
    }

    protected function isGatewaySuccess(array $content): bool
    {
        return Status::isSuccess($content[ResponseFields::STATUS]);
    }

    // -------------------- Callback helper methods end -----------------

    // -------------------- Verify helper methods -----------------------

    protected function sendPaymentVerifyRequest($verify)
    {
        $content = $this->getVerifyRequestData($verify);

        if (isset($content[RequestFields::BANK_REFERENCE_NUMBER]))
        {
            $type = $this->action . '_' . 'BID_PRESENT';
        }
        else
        {
            $type = $this->action . '_' . 'BID_ABSENT';
        }

        $request = $this->getStandardRequestArray($content, 'get', $type);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request'    => $request,
                'gateway'    => $this->gateway,
                'payment_id' => $verify->input['payment']['id'],
            ]
        );

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'response'   => $response->body,
                'gateway'    => $this->gateway,
                'payment_id' => $verify->input['payment']['id'],
            ]
        );

        $verify->verifyResponseContent = $this->parseVerifyResponse($response->body);
    }

    protected function verifyPayment($verify)
    {
        $verify->status = VerifyResult::STATUS_MATCH;

        $this->checkApiSuccess($verify);

        $this->checkVerifyGatewaySuccess($verify);

        if ($verify->apiSuccess !== $verify->gatewaySuccess)
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->amountMismatch = false;

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);
    }

    protected function getVerifyRequestData($verify): array
    {
        $payment = $verify->input['payment'];

        $content = [
            RequestFields::MERCHANT_CONSTANT => Constants::MERCHANT_CONSTANT,
            RequestFields::PAYMENT_ID        => $payment['id'],
            RequestFields::ITEM_CODE         => Constants::ITEM_CODE,
            RequestFields::AMOUNT            => $this->formatAmount($payment['amount']),
            RequestFields::RETURN_URL        => 'abc' //TODO find what needs to be sent here
        ];

        if (isset($input['gateway'][NetbankingEntity::BANK_PAYMENT_ID]))
        {
            $content[RequestFields::BANK_REFERENCE_NUMBER] = $verify->input['gateway'][NetbankingEntity::BANK_PAYMENT_ID];
        }

        return $content;
    }

    protected function parseVerifyResponse($body): array
    {

        return [NetbankingEntity::STATUS => $body];
    }

    protected function checkVerifyGatewaySuccess($verify)
    {
        $verify->gatewaySuccess = false;

        if (VerifyResponse::isSuccess($verify->verifyResponseContent[NetbankingEntity::STATUS]) === true)
        {
            $verify->gatewaySuccess = true;
        }
    }

    // -------------------- Verify helper methods end -------------------

    // -------------------- General helper methods ----------------------

    protected function getMerchantId(): string
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->getTestMerchantId();
        }

        return $this->getLiveMerchantId();
    }

    public function formatAmount(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
