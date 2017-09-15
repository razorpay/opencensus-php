<?php

namespace RZP\Gateway\Netbanking\Bob;

use phpseclib\Crypt\AES;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base\Action;
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
        RequestFields::PAYMENT_ID               => NetbankingEntity::PAYMENT_ID,
        RequestFields::AMOUNT                   => NetbankingEntity::AMOUNT,
        ResponseFields::STATUS                  => NetbankingEntity::STATUS,
        ResponseFields::BANK_REF_NUMBER         => NetbankingEntity::BANK_PAYMENT_ID,
        ResponseFields::CUSTOMER_ACCOUNT_NUMBER => NetbankingEntity::ACCOUNT_NUMBER,
        NetbankingEntity::RECEIVED              => NetbankingEntity::RECEIVED,
    ];

    public function authorize(array $input): array
    {
        parent::authorize($input);

        $request = $this->getAuthorizeRequest($input);

        $contentToSave = $this->getAuthorizeNetbankingContentToSave($input['payment']);

        $this->createGatewayPaymentEntity($contentToSave);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $this->getCallbackContent($input);

        $content = $content + [NetbankingEntity::RECEIVED => true];

        $gatewayPayment = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'],
            Action::AUTHORIZE
        );

        // This asserts the payment id and amount from the response
        $this->checkCallbackResponse($gatewayPayment, $content);

        if ($this->isGatewaySuccess($content) === false)
        {
            $content[NetbankingEntity::RECEIVED] = false;
        }

        $this->updateGatewayPaymentEntity($gatewayPayment, $content);

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
            RequestFields::BILLER_NAME      => $this->input['merchant'][Merchant::NAME],
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
            ]
        );

        return $request;
    }

    protected function getAuthorizeNetbankingContentToSave($payment)
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

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'payment_id'    => $input['payment']['id'],
                'encryptedData' => $encryptedData,
                'content'       => $content
            ]
        );

        return $content;
    }

    protected function checkCallbackStatus(array $content)
    {
        if ($this->isGatewaySuccess($content) === false)
        {
            $this->trace->info(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                [
                    'payment_id' => $content[ResponseFields::PAYMENT_ID],
                    'content' => $content
                ]
            );

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    protected function checkCallbackResponse($gatewayPayment, $content)
    {
        assert($content[ResponseFields::AMOUNT] === $gatewayPayment[NetbankingEntity::AMOUNT]);

        assert($content[ResponseFields::PAYMENT_ID] === $gatewayPayment[NetbankingEntity::PAYMENT_ID]);
    }

    // -------------------- Callback helper methods end -----------------

    // -------------------- Verify helper methods -----------------------

    protected function sendPaymentVerifyRequest($verify)
    {
        $content = $this->getVerifyRequestData($verify);

        $request = $this->getStandardRequestArray($content, 'get');

        $query = http_build_query($content);

        $request['url'] = $request['url'] . '?' . $query;

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'response' => $response->body
            ]);

        $content = $this->parseVerifyResponse($response->body);

        $verify->verifyResponseContent = $content;
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

        $this->action = Action::AUTHORIZE;

        $gatewayAttributes = $this->getAuthorizeNetbankingContentToSave($payment);

        $gatewayAttributes[ResponseFields::BANK_REF_NUMBER] = $content[ResponseFields::BANK_REF_NUMBER];

        // Setting success status, since verification is success
        $gatewayAttributes[ResponseFields::STATUS] = Constants::STATUS_SUCCESS;

        // Timed out auth request
        if ($gatewayPayment === null)
        {
            $gatewayPayment = $this->createGatewayPaymentEntity($gatewayAttributes, Action::AUTHORIZE);
        }
        // Callback gave failure status, but verify is success
        else if ($gatewayPayment[NetbankingEntity::RECEIVED] === false)
        {
            $gatewayPayment = $this->updateGatewayPaymentEntity($gatewayPayment, $gatewayAttributes);
        }

        $this->action = Action::VERIFY;

        return $gatewayPayment;
    }

    // -------------------- Verify helper methods end -------------------

    // -------------------- General helper methods ----------------------

    protected function isGatewaySuccess(array $content): bool
    {
        return ($content[ResponseFields::STATUS] === Constants::STATUS_SUCCESS);
    }

    protected function getMerchantId()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_id'];
        }

        return $this->getLiveMerchantId();
    }

    protected function isStatusCodeSuccess($content)
    {
        return ($content[ResponseFields::STATUS] === Constants::STATUS_SUCCESS);
    }

    public function getEncryptor(): AESCrypto
    {
        $secret = $this->getSecret();

        assert($secret !== null);

        return (new AESCrypto(AES::MODE_CBC, $secret, $secret));
    }

    public function formatAmount(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }

}
