<?php


namespace RZP\Models\Batch\Processor;

use RZP\Constants;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Models\Batch\Entity;
use RZP\Exception\BaseException;
use RZP\Exception\LogicException;
use RZP\Models\Batch\Processor\AESCrypto;

class UpiOnboardedTerminalEdit extends Base
{
    public function processEntry(array & $entry){
        $terminalId         = $entry[Batch\Header::UPI_ONBOARDED_TERMINAL_EDIT_TERMINAL_ID];
        $gateway            = $entry[Batch\Header::UPI_ONBOARDED_TERMINAL_EDIT_GATEWAY];
        $recurring          = $entry[Batch\Header::UPI_ONBOARDED_TERMINAL_EDIT_RECURRING] ?? false;
        $online             = $entry[Batch\Header::UPI_ONBOARDED_TERMINAL_EDIT_ONLINE] ?? false;
        $vpa                = $entry[Batch\Header::UPI_TERMINAL_ONBOARDING_VPA];
        $gatewayTerminalId  = $entry[Batch\Header::UPI_TERMINAL_ONBOARDING_GATEWAY_TERMINAL_ID];
        $gatewayAccessCode  = $entry[Batch\Header::UPI_TERMINAL_ONBOARDING_GATEWAY_ACCESS_CODE];
        $vpaHandle          = $entry[Batch\Header::UPI_TERMINAL_ONBOARDING_VPA_HANDLE];
        $identifiers = [
            Terminal\Entity::VPA                  => $vpa,
            Terminal\Entity::GATEWAY_TERMINAL_ID  => $gatewayTerminalId,
            Terminal\Entity::GATEWAY_ACCESS_CODE  => $gatewayAccessCode,
            'vpa_handle'                          => $vpaHandle,
        ];
        $features = [];
        $otherInputs = [];
        if (boolval($online) === false)
        {
            $features['online'] = '0';
        }
        else
        {
            $features['online'] = '1';
        }
        if (boolval($recurring) === false)
        {
            $features['recurring'] = '0';
        }
        $response = $this->app['terminals_service']->EditOnboardedTerminal($terminalId, $gateway,$identifiers,$features,$otherInputs);
        if (isset($response['terminal'][Terminal\Entity::ID]) === true)
        {
            $entry[Batch\Header::STATUS]            = Batch\Status::SUCCESS;

            $entry[Batch\Header::VPA_WHITELISTED]       = $response['terminal'][Terminal\Entity::GATEWAY_VPA_WHITELISTED];
        }
    }
}
