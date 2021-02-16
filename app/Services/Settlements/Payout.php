<?php

namespace RZP\Services\Settlements;

use RZP\Exception;
use RZP\Models\Payout\Entity;

class Payout extends Base
{
    const STATUS_UPDATE         = '/twirp/rzp.settlements.transfer.v1.TransferService/StatusUpdatePayout';

    public function __construct($app)
    {
        parent::__construct($app);
    }

    /**
     * Status update upon receiving webhook from payout
     * @param array $input
     * @param string $mode
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function sendStatusUpdate(array $input, string $mode) : array
    {
        $this->makeRequest(self::STATUS_UPDATE, $input, self::SERVICE_PAYOUT, $mode);
    }

    public function pushPayoutStatusUpdate(Entity $payout, string $mode)
    {
        $dataToSend = $this->getDataFromPayout($payout);

        $this->sendStatusUpdate($dataToSend, $mode);
    }

    protected function getDataFromPayout(Entity $payout): array
    {
        return [
            'id'              => $payout->getId(),
            'entity'          => $payout->getEntity(),
            'fund_account_id' => $payout->getFundAccountId(),
            'amount'          => $payout->getAmount(),
            'currency'        => $payout->getCurrency(),
            'notes'           => $payout->getNotes(),
            'fees'            => $payout->getFees(),
            'tax'             => $payout->getTax(),
            'status'          => $payout->getStatus(),
            'purpose'         => $payout->getPurpose(),
            'utr'             => $payout->getUtr(),
            'mode'            => $payout->getMode(),
            'channel'         => $payout->getChannel(),
            'remark'          => $payout->getRemarks(),
            'reference_id'    => $payout->getReferenceId(),
            'narration'       => $payout->getNarration(),
            'batch_id'        => $payout->getBatchId(),
            'failure_reason'  => $payout->getFailureReason(),
            'created_at'      => $payout->getCreatedAt(),
        ];
    }
}
