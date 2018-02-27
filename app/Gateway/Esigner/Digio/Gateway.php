<?php

namespace RZP\Gateway\Esigner\Digio;

use View;
use RZP\Error;
use Carbon\Carbon;
use RZP\Exception;
use Lib\PhoneBook;
use RZP\Gateway\Base;

class Gateway extends Base\Gateway
{
    protected $gateway = 'esigner_digio';

    public function authorize(array $input)
    {
        parent::authorize($input);

        $request = $this->getMandateCreationRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        if ($response->status_code !== 200)
        {
            throw new Exception\GatewayErrorException(
                Error\ErrorCode::GATEWAY_ERROR_MANDATE_CREATION_FAILED);
        }

        return $this->getRequestForDirectType($input, $response);
    }

    public function callback(array $input)
    {
        parent::callback($input);

        if (isset($input['gateway']['digio_mandate_id']) === false)
        {
            throw new Exception\GatewayErrorException(
                Error\ErrorCode::GATEWAY_ERROR_MANDATE_CREATION_FAILED);
        }

        $request = $this->getMandateFetchRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        if ($response->status_code !== 200)
        {
            throw new Exception\GatewayErrorException(
                Error\ErrorCode::GATEWAY_ERROR_MANDATE_CREATION_FAILED);
        }

        $mandateXml = $response->body;

        $content = [
            'mandate' => $mandateXml
        ];

        return $content;
    }

    protected function getRequestForDirectType(array $input, $response)
    {
        $decodedResponse = json_decode($response->body, true);

        $mandateId = $decodedResponse['id'];

        $request = [
            'content' => json_encode([
                'signer_id'     => $mandateId,
                'identifier'    => $input['token']->getId(),
                'environment'   => Mode::map($this->mode),
            ]),
            'callback_url'  => $input['callbackUrl'],
        ];

        $content = View::make('gateway.digio')->with('request', $request)->render();

        return [
            'method' => 'direct',
            'content' => $content
        ];
    }

    protected function getMandateCreationRequestArray(array $input)
    {
        $content = [
            'signers' => [
                [
                    'identifier' => $this->getFormattedContact($input['payment']['contact'])
                ]
            ],
            'expire_in_days' => 1,
            'enach_type'     => Type::CREATE,
            'content'        => $this->getEmandateData($input)
        ];

        return $this->getStandardRequestArray($content, 'POST', 'create');
    }

    protected function getMandateFetchRequestArray(array $input)
    {
        $content = [
            'mandate_id' => $input['gateway']['digio_mandate_id'],
        ];

        return $this->getStandardRequestArray($content, 'GET', 'fetch', false);
    }

    protected function getEmandateData(array $input)
    {
        $nextWorkingDt = $this->getNextWorkingDate($input);

        $content = [
            'mandate_request_id'            => $input['token']->getId(),
            'mandate_creation_date_time'    => $nextWorkingDt->toIso8601String(),
            'sponsor_bank_id'               => '',
            'sponsor_bank_name'             => '',
            'destination_bank_id'           => '',
            'destination_bank_name'         => '',
            'aadhaar'                       => $input['token']->getAadhaarNumber(),
            'bank_identifier'               => '',
            'customer_account_type'         => 'Other',
            'management_category'           => CategoryCode::A001,
            'service_provider_name'         => '',
            'service_provider_utility_code' => '',
            'customer_account_number'       => $input['token']->getAccountNumber(),
            'instrument_type'               => Instrument::DEBIT,
            'customer_name'                 => $input['token']->getBeneficiaryName(),
            // @todo: max of 1L ruppee
            'maximum_amount'                => $input['token']->getMaxAmount(),
            'is_recurring'                  => true,
            'frequency'                     => Frequency::ADHOC,
            'first_collection_date'         => $nextWorkingDt->addDay()->format('Y-m-d'),
            'final_collection_date'         => $nextWorkingDt->addYears(5)->format('Y-m-d'),
        ];

        return json_encode($content);
    }

    protected function getNextWorkingDate(array $input)
    {
        $currentTs = $input['payment']['created_at'];

        return Carbon::createFromTimestamp($currentTs);
    }

    protected function getStandardRequestArray($content = [], $method = 'post', $type = null, $json = true)
    {
        $headers = [];

        if ($json === true)
        {
            $content = json_encode($content);

            $headers = [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ];
        }

        $request = parent::getStandardRequestArray($content, $method, $type);

        $request['headers'] = $headers;

        $request['options'] = [
            'auth' => [$this->getClientId(), $this->getClientPassword()]
        ];

        return $request;
    }

    protected function getClientId()
    {
        return $this->config['merchant_id'];
    }

    protected function getClientPassword()
    {
        return $this->config['secure_secret'];
    }

    protected function getFormattedContact($contact)
    {
        $number = new PhoneBook($contact, true);

        return $number->format(PhoneBook::DOMESTIC);
    }

    protected function getRepository()
    {
        return;
    }
}