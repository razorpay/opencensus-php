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
        $recurring          = $entry[Batch\Header::UPI_TERMINAL_ONBOARDING_RECURRING] ?? false;
        $mcc                = $entry[Batch\Header::UPI_TERMINAL_ONBOARDING_MCC] ?? null;
        $category2          = $entry[Batch\Header::UPI_TERMINAL_ONBOARDING_CATEGORY2] ?? null;
        $merchantType       = $entry[Batch\Header::UPI_TERMINAL_ONBOARDING_MERCHANT_TYPE];
        $allowCC            = $entry[Batch\Header::UPI_TERMINAL_ONBOARDING_ALLOW_CC];
        $allowWallet        = $entry[Batch\Header::UPI_TERMINAL_ONBOARDING_ALLOW_WALLET];
        $allowCreditLine    = $entry[Batch\Header::UPI_TERMINAL_ONBOARDING_ALLOW_CREDIT_LINE];
        $directPush         = $entry[Batch\Header::UPI_TERMINAL_ONBOARDING_DIRECT_PUSH];

        $identifiers = [
            Terminal\Entity::VPA                  => $vpa,
            Terminal\Entity::GATEWAY_TERMINAL_ID  => $gatewayTerminalId,
            Terminal\Entity::GATEWAY_ACCESS_CODE  => $gatewayAccessCode,
            'vpa_handle'                          => $vpaHandle,
        ];

        if(empty($mcc) === false)
        {
            $identifiers[Terminal\Entity::CATEGORY] = $mcc;
        }

        $features = [
            Terminal\Entity::EXPECTED               => $expected,
            Terminal\Entity::UPI_FEATURES_TYPE      => $merchantType,
            Terminal\Entity::CC_ON_UPI              => (boolval($allowCC) === true) ? '1' : '0',
            Terminal\Entity::WALLET_ON_UPI          => (boolval($allowWallet) === true) ? '1' : '0',
            Terminal\Entity::CREDIT_LINE_ON_UPI     => (boolval($allowCreditLine) === true) ? '1' : '0',
            Terminal\Entity::DIRECT_PUSH_ON_UPI     => $directPush
        ];

        if (empty($merchantType) === false and
            strtolower($merchantType) === Terminal\Type::ONLINE)
        {
            $features[Terminal\Type::ONLINE] = '1';
        }

        if (empty($merchantType) === false and
            strtolower($merchantType) === Terminal\Type::OFFLINE)
        {
            $features[Terminal\Type::OFFLINE] = '1';
        }

        $otherInputs = [];

        if ((($gateway === Payment\Gateway::UPI_ICICI) or ($gateway === Payment\Gateway::UPI_RZPAPB) or ($gateway === Payment\Gateway::UPI_YESBANK)) and
            (boolval($recurring) === true))
        {
            $features['recurring'] = '1';

            $identifiers[Terminal\Entity::GATEWAY_ACCESS_CODE] = 'v4';

            $merchant = $this->repo->merchant->findOrFail($merchantId);

            $identifiers[Terminal\Entity::CATEGORY] = $merchant->getCategory() ?? "";

            $identifiers[Terminal\Entity::NETWORK_CATEGORY] = $merchant->getCategory2() ?? "";

            if((empty($mcc) === true) xor
               (empty($category2) === true))
            {
                throw new LogicException("Mcc and Category2 should be both sent together and can't be sent seperately");
            }

            if((empty($category2) === false) and
               (empty($mcc) === false))
            {
                $identifiers[Terminal\Entity::CATEGORY] = $mcc;

                $identifiers[Terminal\Entity::NETWORK_CATEGORY] = $category2;
            }

            if ($gateway === Payment\Gateway::UPI_ICICI) {
                $otherInputs[Terminal\Entity::SECRETS] = [Terminal\Entity::GATEWAY_TERMINAL_PASSWORD => app('config')->get("gateway.upi_icici.live_recurring_onboarding_api_key")];
            }
        }

        $response = $this->app['terminals_service']->initiateOnboarding($merchantId, $gateway, $identifiers, $features, [], $otherInputs);

        if (isset($response['terminal'][Terminal\Entity::ID]) === true)
        {
            $entry[Batch\Header::STATUS]            = Batch\Status::SUCCESS;

            $entry[Batch\Header::TERMINAL_ID]       = $response['terminal'][Terminal\Entity::ID];
        }

        if (isset($response['terminal'][Terminal\Entity::GATEWAY_VPA_WHITELISTED]) === true)
        {
            $entry[Batch\Header::VPA_WHITELISTED]       = $response['terminal'][Terminal\Entity::GATEWAY_VPA_WHITELISTED];
        }
    }
}
