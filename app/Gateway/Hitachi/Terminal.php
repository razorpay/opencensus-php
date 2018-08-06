<?php

namespace RZP\Gateway\Hitachi;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Constants\IndianStates;
use RZP\Models\Merchant\Detail;
use RZP\Models\Currency\Currency;

class Terminal extends Base\Terminal
{
    const TEST = 'https://electronix.hitachi-payments.com:8443/PGMerchantBoarding/MerchantBoarding';
    const LIVE = ''; // TODO: get Url after testing on UAT

    protected $gateway = 'hitachi';

    protected function getInputValidationRules()
    {
        return [
            TerminalFields::MID                  => 'required|alpha_num|size:15',
            TerminalFields::TID                  => 'required|alpha_num|size:8',
            TerminalFields::TRANS_MODE           => 'required|string',
            TerminalFields::CURRENCY             => 'required|string',
            TerminalFields::MCC                  => 'required|numeric|digits:4'
        ];
    }

    protected function getMerchantDetailsValidationRules()
    {
        return [
            Detail\Entity::BUSINESS_OPERATION_ADDRESS       => 'required|string',
            Detail\Entity::BUSINESS_OPERATION_STATE         => 'required|string',
            Detail\Entity::BUSINESS_OPERATION_PIN           => 'required|numeric|digits:6',
            Detail\Entity::BUSINESS_DBA                     => 'required|string|max:25',
            Detail\Entity::BUSINESS_NAME                    => 'sometimes|string',
            Detail\Entity::BUSINESS_OPERATION_CITY          => 'sometimes|string|max:13',
        ];
    }

    protected function getOnboardRequestArray(array $input, array $merchantDetail)
    {
        if (strlen($merchantDetail['business_operation_state']) === 2)
        {
            $state = strtoupper($merchantDetail['business_operation_state']);
        }
        else
        {
            $state = IndianStates::getStateCode(strtoupper($merchantDetail['business_operation_state']));
        }

        $currency = Currency::getIsoCode($input[TerminalFields::CURRENCY]);

        $terminalProperties = new TerminalProperties();

        $content = [
            TerminalFields::COUNTRY              => $terminalProperties->getCountry(),
            TerminalFields::PROCESS_ID           => $terminalProperties->getProcessId(),
            TerminalFields::CUSTOMER_NO          => $terminalProperties->getCustomerNo(),
            TerminalFields::EXISTING_MERCHANT    => $terminalProperties->getExistingMerchant(),
            TerminalFields::SPONSOR_BANK         => $terminalProperties->getSponserBank(),
            TerminalFields::ACTION_CODE          => $terminalProperties->getActionCode(),
            TerminalFields::S_NO                 => $terminalProperties->getSno(),
            TerminalFields::SUPER_MID            => $terminalProperties->getSuperMid(),
            TerminalFields::MERCHANT_STATUS      => $terminalProperties->getMerchantStatus(),
            TerminalFields::BANK                 => $terminalProperties->getBank(),
            TerminalFields::INTERNATIONAL        => $terminalProperties->getInternational(),
            TerminalFields::TERMINAL_ACTIVE      => $terminalProperties->getTerminalActive(),

            //Gateway specific input
            TerminalFields::MID                  => $input[TerminalFields::MID],
            TerminalFields::MCC                  => (string) $input[TerminalFields::MCC],
            TerminalFields::TID                  => $input[TerminalFields::TID],
            TerminalFields::CURRENCY             => $currency,
            TerminalFields::TRANS_MODE           => $input[TerminalFields::TRANS_MODE],

            //Merchant detail based input
            TerminalFields::CITY                 => substr($merchantDetail['business_operation_city'], 0 ,13),
            TerminalFields::MERCHANT_GROUP       => substr($merchantDetail['business_name'], 0, 8),
            TerminalFields::MERCHANT_NAME        => substr($merchantDetail['business_name'], 0, 25),
            TerminalFields::ZIPCODE              => $merchantDetail['business_operation_pin'],
            TerminalFields::MERCHANT_DB_NAME     => substr($merchantDetail['business_dba'], 0, 25),
            TerminalFields::LOCATION             => substr($merchantDetail['business_operation_address'], 0, 23),
            TerminalFields::STATE                => $state,
        ];

        return $this->getStandardRequestArray($content);
    }

    protected function getStandardRequestArray($content = [], $method = 'post')
    {
        $body = json_encode($content);

        $request = parent::getStandardRequestArray($body, $method);

        $request['headers']['Content-Type'] = 'application/json';

        $request['options'] = [
            'timeout'         => 30,
            'connect_timeout' => 30,
            'verify'          => $this->getCaInfo(),
        ];

        return $request;
    }

    protected function parseOnboardResponse($input, $response)
    {
        if ((isset($response[TerminalFields::RESPONSE_CODE]) === true) and
            ($response[TerminalFields::RESPONSE_CODE] === TerminalFields::SUCCESS))
        {
            return [
                    TerminalFields::TERMINAL_CREATION_CATEGORY      => $input[TerminalFields::MCC],
                    TerminalFields::TERMINAL_CREATION_GATEWAY       => 'hitachi',
                    TerminalFields::TERMINAL_CREATION_GATEWAY_MID   => $response[TerminalFields::GATEWAY_MID],
                    TerminalFields::TERMINAL_CREATION_GATEWAY_TID   => $response[TerminalFields::GATEWAY_TID],
                    TerminalFields::TERMINAL_CREATION_ACQUIRER      => 'ratn',
                    TerminalFields::TERMINAL_CREATION_CURRENCY      => $input[TerminalFields::CURRENCY],
            ];
        }
        else
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_ONBOARDING_FAILED,
                $response[TerminalFields::RESPONSE_CODE],
                $response[TerminalFields::RESPONSE_DESC]);
        }
    }

    protected function getUrl($type = null)
    {
        if ($this->mode === Mode::TEST)
        {
            return self::TEST;
        }
        else
        {
            return self::LIVE;
        }
    }

    protected function sendGatewayRequest($request)
    {
        return parent::sendGatewayRequest($request);
    }

    protected function getCaInfo()
    {
        $clientCertPath = dirname(__FILE__) . '/cainfo/onboard.cainfo.pem';

        return $clientCertPath;
    }
}
