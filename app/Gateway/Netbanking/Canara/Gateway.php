<?php

namespace RZP\Gateway\Netbanking\Canara;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Verify;
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

       // $this->verifyCallback($input, $content);

        $this->assertPaymentId($input['payment']['id'], $content[ResponseFields::PAYMENT_ID]);

        $this->assertAmount($input['payment']['amount'], (int)$content[ResponseFields::AMOUNT]);

        $gatewayPayment = $this->saveCallbackResponse($content);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input,$acquirerData);
    }

    protected function verifyCallback(array $input, array $callbackContent)
    {
        $verify = new Verify($this->gateway, $input);

        $this->getPaymentToVerify($verify);

        $this->sendPaymentVerifyRequest($verify);

        $this->checkGatewaySuccess($verify);

        $apiSuccess = ($callbackContent[ResponseFields::STATUS] === ResponseCodeMap::SUCCESS_CODE);

        if ($verify->gatewaySuccess !== $apiSuccess)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED);
        }
    }

  /*  public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);

    }*/

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

    public function getDate($createdat)
    {
        return $date = Carbon::createFromTimestamp($createdat, Timezone::IST)
                             ->format('d/m/Y+H:i:s');
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

   /* protected function sendPaymentVerifyRequest($verify)
    {
        $request = $this->getVerifyRequest($verify);

        $response = $this->sendVerifyRequest($request);
        sd($response);

    }*/

 /*   protected function getVerifyRequest($verify)
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
            RequestFields::VERIFY_MERCHANT_CODE         => $this->getMerchantId(),
            RequestFields::VERIFY_PAYMENT_ID            => $input['payment']['id'],
            RequestFields::VERIFY_AMOUNT                => $this->formatAmount($input['payment']['amount']),
            RequestFields::VERIFY_BANK_REF_NUMBER       => $bankRefNumber,
            RequestFields::VERIFY_MODE_OF_TRANSACTION   => RequestFields::VERIFY_MODE_OF_TRANSACTION_VALUE,
            RequestFields::FUND_TRANSFER                => Constants::FUND_TRANSFER,
        ];

        $encryptedString = $this->getEncryptor()->encryptData($data);

        $content = [
            RequestFields::VERIFY_MERCHANT_CODE => $this->getMerchantId(),
            RequestFields::VERIFY_DATA          => $encryptedString
        ];

        $request = $this->getStandardRequestArray($content, 'get', Action::VERIFY);

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
    }*/


    // -------------------------- General helper methods --------------------------

    public function stripEmailSpecialChars($email)
    {
        return preg_replace("/[^a-zA-Z0-9]+/", "", $email);
    }

}