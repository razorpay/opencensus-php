<?php

namespace RZP\Models\FundTransfer\Base\Beneficiary;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Settlement\SlackNotification;

abstract class RequestProcessor extends Beneficiary
{
    protected $summary = [];

    protected $count = 0;

    /**
     * Queues the bank account which has to be registered with the given channel
     *
     * @param PublicCollection $bankAccounts
     * @return array
     */
    public function registerBeneficiary(PublicCollection $bankAccounts): array
    {
        $this->process($bankAccounts);

        $response = [
            'body'            => 'Beneficiaries registration request sent to ' . ucfirst($this->channel),
            'channel'         => $this->channel
        ];

        return $response;
    }

    /**
     * Place holder method for verifyBeneficiary since parent abstract class has it.
     * @param PublicCollection $bankAccounts
     * @return array
     * @throws Exception\LogicException
     */
    public function verifyBeneficiary(PublicCollection $bankAccounts): array
    {
        throw new Exception\LogicException('Beneficiary verification not supported for channel '.$this->channel);

        return [];
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

    abstract public function process(PublicCollection $bankAccounts);
}
