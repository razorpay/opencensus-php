<?php

namespace RZP\Models\Batch\Processor;

use RZP\Constants;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Models\Batch\Entity;
use RZP\Exception\BaseException;
use RZP\Models\Batch\Processor\AESCrypto;

class UpiTerminalOnboarding extends Base
{
    public function processEntry(array & $entry)
    {
        $merchantId         = $entry[Batch\Header::UPI_TERMINAL_ONBOARDING_MERCHANT_ID];
        $gateway            = $entry[Batch\Header::UPI_TERMINAL_ONBOARDING_GATEWAY];
        $vpa                = $entry[Batch\Header::UPI_TERMINAL_ONBOARDING_VPA];
        $gatewayTerminalId  = $entry[Batch\Header::UPI_TERMINAL_ONBOARDING_GATEWAY_TERMINAL_ID];
        $gatewayAccessCode  = $entry[Batch\Header::UPI_TERMINAL_ONBOARDING_GATEWAY_ACCESS_CODE];
        $expected           = $entry[Batch\Header::UPI_TERMINAL_ONBOARDING_EXPECTED];
        $vpaHandle          = $entry[Batch\Header::UPI_TERMINAL_ONBOARDING_VPA_HANDLE];


        $identifiers = [
            Terminal\Entity::VPA                  => $vpa,
            Terminal\Entity::GATEWAY_TERMINAL_ID  => $gatewayTerminalId,
            Terminal\Entity::GATEWAY_ACCESS_CODE  => $gatewayAccessCode,
            'vpa_handle'                          => $vpaHandle,
        ];

        $features = [
            Terminal\Entity::EXPECTED   =>  $expected
        ];

        $response = $this->app['terminals_service']->initiateOnboarding($merchantId, $gateway, $identifiers, $features, [], []);

        if (isset($response['terminal'][Terminal\Entity::ID]) === true)
        {
            $entry[Batch\Header::STATUS]            = Batch\Status::SUCCESS;

            $entry[Batch\Header::TERMINAL_ID]       = $response['terminal'][Terminal\Entity::ID];
        }
    }
}
