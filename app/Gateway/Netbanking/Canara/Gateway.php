<?php

namespace RZP\Gateway\Netbanking\Canara;

use TransactionType;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Models\Payment\Action;
use RZP\Gateway\Netbanking\Base;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'netbanking_canara';

    protected $bank = 'canara';

    protected $map = [
        RequestFields::CLIENT_CODE             => NetbankingEntity::CLIENT_CODE,
        RequestFields::MERCHANT_CODE           => NetbankingEntity::MERCHANT_CODE,
        RequestFields::AMOUNT                  => NetbankingEntity::AMOUNT,
        RequestFields::DATE                    => NetbankingEntity::DATE,
        ResponseFields::BANK_REFERENCE_NUMBER  => NetbankingEntity::BANK_PAYMENT_ID,
        NetbankingEntity::RECEIVED             => NetbankingEntity::RECEIVED,
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getRequestData($input);

        $this->createGatewayPaymentEntity($content);

        $request = $this->createRequest($content);

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

        $this->assertPaymentId($input['payment']['id'], $content[ResponseFields::PAYMENT_ID]);

        $this->assertAmount($input['payment']['amount'], (int) $content[ResponseFields::AMOUNT]);

        $this->checkCallbackStatus($content);

        $this->verifyCallback($input);

        $gatewayPayment = $this->saveCallbackResponse($content);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input,$acquirerData);
    }

    protected function verifyCallback(array $input)
    {
        $verify = new Verify($this->gateway, $input);

        $this->getPaymentToVerify($verify);

        $this->sendPaymentVerifyRequest($verify);

        $this->checkGatewaySuccess($verify);

        $verify->amountMismatch = $this->getVerifyAmountMismatch($verify);

        if ($verify->amountMismatch === true)
        {
            throw new Exception\LogicException(
                'Amount tampering found.',
                ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED,
                null,
                null,
                [
                    'callback_response' => $input['gateway'],
                    'verify_response'   => $verify->verifyResponseContent,
                    'payment_id'        => $input['payment']['id'],
                    'gateway'           => $this->gateway
                ]);
        }

        if ($verify->gatewaySuccess === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR,
                null,
                null,
                [
                    'callback_response' => $input['gateway'],
                    'verify_response'   => $verify->verifyResponseContent,
                    'payment_id'        => $input['payment']['id'],
                    'gateway'           => $this->gateway
                ]);
        }
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);

    }

    // -------------------------- Authorise helper methods ------------------------------

    protected function getRequestData($input)
    {
        $paymentEntity = $input['payment'];
        $data = [
            RequestFields::MODE_OF_TRANSACTION           => TransactionType::AUTHORIZE,
            RequestFields::CLIENT_CODE                   => $this->getClientCode($paymentEntity[Payment\Entity::EMAIL]),
            RequestFields::CLIENT_ACCOUNT                => '',
            RequestFields::MERCHANT_CODE                 => $this->getMerchantId(),
            RequestFields::CURRENCY                      => PaymentEntity::DEFAULT_CURRENCY,
            RequestFields::AMOUNT                        => $paymentEntity['amount'], // have to verify
            RequestFields::SERVICE_CHARGE                => 0,
            RequestFields::PAYMENT_ID                    => $paymentEntity['id'],
            RequestFields::SUCCESS_STATIC_FLAG           => Constants::SUCCESS_AND_FAILURE_STATIC_FLAG,
            RequestFields::FAILURE_STATIC_FLAG           => Constants::SUCCESS_AND_FAILURE_STATIC_FLAG,
            RequestFields::DATE                          => $this->getDate($paymentEntity[Payment\Entity::CREATED_AT]),
        ];

        return $data;
    }

    protected function createRequest($content)
    {
        $request = $this->getStandardRequestArray();

        $content = http_build_query($content);

        $request['url'] .= '?' . $content;

        return $request;
    }

    protected function getClientCode($email)
    {
        $email = $email ?: Constants::CLIENT_CODE;

        $clientCode = $this->stripEmailSpecialChars($email);

        return $clientCode;
    }

    public function getMerchantId()  // TODO:: Merchant id has to be added, MID not recieeved from bank
    {
        $mid = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            $mid = $this->getTestMerchantId();
        }

        return $mid;
    }

    // -------------------------- Callback helper methods ------------------------------

    protected function checkCallbackStatus(array $content)
    {
        if (isset($content[ResponseFields::BANK_REFERENCE_NUMBER]) === false)
        {
            $this->trace->info(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                [
                    'paymentid' => $content[ResponseFields::PAYMENT_ID],
                    'gateway'   => $this->gateway,
                    'content'   => $content,
                ]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    protected function saveCallbackResponse($content)
    {
        $content[NetbankingEntity::RECEIVED] = true;

        $gatewayPayment = $this->getRepository()->findByPaymentIdAndActionOrFail(
            $content[ResponseFields::PAYMENT_ID],
            Action::AUTHORIZE);

        $gatewayPayment = $this->updateGatewayPaymentEntity($gatewayPayment, $content);

        return $gatewayPayment;
    }

    // -------------------------- Verify helper methods ------------------------------

    protected function sendPaymentVerifyRequest($verify)
    {
        $request = $this->getVerifyRequest($verify);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            $request
        );

        $response = $this->sendGatewayRequest($request);

        $verify->verifyResponseContent = $this->parseResponseXml($response->body);

    }

    protected function getVerifyRequest($verify)
    {
        $input = $verify->input;

        if ($this->action === Action::VERIFY and isset($verify->payment['bank_payment_id']))
        {

            $bankRefNumber = $verify->payment['bank_payment_id'];
        }
        elseif($this->action === Action::CALLBACK)
        {
            $bankRefNumber = $input['gateway'][ResponseFields::BANK_REFERENCE_NUMBER];
        }

        $paymentEntity = $input['payment'];

        $data = [
            RequestFields::MODE_OF_TRANSACTION           => TransactionType::VERIFY,
            RequestFields::CLIENT_CODE                   => $this->getClientCode($paymentEntity[Payment\Entity::EMAIL]),
            RequestFields::CLIENT_ACCOUNT                => '',
            RequestFields::MERCHANT_CODE                 => $this->getMerchantId(),
            RequestFields::CURRENCY                      => PaymentEntity::DEFAULT_CURRENCY,
            RequestFields::AMOUNT                        => $paymentEntity['amount'], // have to verify
            RequestFields::SERVICE_CHARGE                => 0,
            RequestFields::PAYMENT_ID                    => $paymentEntity['id'],
            RequestFields::SUCCESS_STATIC_FLAG           => 'N',
            RequestFields::FAILURE_STATIC_FLAG           => 'N',
            RequestFields::VER_DATE                      => $this->getCurrentDate(),
            RequestFields::PUR_DATE                      => $this->getDate($paymentEntity[Payment\Entity::CREATED_AT]),
        ];

        if(isset($bankRefNumber) === true)
        {
            $data[ResponseFields::BANK_REFERENCE_NUMBER] = $bankRefNumber;
        }

        $request = $this->getStandardRequestArray($data, 'get', Action::VERIFY);

        // Since they don't have a valid SSL certificate on UAT site.
        if ($this->mode === Mode::TEST)
        {
            $request['options']['verify'] = false;
        }

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'gateway'           => $this->gateway,
                'request'           => $request,
                'payment_id'        => $paymentEntity['id'],
                'content'           => $data,
            ]
        );

        return $request;
    }

    public function verifyPayment(Verify $verify)
    {
        $verify->status = $this->getVerifyStatus($verify);

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $verify->amountMismatch = $this->getVerifyAmountMismatch($verify);

        $verify->payment = $this->saveVerifyContent($verify);
    }

    protected function getVerifyStatus(Verify $verify) :string
    {
        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        $status = VerifyResult::STATUS_MATCH;

        if ($verify->apiSuccess !== $verify->gatewaySuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        return $status;
    }

    protected function saveVerifyContent(Verify $verify): Base\Entity
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        $attributes = $this->getVerifyAttributes($content);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function checkGatewaySuccess(Verify $verify)
    {
        $verify->gatewaySuccess = false;

        $response = $verify->verifyResponseContent;

        if (($response[ResponseFields::RETURN_CODE] === Constants::SUCCESS) and
            (isset($response[ResponseFields::VERIFY_STATUS]) === true) and
            ($response[ResponseFields::VERIFY_STATUS] === Constants::SUCCESS_VERIFY_STATUS))
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function getVerifyAttributes(array $content): array
    {
        return [
            Base\Entity::RECEIVED        => true,
            Base\Entity::STATUS          => $content[ResponseFields::VERIFY_STATUS],
            Base\Entity::BANK_PAYMENT_ID => $content[ResponseFields::VER_BANK_REFERENCE_NUMBER]
        ];
    }

    protected function getVerifyAmountMismatch(Verify $verify)
    {
        $input = $verify->input;

        $content = $verify->verifyResponseContent;

        if (isset($content[ResponseFields::VER_AMOUNT]) === false)
        {
            return false;
        }

        $expectedAmount = $input['payment']['amount'];

        $actualAmount = (int) $content[ResponseFields::VER_AMOUNT];

        return ($expectedAmount !== $actualAmount);
    }



    // -------------------------- General helper methods --------------------------

    public function stripEmailSpecialChars($email)
    {
        return preg_replace("/[^a-zA-Z0-9]+/", "", $email);
    }

    public function getDate($createdat)
    {
        return $date = Carbon::createFromTimestamp($createdat, Timezone::IST)
                             ->format('d/m/Y+H:i:s');
    }

    public function getCurrentDate()
    {
        return $date = Carbon::now()->format('d/m/Y+H:i:s');
    }

    protected function parseResponseXml(string $response): array
    {
        return (array) simplexml_load_string(trim($response));
    }

}