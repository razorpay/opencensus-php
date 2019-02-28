<?php

namespace RZP\Models\Batch\Processor\MerchantOnboarding;

use Mail;

use RZP\Models\Batch;
use RZP\Models\Terminal;
use RZP\Models\Batch\Header;
use RZP\Models\Payment\Gateway;
use RZP\Mail\Batch\MerchantOnboarding as MerchantOnboardingMail;
use RZP\Models\Batch\Processor\Base as BaseProcessor;

class EmiSbi extends BaseProcessor
{
    protected $gateway = Gateway::EMI_SBI;

    protected function processEntry(array & $entry)
    {
        $entry[Header::STATUS] = Batch\Status::FAILURE;

        $merchantId = $entry[Header::MERCHANT_ONBOARDING_EMI_SBI_MID];
        $gatewayMid = $entry[Header::MERCHANT_ONBOARDING_EMI_SBI_GATEWAY_MID];
        $gatewayTid = $entry[Header::MERCHANT_ONBOARDING_EMI_SBI_GATEWAY_TID];

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $createTerminalInput = [
            Terminal\Entity::GATEWAY_MERCHANT_ID => $gatewayMid,
            Terminal\Entity::GATEWAY_TERMINAL_ID => $gatewayTid,
            Terminal\Entity::GATEWAY             => Gateway::EMI_SBI,
            Terminal\Entity::ENABLED             => '1',
        ];

        $terminal = (new Terminal\Core)->create($createTerminalInput, $merchant);

        $entry[Header::STATUS ]                              = Batch\Status::SUCCESS;
        $entry[Header::MERCHANT_ONBOARDING_EMI_SBI_RZP_TID] = $terminal->id;
    }

    protected function sendProcessedMail()
    {
        // The batch settings would be used to identify the gateway while sending mail
        $mail = new MerchantOnboardingMail(
            $this->batch->toArray(),
            $this->merchant->toArray(),
            $this->outputFileLocalPath,
            [
                'gateway' => 'SBI EMI',
            ]);

        Mail::send($mail);
    }

    public function getOutputFileHeadings(): array
    {
        return Batch\Header::HEADER_MAP['merchant_onboarding_emi_sbi'][Batch\Header::OUTPUT] ?? [];
    }
}
