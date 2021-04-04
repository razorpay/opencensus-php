<?php

namespace RZP\Gateway\Esigner\Legaldesk;

use Carbon\Carbon;

use RZP\Error;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Models\Settlement\Holidays;
use RZP\Gateway\Enach\Base\CategoryCode;

class Gateway extends Base\Gateway
{
    protected $gateway = 'esigner_legaldesk';

    /**
     * @param array $input
     * @return array|void
     * @throws Exception\GatewayErrorException
     * @throws Exception\GatewayRequestException
     * @throws Exception\GatewayTimeoutException
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        $request = $this->getMandateCreationRequestArray($input);

        $traceContent = $request;

        unset($traceContent['headers']);

        $this->trace->info(
            TraceCode::GATEWAY_MANDATE_REQUEST,
            [
                'gateway'     => $this->gateway,
                'payment_id'  => $input['payment']['id'],
                'terminal_id' => $input['terminal']['id'],
                'request'     => $traceContent,
            ]
        );

        $response = $this->sendGatewayRequest($request);

        $response = json_decode($response->body, true);

        $this->trace->info(
            TraceCode::GATEWAY_MANDATE_RESPONSE,
            [
                'gateway'     => $this->gateway,
                'payment_id'  => $input['payment']['id'],
                'terminal_id' => $input['terminal']['id'],
                'response'    => $response,
            ]
        );

        if ($response[ResponseFields::STATUS] !== Status::SUCCESS)
        {
            throw new Exception\GatewayErrorException(Error\ErrorCode::GATEWAY_ERROR_MANDATE_CREATION_FAILED,
                $response[ResponseFields::ERROR_CODE],
                $response[ResponseFields::ERROR],
                [
                    'payment_id'              => $input['payment']['id'],
                    'token_id'                => $input['token']['id'],
                    'mandate_create_response' => $response,
                ]);
        }

        return $this->getRedirectRequestArray($input, $response);
    }

    /**
     * @param array $input
     * @return array|null
     * @throws Exception\GatewayErrorException
     * @throws Exception\GatewayRequestException
     * @throws Exception\GatewayTimeoutException
     */
    public function callback(array $input)
    {
        parent::callback($input);

        if ((isset($input['gateway'][ResponseFields::CALLBACK_STATUS]) === false) or
            ($input['gateway'][ResponseFields::CALLBACK_STATUS] !== Status::SUCCESS))
        {
            $status = $input['gateway'][ResponseFields::CALLBACK_STATUS] ?? null;
            $message = $input['gateway'][ResponseFields::MESSAGE] ?? null;

            throw new Exception\GatewayErrorException(
                Error\ErrorCode::GATEWAY_ERROR_MANDATE_CREATION_FAILED,
                $status,
                $message,
                [
                    'payment_id' => $input['payment']['id'],
                    'token_id' => $input['token']['id'],
                    'gateway_content' => $input['gateway'],
                ]
            );
        }

        $response = $this->getMandateStatusAndSignedXml($input['gateway']['emandate_id'], $input);

        if ($response[ResponseFields::STATUS] !== Status::SUCCESS)
        {
            throw new Exception\GatewayErrorException(
                Error\ErrorCode::GATEWAY_ERROR_MANDATE_CREATION_FAILED,
                $response[ResponseFields::ERROR_CODE],
                $response[ResponseFields::ERROR],
                [
                    'payment_id'       => $input['payment']['id'],
                    'token_id'         => $input['token']['id'],
                    'gateway'          => $this->gateway,
                    'gateway_response' => $response,
                ]
            );
        }

        $mandateXml = base64_decode($response[ResponseFields::CONTENT]);

        if (empty($mandateXml) === true)
        {
            throw new Exception\GatewayErrorException(
                Error\ErrorCode::GATEWAY_ERROR_MANDATE_CREATION_FAILED);
        }

        $content = [
            'signed_xml' => $mandateXml
        ];

        return $content;
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $gatewayPayment = $verify->payment;

        $verify->verifyResponseContent = $this->getMandateStatusAndSignedXml(
            $gatewayPayment['gateway_reference_id'],
            $verify->input
        );
    }

