<?php

namespace RZP\Gateway\Esigner\Legaldesk;

use Carbon\Carbon;

use RZP\Error;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Gateway\Esigner\Base;
use RZP\Models\Settlement\Holidays;
use RZP\Models\Bank\Name as BankName;
use RZP\Gateway\Enach\Base\CategoryCode;

class Gateway extends Base\Gateway
{
    protected $gateway = 'esigner_legaldesk';

    protected $map = [
        RequestFields::DEBTOR_ACCOUNT_TYPE        => Base\Entity::ACCOUNT_TYPE,
        RequestFields::DEBTOR_ACCOUNT_ID          => Base\Entity::ACCOUNT_NUMBER,
        RequestFields::INSTRUCTED_AGENT_ID_TYPE   => Base\Entity::AGENT_TYPE,
        RequestFields::INSTRUCTED_AGENT_ID        => Base\Entity::AGENT_ID,
        RequestFields::INSTRUCTED_AGENT_NAME      => Base\Entity::AGENT_NAME,
        RequestFields::OCCURANCE_SEQUENCE_TYPE    => Base\Entity::SEQUENCE_TYPE,
        RequestFields::OCCURANCE_FREQUENCY_TYPE   => Base\Entity::FREQUENCY_TYPE,
        RequestFields::FIRST_COLLECTION_DATE      => Base\Entity::START_DATE,
        RequestFields::FINAL_COLLECTION_DATE      => Base\Entity::END_DATE,
        RequestFields::COLLECTION_AMOUNT_TYPE     => Base\Entity::AMOUNT_TYPE,
        RequestFields::AMOUNT                     => Base\Entity::AMOUNT,
        RequestFields::MANDATE_TYPE_CATEGORY_CODE => Base\Entity::CATEGORY_CODE,
        RequestFields::EMANDATE_ID                => Base\Entity::MANDATE_ID,
    ];

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

        list($request, $gatewayPayment) = $this->getMandateCreationRequestArray($input);

        $this->trace->info(
            TraceCode::GATEWAY_MANDATE_REQUEST,
            [
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
                'request'    => $request
            ]
        );

        $response = $this->sendGatewayRequest($request);

        $response = json_decode($response->body, true);

        $this->updateGatewayPaymentEntity($gatewayPayment, $response);

        $this->trace->info(
            TraceCode::GATEWAY_MANDATE_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
                'response'   => $response
            ]
        );

        if ($response[ResponseFields::STATUS] !== Status::SUCCESS)
        {
            throw new Exception\GatewayErrorException(Error\ErrorCode::GATEWAY_ERROR_MANDATE_CREATION_FAILED,
                $response[ResponseFields::ERROR_CODE],
                $response[ResponseFields::ERROR],
                [
                    'payment_id'             => $input['payment']['id'],
                'token_id'                   => $input['token']['id'],
                    'mandate_crete_response' => $response,
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

        if ((isset($input['gateway']['status']) === false) or
            ($input['gateway']['status'] !== Status::SUCCESS))
        {
            $status = $input['gateway'][ResponseFields::STATUS] ?? null;
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

        $request = $this->getMandateFetchRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $response = json_decode($response->body, true);

        if ($response[ResponseFields::STATUS] != Status::SUCCESS)
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

    protected function getRedirectRequestArray($input, $response)
    {
        $request = [
            'url'     => $response[ ResponseFields::QUICK_INVITE_URL ],
            'method'  => 'get',
            'content' => [
                'reference_id' => $response[ ResponseFields::EMANDATE_ID ],
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

    protected function getMandateCreationRequestArray(array $input)
    {
        $nextWorkingDt = $this->getNextWorkingDate($input)->format('Y-m-d');

        $finalCollection = Carbon::createFromTimestamp($input['token']->getExpiredAt(), Timezone::IST)->format('Y-m-d');

        $destinationBankIfsc = $input['token']->getIfsc();

        $mcc = $this->input['terminal']['category'];

        $content = [
            RequestFields::REFERENCE_ID               => $input['payment']['id'],
            RequestFields::MANDATE_REQUEST_ID         => $input['token']['id'],
            RequestFields::DEBTOR_ACCOUNT_TYPE        => Constants::DEBTOR_ACCOUNT_TYPE_SAVINGS,
            RequestFields::DEBTOR_ACCOUNT_ID          => $input['token']->getAccountNumber(),
            RequestFields::INSTRUCTED_AGENT_ID_TYPE   => Constants::INSTRUCTED_AGENT_ID_TYPE_IFSC,
            RequestFields::INSTRUCTED_AGENT_ID        => $destinationBankIfsc,
            RequestFields::INSTRUCTED_AGENT_NAME      => BankName::getName($destinationBankIfsc),
            RequestFields::OCCURANCE_SEQUENCE_TYPE    => Constants::OCCURANCE_SEQUENCE_TYPE_RECURRING,
            RequestFields::OCCURANCE_FREQUENCY_TYPE   => Constants::OCCURANCE_FREQUENCY_TYPE_ADHOC,
            RequestFields::DEBTOR_NAME                => $input['token']->getBeneficiaryName(),
            RequestFields::FIRST_COLLECTION_DATE      => $nextWorkingDt,
            RequestFields::FINAL_COLLECTION_DATE      => $finalCollection,
            RequestFields::COLLECTION_AMOUNT_TYPE     => Constants::COLLECTION_AMOUNT_TYPE_MAXIMUM,
            RequestFields::AMOUNT                     => $input['token']->getMaxAmount() / 100,
            RequestFields::MANDATE_TYPE_CATEGORY_CODE => CategoryCode::getCategoryCodeFromMcc($mcc),
            RequestFields::CALLBACK_URL               => $this->input['callbackUrl'],
        ];

        $gatewayPayment = $this->createGatewayPaymentEntity($content);

        return [
            $this->getStandardRequestArray($content, 'POST', 'create'),
            $gatewayPayment
        ];
    }

    protected function getMandateFetchRequestArray(array $input)
    {
        $content = [
            RequestFields::EMANDATE_ID        => $input['gateway'][ResponseFields::EMANDATE_ID],
            RequestFields::MANDATE_REQUEST_ID => $input['token']['id'],
        ];

        return $this->getStandardRequestArray($content, 'GET', 'fetch', false);
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

    protected function getApiKey(): string
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_api_key'];
        }

        return $this->config['live_api_key'];
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
}
