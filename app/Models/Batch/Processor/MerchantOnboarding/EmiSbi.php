<?php

namespace RZP\Models\Batch\Processor\MerchantOnboarding;

use RZP\Models\Batch;
use RZP\Models\Terminal;
use RZP\Models\Batch\Header;
use RZP\Models\Payment\Gateway;
use RZP\Models\Batch\Processor\Base as BaseProcessor;

class EmiSbi extends BaseProcessor
{
    protected $gateway = Gateway::EMI_SBI;

    protected function processEntry(array & $entry)
    {
        $entry[Header::STATUS] = Batch\Status::FAILURE;

        $merchantId = $entry[Header::MERCHANT_0NBOARDING_EMI_SBI_MID];
        $gatewayMid = $entry[Header::MERCHANT_0NBOARDING_EMI_SBI_GATEWAY_MID];
        $gatewayTid = $entry[Header::MERCHANT_0NBOARDING_EMI_SBI_GATEWAY_TID];

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $createTerminalInput = [
            Terminal\Entity::GATEWAY_MERCHANT_ID => $gatewayMid,
            Terminal\Entity::GATEWAY_TERMINAL_ID => $gatewayTid,
        ];

        (new Terminal\Core)->create($createTerminalInput, $merchant);

        $entry[Header::STATUS] = Batch\Status::SUCCESS;
    }
}
