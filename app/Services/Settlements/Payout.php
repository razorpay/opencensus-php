<?php

namespace RZP\Services\Settlements;

use RZP\Exception;
use RZP\Models\Payout\Entity;
use RZP\Models\Payout\PayoutError;
use RZP\Models\PayoutsStatusDetails as PayoutsStatusDetails;
use RZP\Trace\TraceCode;

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
        return $this->makeRequest(self::STATUS_UPDATE, $input, self::SERVICE_PAYOUT, $mode);
    }

    public function pushPayoutStatusUpdate(Entity $payout, string $mode)
    {
        $dataToSend = $this->getDataFromPayout($payout);

        $this->sendStatusUpdate($dataToSend, $mode);
    }

    protected function getDataFromPayout(Entity $payout): array
    {
        $payoutID = $payout->getId();

        // before this call; if PAYOUTS_STATUS_DETAILS_ENTITY_CREATED is
        // performed; there will be an updated status details ID; otherwise
        // existing data OR null structure will be returned
        $statusDetailsID = $payout->getStatusDetailsId();

        if ($statusDetailsID !== null)
        {
            $statusDetails = $this->preparePayoutStatusDetails($statusDetailsID, $payout);
        }
        else
        {
            $statusDetails = [
                'reason'      => null,
                'description' => null,
                'source'      => null,
            ];
            $this->trace->debug(TraceCode::SETTLEMENT_PAYOUT_UPDATE_WEBHOOK_DEBUG, [
                'payout_id' => $payoutID,
                'message'   => 'payout status details ID is NULL; so status details will be NULL'
            ]);
        }
        // this debug is needed to analyse the data provided to webhook via sumo logs
        $this->trace->info(TraceCode::SETTLEMENT_PAYOUT_UPDATE_WEBHOOK_RESPONSE, [
                'payout_id'             => $payoutID,
                'payout_status'         => $payout->getStatus(),
                'status_detail_id'      => $statusDetailsID,
                'payout_status_details' => $statusDetails,
                'failure_reason'        => $payout->getFailureReason(),
                'remark'                => $payout->getRemarks(),
        ]);

        return [
            'id'                     => $payoutID,
            'entity'                 => $payout->getEntity(),
            'fund_account_id'        => $payout->getFundAccountId(),
            'amount'                 => $payout->getAmount(),
            'currency'               => $payout->getCurrency(),
            'notes'                  => $payout->getNotes(),
            'fees'                   => $payout->getFees(),
            'tax'                    => $payout->getTax(),
            'status'                 => $payout->getStatus(),
            'purpose'                => $payout->getPurpose(),
            'utr'                    => $payout->getUtr(),
            'mode'                   => $payout->getMode(),
            'channel'                => $payout->getChannel(),
            'remark'                 => $payout->getRemarks(),
            'reference_id'           => $payout->getReferenceId(),
            'narration'              => $payout->getNarration(),
            'batch_id'               => $payout->getBatchId(),
            'failure_reason'         => $payout->getFailureReason(),
            'created_at'             => $payout->getCreatedAt(),
            'payout_status_details'  => $statusDetails,
        ];
    }

    /**
     * @param string $statusDetailsID
     * @return array
     */
    protected function preparePayoutStatusDetails(string $statusDetailsID, Entity $payout): array
    {
        $statusDetails = (new PayoutsStatusDetails\Repository())->fetchStatusDetailsFromStatusDetailsId($statusDetailsID);

        if ($statusDetails !== null)
        {
            $this->trace->debug(TraceCode::SETTLEMENT_PAYOUT_UPDATE_WEBHOOK_DEBUG, [
                'status_details_id' => $statusDetailsID,
                'message'           => 'status details found in DB'
            ]);

            $source = $this->getSourceForStatusDetails($statusDetails, $payout);

            // if status details exist
            return [
                'reason'        => $statusDetails['reason'],
                'description'   => $statusDetails['description'],
                'source'        => $source,
            ];
        }

        $this->trace->debug(TraceCode::SETTLEMENT_PAYOUT_UPDATE_WEBHOOK_DEBUG, [
            'status_details_id' => $statusDetailsID,
            'message'           => 'status details not present for this ID'
        ]);

        // if status details not found
        return [
            'reason'        => null,
            'description'   => null,
            'source'        => null,
        ];
    }

    /**
     * @param PayoutsStatusDetails\Entity $statusDetails
     * @return mixed|string|null
     * Mapping of source based on reason; either pre-defined from map otherwise from json config file
     */
    protected function getSourceForStatusDetails(PayoutsStatusDetails\Entity $statusDetails, Entity $payout)
    {
        $source = PayoutsStatusDetails\ReasonSourceMap::$statusDetailsReasonToSourceMap[$statusDetails['reason']] ?? null;

        // if source mapping is not present; then use the json file to get source
        if($source === null)
        {
            $error        = new PayoutError($payout);
            $errorDetails = $error->getErrorDetails();
            $source       = $errorDetails['source'] ?? null;
            $this->trace->debug(TraceCode::SETTLEMENT_PAYOUT_UPDATE_WEBHOOK_DEBUG, [
                'message'   => 'payout status details source will be read from JSON file'
            ]);
        }
        $this->trace->debug(TraceCode::SETTLEMENT_PAYOUT_UPDATE_WEBHOOK_DEBUG, [
            'message'   => 'payout status details source will be read from reason v/s source map'
        ]);
        return $source;
    }
}
