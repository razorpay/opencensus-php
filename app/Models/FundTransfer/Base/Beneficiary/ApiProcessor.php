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
     * @param PublicCollection $accounts
     * @return array
     */
    public function registerBeneficiary(PublicCollection $accounts): array
    {
        $this->process($accounts, 'REGISTER');

        $response = [
            'body'           => 'Beneficiaries registration request sent to ' . ucfirst($this->channel),
            'channel'        => $this->channel,
            'account_type'   => $this->accountType,
            'register_count' => $accounts->count(),
            'total_count'    => $accounts->count(),
        ];

        return $response;
    }

    /**
     * Queues the bank account which has to be registered with the given channel
     *
     * @param PublicCollection $accounts
     * @return array
     */
    public function verifyBeneficiary(PublicCollection $accounts): array
    {
        $this->process($accounts, 'VERIFY');

        $response = [
            'body'           => 'Beneficiaries verification request sent to ' . ucfirst($this->channel),
            'channel'        => $this->channel,
            'account_type'   => $this->accountType,
            'register_count' => $accounts->count(),
            'total_count'    => $accounts->count(),
        ];

        return $response;
    }

    /**
     * Will send slack notification on bene registration status
     */
    protected function notify()
    {
        $failureCount = count($this->summary);

        $data = [
            'channel'       => $this->channel,
            'total'         => $this->count,
            'failure_count' => $failureCount,
        ];

        (new SlackNotification)->send('bene_reg_status', $data, null, $failureCount);

        $this->trace->error(
            TraceCode::BENEFICIARY_REGISTRATION_SUMMARY,
            $data);
    }

    abstract public function process(PublicCollection $accounts, $action);
}