    /**
     * @param $mandateId
     * @param array $input
     * @return array
     *
     * This can be called from both verify and from the callback.
     * @throws Exception\GatewayRequestException
     * @throws Exception\GatewayTimeoutException
     */
    protected function getMandateStatusAndSignedXml($mandateId, $input)
    {
        $content = [
            'emandate_id'        => $mandateId,
            'type'               => Type::CREATE,
            'mandate_request_id' => $input['token']['id'],
        ];

        $request = $this->getStandardRequestArray($content, 'POST', 'fetch', true);

        $traceContent = $request;

        unset($traceContent['headers']);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request'    => $traceContent,
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
                'mandate_id' => $mandateId,
            ]
        );

        $response =  $this->sendGatewayRequest($request);

        $content = json_decode($response->body, true);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'response'   => $content,
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
            ]
        );

        return $content;
    }

    protected function getRedirectRequestArray($input, $response)
    {
        $request = [
            'url'     => $response[ResponseFields::QUICK_INVITE_URL],
            'method'  => 'get',
            'content' => [
                'reference_id' => $response[ResponseFields::EMANDATE_ID],
            ],
        ];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
                'request'    => $request
            ]
        );

        return $request;
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

        // Their verify response does not have amount. So, we set it to false without the check.
        $verify->amountMismatch = false;

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $this->saveSignedXml($verify);
    }

    protected function saveSignedXml(Verify $verify)
    {
        $gatewayPayment = $verify->payment;

        if ($verify->gatewaySuccess === true)
        {
            $mandateXml = base64_decode($verify->verifyResponseContent[ResponseFields::CONTENT]) ?? null;

            $dt = Carbon::now(Timezone::IST);
            $nextWorkingDayTimestamp = Holidays::getNextWorkingDay($dt)->getTimestamp();

            $content = [
                'signed_xml'        => $mandateXml,
                'registration_date' => $nextWorkingDayTimestamp,
            ];

            $gatewayPayment->fill($content);

            $this->repo->saveOrFail($gatewayPayment);

            return $gatewayPayment;
        }
    }

    protected function checkVerifyGatewaySuccess($verify)
    {
        // Initially assume gatewaySuccess is false
        $verify->gatewaySuccess = false;

        if ($verify->verifyResponseContent[ResponseFields::STATUS] === Status::SUCCESS)
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function getMandateCreationRequestArray(array $input)
    {
        $nextWorkingDt = $this->getNextWorkingDate($input)->format('Y-m-d');

        $destinationBankIfsc = $input['token']->getIfsc();

        $bank = $input['payment']['bank'];

        if (in_array($bank, Payment\Processor\Netbanking::$inconsistentIfsc) === true)
        {
            $bank = array_search ($bank, Payment\Processor\Netbanking::$defaultInconsistentBankCodesMapping);
        }

        $mcc = $this->input['terminal']['category'];

        $content = [
            RequestFields::REFERENCE_ID               => $input['payment']['id'],
            RequestFields::DEBTOR_ACCOUNT_TYPE        => Constants::DEBTOR_ACCOUNT_TYPE_SAVINGS,
            RequestFields::PHONE_NUMBER               => $input['payment']['contact'],
            RequestFields::DEBTOR_ACCOUNT_ID          => $input['token']->getAccountNumber(),
            RequestFields::INSTRUCTED_AGENT_ID_TYPE   => Constants::INSTRUCTED_AGENT_ID_TYPE_IFSC,
            RequestFields::INSTRUCTED_AGENT_ID        => $destinationBankIfsc,
            RequestFields::OCCURANCE_SEQUENCE_TYPE    => Constants::OCCURANCE_SEQUENCE_TYPE_RECURRING,
            RequestFields::OCCURANCE_FREQUENCY_TYPE   => Constants::OCCURANCE_FREQUENCY_TYPE_ADHOC,
            RequestFields::DEBTOR_NAME                => $input['token']->getBeneficiaryName(),
            RequestFields::FIRST_COLLECTION_DATE      => $nextWorkingDt,
            RequestFields::COLLECTION_AMOUNT_TYPE     => Constants::COLLECTION_AMOUNT_TYPE_MAXIMUM,
            RequestFields::AMOUNT                     => $input['token']->getMaxAmount() / 100,
            RequestFields::MANDATE_TYPE_CATEGORY_CODE => 'C001', // as of now Legaldesk is only accepting this.
            RequestFields::INSTRUCTED_AGENT_CODE      => $bank,
            RequestFields::ESIGN_TYPE                 => Constants::ESIGN_TYPE_OTP,
            RequestFields::AUTHENTICATION_MODE        => Constants::DEFAULT_AUTHENTICATION_MODE,
            RequestFields::IS_UNTIL_CANCELLED         => 'true',
        ];

        if ($input['token']->getExpiredAt() !== null)
        {
            $finalCollection = Carbon::now(Timezone::IST)->setTimestamp($input['token']->getExpiredAt());

            $content[RequestFields::FINAL_COLLECTION_DATE] = $finalCollection->format('Y-m-d');
            unset($content[RequestFields::IS_UNTIL_CANCELLED]);
        }

        if ($input['payment']['auth_type'] === Payment\AuthType::AADHAAR_FP)
        {
            $content[RequestFields::ESIGN_TYPE] = Constants::ESIGN_TYPE_BIOMETRIC;
        }

        return $this->getStandardRequestArray($content, 'POST', 'create');
    }

    protected function getStandardRequestArray($content = [], $method = 'post', $type = null, $json = true)
    {
        $headers = [];

        if ($json === true)
        {
            $content = json_encode($content);

            $headers = [
                'Content-Type'  => 'application/json',
            ];
        }

        $headers = array_merge($headers, [
            RequestFields::REST_API_KEY   => $this->getApiKey(),
            RequestFields::APPLICATION_ID => $this->getApplicationId(),
        ]);

        $request = parent::getStandardRequestArray($content, $method, $type);

        $request['headers'] = $headers;

        return $request;
    }

    public function preProcessServerCallback($input, $gateway = null, $mode = null): array
    {
        return $input;
    }

    public function getPaymentIdFromServerCallback(array $response)
    {
        return $response[RequestFields::REFERENCE_ID];
    }

    protected function getApiKey(): string
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_api_key'];
        }

        return $this->config['live_api_key'];
    }

    protected function getGatewayMerchantId2()
    {
        if ($this->mode === Mode::LIVE)
        {
            return $this->getLiveMerchantId2();
        }

        return $this->getTestMerchantId2();
    }

    protected function getTerminalAccessCode(array $input)
    {
        if ($this->mode === Mode::LIVE)
        {
            return $input['terminal']['gateway_access_code'];
        }

        return $this->getTestAccessCode();
    }

    protected function getGatewayMerchantId()
    {
        if ($this->mode === Mode::LIVE)
        {
            return $this->getLiveMerchantId();
        }

        return $this->getTestMerchantId();
    }

    protected function getApplicationId(): string
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_application_id'];
        }

        return $this->config['live_application_id'];
    }

    protected function getNextWorkingDate($input)
    {
        if (isset($input['gateway']['next_working_dt']) === true)
        {
            return $input['gateway']['next_working_dt'];
        }

        $paymentCreatedAt = $input['payment']['created_at'];

        $dt = Carbon::createFromTimestamp($paymentCreatedAt, Timezone::IST);

        return Holidays::getNextWorkingDay($dt);
    }

    protected function getRepository()
    {
        $gateway = 'enach';

        return $this->app['repo']->$gateway;
    }
}
