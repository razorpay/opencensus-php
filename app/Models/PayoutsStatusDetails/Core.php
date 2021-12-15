<?php

namespace RZP\Models\PayoutsStatusDetails;

use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestException;
use RZP\Models\FundTransfer\Attempt\Entity as FTAEntity;
use RZP\Models\FundTransfer\Attempt\Constants as FTAConstants;
use RZP\Models\PayoutsStatusDetails as PayoutsStatusDetails;
use Illuminate\Support\Facades\View as View;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();
    }

    public function create(Payout\Entity $payout)
    {
        $payoutId = $payout->getId();

        $status = $payout->getStatus();

        if($status === Payout\Status::PROCESSED)
        {
            $reason = "payout_processed";

            $description = View::make('status_details.processed_status')->render();

            $description = rtrim($description);
        }

       else if($status === Payout\Status::PENDING)
       {
            $reason = "pending_approval";

            $description = View::make('status_details.pending_status')->render();

            $description = rtrim($description);
       }

       else if($status === Payout\Status::QUEUED)
       {
            $reason = $payout->getQueuedReason();

            $description = $payout->getDescriptionForQueuedReason($reason);
       }

       else if($status === Payout\Status::REVERSED or $status === Payout\Status::FAILED)
       {

           $error = new Payout\PayoutError($payout);

           $errorDetails = $error->getErrorDetails();

           $reason = $errorDetails['reason'] ?? null;

           $description = $errorDetails['description'] ?? null;

       }
       else
       {
           $reason = null;
           $description = null;
       }

        $this->savePayoutStatusDetailsEntity($payout, $status, $reason, $description);
    }

    // creates status details entity for processing state
    public function createStatusDetails(Payout\Entity $payout, array $ftadata)

    {
        $payoutId = $payout->getId();

        $status = $payout->getStatus();

        $statusDetails = $ftadata[FTAEntity::STATUS_DETAILS] ?? null;

        $reason = $statusDetails[FTAEntity::REASON] ?? null;

        $processByTime = $statusDetails[FTAEntity::PARAMETERS][FTAConstants::PROCESSED_BY_TIME] ?? null;

        if ($reason === null)
        {
            $description = null;
        }
        else

        {
            $beneBankName = $payout->provideBeneBankName() ?? 'beneficiary bank';

            $description = View::make('status_details.processing_status',
                [
                    'beneficiary_bank' => $beneBankName,
                    'processByTime' => $processByTime,
                    'reason' => $reason,
                    'mode' => $payout->getMode(),
                ])->render();

            $description = rtrim($description);
        }

        $this->savePayoutStatusDetailsEntity($payout, $status, $reason, $description);
    }

    public function savePayoutStatusDetailsEntity($payout, $status, $reason, $description)
    {
        $input = [
            PayoutsStatusDetails\Entity::STATUS                    => $status,
            PayoutsStatusDetails\Entity::REASON                    => $reason,
            PayoutsStatusDetails\Entity::DESCRIPTION               => $description,
            PayoutsStatusDetails\Entity::MODE                      => 'system',
        ];

        // if two fts webhook comes with same status details update then two rows will be created
        if($reason !== null and $description !== null)
        {
            $this->trace->info(TraceCode::PAYOUTS_STATUS_DETAILS_CREATE_REQUEST, ['input' => $input]);

            $statusDetails = (new PayoutsStatusDetails\Entity)->build($input);

            $statusDetails->payout()->associate($payout);

            $this->repo->saveOrFail($statusDetails);

            $this->trace->info(
                TraceCode::PAYOUTS_STATUS_DETAILS_ENTITY_CREATED,
                $statusDetails->toArray()
            );

        }
    }
}

