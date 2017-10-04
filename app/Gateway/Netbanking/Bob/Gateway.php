<?php

namespace RZP\Gateway\Netbanking\Bob;

use phpseclib\Crypt\AES;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Entity as GatewayEntity;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Netbanking\Base;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Payment\Entity as Payment;
use RZP\Trace\TraceCode;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'netbanking_bob';

    protected $bank = 'bob';

    protected $map = [
        RequestFields::MERCHANT_ID              => NetbankingEntity::MERCHANT_CODE,
        RequestFields::AMOUNT                   => NetbankingEntity::AMOUNT,
        ResponseFields::STATUS                  => NetbankingEntity::STATUS,
        ResponseFields::BANK_REF_NUMBER         => NetbankingEntity::BANK_PAYMENT_ID,
        ResponseFields::CUSTOMER_ACCOUNT_NUMBER => NetbankingEntity::ACCOUNT_NUMBER,
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

        $content = $this->getCallbackContent($input);

        $this->assertPaymentId($content[ResponseFields::PAYMENT_ID], $input['payment']['id']);

        $this->saveCallbackResponse($content, $input);

        $this->checkCallbackStatus($content);

        return $this->getCallbackResponseData($input);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    // -------------------- Auth helper methods-------------------------

    protected function getAuthorizeRequest($input)
    {
        $payment = $input['payment'];

        $content = [
            RequestFields::MERCHANT_ID      => $this->getMerchantId(),
            RequestFields::BANK_FIXED_VALUE => Constants::BANK_FIXED_VALUE,
            RequestFields::BILLER_NAME      => Constants::BILLER_NAME,
            RequestFields::AMOUNT           => $this->formatAmount($payment[Payment::AMOUNT]),
            RequestFields::CALLBACK_URL     => $input['callbackUrl'],
            RequestFields::PAYMENT_ID       => $payment[Payment::ID]
        ];

        $encryptedData = $this->getEncryptor()->encryptData($content);

        $requestData = [
            RequestFields::ENCRYPTED_DATA => $encryptedData
        ];

        $request = $this->getStandardRequestArray($requestData);

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

    protected function getContentToSave($payment)
    {
        return [
            RequestFields::MERCHANT_ID => $this->getMerchantId(),
            RequestFields::AMOUNT      => $payment[Payment::AMOUNT]
        ];
    }

    // -------------------- Auth helper methods end----------------------

    // -------------------- Callback helper methods----------------------

    protected function getCallbackContent(array $input): array
    {
        $encryptedData = $input['gateway'][RequestFields::ENCRYPTED_DATA];

        $content = $this->getEncryptor()->decryptData($encryptedData);

        return $content;
    }

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
        if (isset($content[ResponseFields::BANK_REF_NUMBER]) === true)
        {
            $content[ResponseFields::BANK_REF_NUMBER] = trim($content[ResponseFields::BANK_REF_NUMBER]);
        }

        $gatewayPayment = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'],
            Action::AUTHORIZE
        );

        $this->updateGatewayPaymentEntity($gatewayPayment, $content);
    }

    // -------------------- Callback helper methods end -----------------

    // -------------------- Verify helper methods -----------------------

    protected function sendPaymentVerifyRequest($verify)
    {
        $content = $this->getVerifyRequestData($verify);

        $request = $this->getStandardRequestArray($content, 'get');

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request' => $request,
                'gateway' => $this->gateway
            ]);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'response' => $response->body,
                'gateway'  => $this->gateway
            ]);

        $verify->verifyResponseContent = $this->parseVerifyResponse($response->body);
    }

    protected function verifyPayment($verify)
    {
        $gatewayPayment = $verify->payment;

        $verify->status = VerifyResult::STATUS_MATCH;

        $this->checkApiSuccess($verify);

        $this->checkVerifyGatewaySuccess($verify);

        if ($verify->apiSuccess !== $verify->gatewaySuccess)
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $this->saveVerifyContentIfNeeded($gatewayPayment, $verify);
    }

    protected function getVerifyRequestData($verify)
    {
        $content = [
            RequestFields::PAYMENT_ID => $verify->input['payment']['id']
        ];

        return $content;
    }

    protected function parseVerifyResponse($body)
    {
        $pairs = explode(Constants::VERIFY_PAIR_SEPARATOR, $body);

        $content = [];

        foreach ($pairs as $value)
        {
            $pair = explode(Constants::VERIFY_KEY_VALUE_SEPARATOR, $value);

            $content[$pair[0]] = $pair[1];
        }

        return $content;
    }

    protected function checkVerifyGatewaySuccess($verify)
    {
        // Initially assume gatewaySuccess is false
        $verify->gatewaySuccess = false;

        if ($this->isStatusCodeSuccess($verify->verifyResponseContent) === true)
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function saveVerifyContentIfNeeded($gatewayPayment, $verify)
    {
        $payment = $verify->payment;

        $content = $verify->verifyResponseContent;

        $gatewayAttributes = $this->getContentToSave($payment);

        $gatewayAttributes[ResponseFields::BANK_REF_NUMBER] = $content[ResponseFields::BANK_REF_NUMBER];

        // Setting success status, since verification is success
        $gatewayAttributes[ResponseFields::STATUS] = Status::SUCCESS;

        // Callback gave failure status, but verify is success
        if ($gatewayPayment[NetbankingEntity::STATUS] === Status::FAILURE)
        {
            $gatewayPayment = $this->updateGatewayPaymentEntity($gatewayPayment, $gatewayAttributes);
        }

        return $gatewayPayment;
    }

    // -------------------- Verify helper methods end -------------------

    // -------------------- General helper methods ----------------------

    protected function isGatewaySuccess(array $content): bool
    {
        return ($content[ResponseFields::STATUS] === Status::SUCCESS);
    }

    protected function getMerchantId()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->getTestMerchantId();
        }

        return $this->getLiveMerchantId();
    }

    protected function isStatusCodeSuccess($content)
    {
        return ($content[ResponseFields::STATUS] === Status::SUCCESS);
    }

    protected function updateGatewayPaymentEntity(
        GatewayEntity $gatewayPayment,
        array $content,
        bool $mapped = true
    )
    {
        $attr = $this->getMappedAttributes($content);

        // To mark that we have received a response for this request
        $attr[NetbankingEntity::RECEIVED] = 1;

        $gatewayPayment->fill($attr);

        $gatewayPayment->saveOrFail();
    }

    public function getEncryptor(): AESCrypto
    {
        $secret = $iv = $this->getSecret();

        assert($secret !== null);

        return (new AESCrypto(AES::MODE_CBC, $secret, $iv));
    }

    public function formatAmount(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
