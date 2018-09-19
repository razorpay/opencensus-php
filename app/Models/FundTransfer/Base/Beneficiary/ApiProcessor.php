<?php

namespace RZP\Models\FundTransfer\Base\Beneficiary;

use RZP\Trace\TraceCode;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Settlement\SlackNotification;

abstract class ApiProcessor extends Beneficiary
{
    protected $summary = [];

    protected $count = 0;

    /**
     * Queues the bank account which has to be registered with the given channel
     *
     * @param PublicCollection $bankAccounts
     * @return array
     */
    protected function registerBeneficiary(PublicCollection $bankAccounts): array
    {
        $this->process($bankAccounts);

        $response = [
            'body'            => 'Beneficiaries registration request sent to ' . ucfirst($this->channel),
            'channel'         => $this->channel,
            'merchants_count' => $bankAccounts->count(),
        ];

        return $response;
    }

    /**
     * Will send slack notification on bene registration status
     */
    protected function notify()
    {
        $failureCount = count($this->summary);

        $status = ($failureCount === 0) ? SlackNotification::GOOD : SlackNotification::BAD;

        $data = [
            'message'       => 'Beneficiaries Registration status',
            'channel'       => $this->channel,
            'total'         => $this->count,
            'failure_count' => $failureCount,
        ];

        (new SlackNotification())->send([
                'status'        => $status
            ] + $data);

        $this->trace->error(
            TraceCode::BENEFICIARY_REGISTRATION_SUMMARY,
            $data);
    }

    abstract public function process(PublicCollection $bankAccounts);
}
