<?php

namespace RZP\Gateway\Netbanking\Canara;

use Carbon\Carbon;
use http\Env\Request;
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
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;

class Gateway extends Base\Gateway
{
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

        $this->verifyCallback($input);

        $this->assertPaymentId($input['payment']['id'], $content[ResponseFields::PAYMENT_ID]);

        $this->assertAmount($input['payment']['amount'], (int)$content[ResponseFields::AMOUNT]);

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

    // -------------------------- Authorise helper methods ------------------------------

    protected function getRequestData($input)
    {
        $data = [
            RequestFields::MODE_OF_TRANSACTION           => Constants::MODE_OF_TRANSACTION_PURCHASE,
            RequestFields::CLIENT_CODE                   => $this->getClientCode($input['payment'][Payment\Entity::EMAIL]),
            RequestFields::CLIENT_ACCOUNT                => '',
            RequestFields::MERCHANT_CODE                 => $this->getMerchantCode(),
            RequestFields::CURRENCY                      => Constants::INDIAN_CURRENCY,
            RequestFields::AMOUNT                        => $input['payment']['amount'], // have to verify
            RequestFields::SERVICE_CHARGE                => 0,
            RequestFields::PAYMENT_ID                    => $input['payment']['id'],
            RequestFields::SUCCESS_STATIC_FLAG           => 'N',
            RequestFields::FAILURE_STATIC_FLAG           => 'N',
            RequestFields::DATE                          => $this->getDate($input['payment'][Payment\Entity::CREATED_AT]),
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

    public function getMerchantCode()  // verify - have to add id somewhere
    {
        $mid = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            $mid = $this->getTestMerchantId();
        }

        return $mid;
    }

    // -------------------------- Callback helper methods ------------------------------

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

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'response'   => $response->body,
                'payment_id' => $verify->input['payment']['id'],
            ]
        );

        $verify->verifyResponseContent = $this->parseResponseXml($response->body);

    }

    protected function getVerifyRequest($verify)
    {
        $input = $verify->input;

        if ($this->action === Action::VERIFY)
        {
            $gatewayPayment = $verify->payment;

            $bankRefNumber = $gatewayPayment['bank_payment_id'];
        }
        elseif ($this->action === Action::CALLBACK)
        {
            $bankRefNumber = $input['gateway'][ResponseFields::BANK_REFERENCE_NUMBER];
        }
        else
        {
            throw new Exception\LogicException('Verify should be called from either verify or callback actions');
        }

        $data = [
            RequestFields::MODE_OF_TRANSACTION           => Constants::MODE_OF_TRANSACTION_VERIFY,
            RequestFields::CLIENT_CODE                   => $this->getClientCode($input['payment'][Payment\Entity::EMAIL]),
            RequestFields::CLIENT_ACCOUNT                => '',
            RequestFields::MERCHANT_CODE                 => $this->getMerchantCode(),
            RequestFields::CURRENCY                      => Constants::INDIAN_CURRENCY,
            RequestFields::AMOUNT                        => $input['payment']['amount'], // have to verify
            RequestFields::SERVICE_CHARGE                => 0,
            RequestFields::PAYMENT_ID                    => $input['payment']['id'],
            RequestFields::SUCCESS_STATIC_FLAG           => 'N',
            RequestFields::FAILURE_STATIC_FLAG           => 'N',
            RequestFields::VER_DATE                      => $this->getCurrentDate(),
            RequestFields::PUR_DATE                      => $this->getDate($input['payment'][Payment\Entity::CREATED_AT]),
            ResponseFields::BANK_REFERENCE_NUMBER        => $bankRefNumber,
        ];

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
                'payment_id'        => $input['payment']['id'],
                'decrypted_content' => $data,
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

        if ((isset($response[ResponseFields::VERIFY_STATUS]) === true) and
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
            Base\Entity::BANK_PAYMENT_ID => $content[ResponseFields::VER_BANK_REFERENCE_NUMBER] // doubt indusind had a ? operrator
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

        $expectedAmount = (int)$input['payment']['amount'];

        $actualAmount = (int)$content[ResponseFields::VER_AMOUNT];

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