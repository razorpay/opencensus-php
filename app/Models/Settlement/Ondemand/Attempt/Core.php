<?php

namespace RZP\Models\Settlement\Ondemand\Attempt;

use Config;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Services\RazorpayXClient;
use RZP\Models\Settlement\Ondemand;
use RZP\Exception\BadRequestException;
use RZP\Models\Settlement\OndemandPayout;
use RZP\Models\Settlement\Ondemand\Transfer;

class Core extends Base\Core
{
    const PAYOUT_PROCESSED_EVENT = 'payout.processed';

    const PAYOUT_REVERSED_EVENT = 'payout.reversed';

    public function createAttempt($settlementOndemandTransfer)
    {
        $this->trace->info(TraceCode::SETTLEMENT_ONDEMAND_ATTEMPT_CREATE, [
            'settlement_ondemand_transfer_id'   => $settlementOndemandTransfer->getId(),
        ]);

        $data = [
            Entity::SETTLEMENT_ONDEMAND_TRANSFER_ID => $settlementOndemandTransfer->getId(),
            Entity::STATUS                          => Status::CREATED,
        ];

        $attempt = (new Entity)->build($data);

        $attempt->generateId();

        $attempt->settlementOndemandTransfer()->associate($settlementOndemandTransfer);

        $this->repo->saveOrFail($attempt);

        return $attempt;
    }

    public function makeBulkPayoutRequest($settlementOndemandAttemptId, $currency, $settlementOndemandTransfer)
    {
        $payoutAmount = $settlementOndemandTransfer->getAmount();

        $fundAccountId = Config::get('applications.razorpayx_client.live.ondemand_contact.fund_account_id');

        $data = [
            'amount'           => $payoutAmount,
            'fund_account_id'  => $fundAccountId,
            'currency'         => $currency,
            'mode'             => $settlementOndemandTransfer->getMode(),
            'reference_id'     => $settlementOndemandAttemptId,
        ];

        (new Transfer\Core)
            ->setLastAttemptAt($settlementOndemandTransfer, Carbon::now(Timezone::IST)->getTimestamp());

        $response = $this->app->razorpayXClient->makePayoutRequest($data, $settlementOndemandAttemptId, true);

        $status = $response[RazorpayXClient::STATUS] ?: null;

        $payoutId = $response[RazorpayXClient::ID] ?: null;

        return [$status, $payoutId, $response];
    }

    public function updateStatusAfterPayoutRequest(
        $payoutStatus,
        $payoutId,
        $settlementOndemandAttempt,
        $response,
        $failureReason)
    {
        if (in_array($payoutStatus, OndemandPayout\Core::PAYOUT_VALID_STATUS_LIST, true) === false)
        {
            throw new BadRequestException(ErrorCode::SERVER_ERROR_RAZORPAYX_PAYOUT_CREATION_FAILURE,
                null,
                [
                    'response'   => $response,
                ]);
        }
        else
        {
            $this->repo
                 ->transaction(function() use ($settlementOndemandAttempt, $payoutStatus, $payoutId, $failureReason)
            {
                if ($settlementOndemandAttempt->getStatus() === $payoutStatus)
                {
                    return;
                }

                $settlementOndemandAttempt->setStatus($payoutStatus);

                $settlementOndemandAttempt->setPayoutId($payoutId);

                if ($payoutStatus === Status::REVERSED)
                {
                    $settlementOndemandAttempt->setFailureReason($failureReason);
                }

                $this->repo->saveOrFail($settlementOndemandAttempt);

                $settlementOndemandTransfer = $settlementOndemandAttempt->settlementOndemandTransfer;

                (new Transfer\Core)
                    ->updateStatusAttemptsAndPayoutId($payoutStatus, $settlementOndemandTransfer, $payoutId);

            });
        }
    }

    public function updateOndemandBulkPayoutStatus($event, $payoutData)
    {
        $response= [];

        $settlementOndemandAttempt = (new Repository)->findById($payoutData['reference_id']);

        $settlementOndemandTransfer = (new Transfer\Repository)
                                            ->findById($settlementOndemandAttempt->getSettlementOndemandTransferId());

        $attempts = $settlementOndemandTransfer->getAttempts();

        switch($event)
        {
            case self::PAYOUT_PROCESSED_EVENT:

                if($settlementOndemandAttempt->getStatus() === Status::PROCESSED)
                {
                    $response = ['response' => 'status already updated'];
                }
                else
                {
                    $this->updateStatusAfterWebhookResponse(
                            $payoutData,
                            Status::PROCESSED,
                            $settlementOndemandAttempt);

                    $response = ['response' => 'status updated'];
                }

                break;

            case self::PAYOUT_REVERSED_EVENT:

                if($settlementOndemandAttempt->getStatus()=== Status::REVERSED)
                {
                    $response = ['response' => 'status already updated'];
                }

                else
                {
                    $this->updateStatusAfterWebhookResponse($payoutData,
                        Status::REVERSED,
                        $settlementOndemandAttempt);

                    $response = ['response' => 'status updated'];
                }

                break;

            default:
                throw new Exception\InvalidArgumentException(
                    'not a valid ondemand_attempt event');
        }

        return $response;
    }

    public function updateStatusAfterWebhookResponse($payoutData, $payoutStatus, $settlementOndemandAttempt)
    {
        $settlementOndemandAttempt->setStatus($payoutStatus);

        if(isset($payoutData['id']) === true)
        {
            $settlementOndemandAttempt->setPayoutId($payoutData['id']);
        }

        if($payoutStatus === Status::REVERSED)
        {
            $settlementOndemandAttempt->setFailureReason($payoutData['failure_reason']);
        }

        $this->repo->saveOrFail($settlementOndemandAttempt);

        (new Transfer\Core)
            ->updateStatusAndRetryIfRequired($payoutData['id'], $payoutStatus, $settlementOndemandAttempt->settlementOndemandTransfer);
    }
}
