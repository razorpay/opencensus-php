<?php

namespace RZP\Models\Adjustment;

use RZP\Error\Error;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Models\Adjustment;
use RZP\Models\Transaction;
use RZP\Models\Merchant\Balance;
use RZP\Constants as DefaultConstants;
use RZP\Models\Merchant\Notify as NotifyTrait;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\Merchant\SlackActions as SlackActions;
use RZP\Models\Ledger\ReverseShadow\Adjustments\Core as ReverseShadowAdjustmentsCore;


class Service extends Base\Service
{
    use NotifyTrait;

    public function getAdjustment($id)
    {
        $adj = $this->repo->adjustment->findByPublicIdAndMerchant($id, $this->merchant);

        return $adj->toArrayPublic();
    }

    public function getAdjustments($input)
    {
        $adjustments = $this->repo->adjustment->fetch($input, $this->merchant->getKey());

        return $adjustments->toArrayPublic();
    }

    public function addAdjustment($input)
    {
        $merchantId = $input[Entity::MERCHANT_ID];
        unset($input[Entity::MERCHANT_ID]);

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $adj = $this->repo->transaction(
            function () use ($merchant, $input)
            {
                $adj = (new Adjustment\Core)->createAdjustment($input, $merchant);

                $publicId = $adj->getPublicId();

                if (($adj->isBalanceTypePrimary() === true) and
                    ($adj->getEntityType() !== DefaultConstants\Entity::DISPUTE))
                {
                    if ($merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true)
                    {
                        (new ReverseShadowAdjustmentsCore())->createLedgerEntryForManualAdjustmentReverseShadow($adj, $publicId);

                        $adj->setStatus(Status::PROCESSED);

                        $this->repo->saveOrFail($adj);
                    }
                    else
                    {
                        (new Adjustment\Core)->createLedgerEntriesForManualAdjustment($adj, $merchant, $publicId);
                    }
                }

                return $adj;
            }
        );

        // Todo: need to check if we need to push to slack channel before final state of adj ?
        $this->logActionToSlack($merchant, SlackActions::ADD_ADJUSTMENT, $input);

        return $adj->toArrayPublic();
    }

    public function addAdjustmentBatch($input)
    {
        $this->trace->info(
            TraceCode::BULK_ADJUSTMENT_CREATE_REQUEST,
            [
                'request' => $input
            ]);

        $result = new Base\PublicCollection;

        foreach ($input as $adjustmentInput)
        {
            $idempotencyKey = $adjustmentInput[\RZP\Models\Batch\Constants::IDEMPOTENCY_KEY] ?? '';
            unset($adjustmentInput[\RZP\Models\Batch\Constants::IDEMPOTENCY_KEY]);

            $adjustmentInput[Entity::TYPE] = trim($adjustmentInput[Entity::TYPE]) ?: Balance\Type::PRIMARY;

            try
            {
                $this->app['workflow']->skipWorkflows(function() use ($adjustmentInput)
                {
                    $this->addAdjustment($adjustmentInput);
                });

                try
                {
                    /** @var Balance\Entity $balance */
                    $balance = $this->repo->balance->getMerchantBalanceByType($adjustmentInput[Entity::MERCHANT_ID],
                                                                    $adjustmentInput[Entity::TYPE]);

                    $balanceAmount = $balance->getBalance();
                }
                catch (\Exception $ex)
                {
                    $this->trace->traceException($ex);

                    $balanceAmount = 0;
                }

                $result->push([
                    'idempotency_key'   => $idempotencyKey,
                    'success'           => true,
                    'balance'           => $balanceAmount,
                ]);
            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    null,
                    null,
                    $adjustmentInput);

                $result->push([
                    'idempotency_key'   => $idempotencyKey,
                    'success'           => false,
                    'balance'           => 0,
                    'error'             => [
                        Error::DESCRIPTION       => $ex->getMessage(),
                        Error::PUBLIC_ERROR_CODE => $ex->getCode(),
                    ]
                ]);
            }
        }

        $this->trace->info(
            TraceCode::BULK_ADJUSTMENT_CREATE_RESPONSE,
            [
                'response' => $result->toArrayWithItems(),
            ]);

        return $result->toArrayWithItems();
    }

    /**
     * Adds Multiple adjustments
     * @param array $input [list of adjustments to be added]
     * @return array
     */
    public function addMultipleAdjustment(array $input)
    {
        $this->trace->info(TraceCode::BULK_ADJUSTMENT_CREATE_REQUEST, $input);

        $adjustments = $input['adjustments'];

        $success = 0;
        $failed = 0;

        foreach ($adjustments as $adjInput)
        {
            try
            {
                $this->addAdjustment($adjInput);

                $success++;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    null,
                    null,
                    $adjInput);

                $failed++;
            }
        }

        $response = [
            'success'       => $success,
            'failed'        => $failed,
        ];

        $this->trace->info(TraceCode::BULK_ADJUSTMENT_CREATE_RESPONSE, $response);

        return $response;
    }

    public function postReverseAdjustments($input)
    {
        $adjustmentIds = $input['ids'];

        $success = 0;
        $failed = 0;
        $failedIds = [];

        foreach ($adjustmentIds as $adjustmentId)
        {
            $adjustment = null;

            try
            {
                Adjustment\Entity::verifyIdAndStripSign($adjustmentId);

                $adjustment = $this->repo->adjustment->findOrFail($adjustmentId);

                $request = [
                    Entity::AMOUNT      => -1 * $adjustment->getAmount(),
                    Entity::CURRENCY    => 'INR',
                    Entity::DESCRIPTION => 'Reverse adjustment for '. $adjustment->getId()
                ];

                $revAdj = (new Adjustment\Core)->createAdjustment($request, $adjustment->merchant);

                $success++;
            }
            catch (\Exception $ex)
            {
                $failed++;

                $failedIds[] = $adjustment->getId();
            }

            $response['success'] = $success;
            $response['failed'] = $failed;
            $response['failedIds'] = $failedIds;
        }

        return $response;
    }

    public function splitAdjustments(array $input): array
    {
        return $this->core()->splitAdjustments($input);
    }

    public function subBalanceAdjustment(array $input)
    {
        $this->trace->info(
            TraceCode::ADJUSTMENT_BETWEEN_BANKING_BALANCE_CREATE_REQUEST,
            [
                'input' => $input
            ]);

        (new Validator)->validateInput(Validator::ADJUSTMENT_BETWEEN_BALANCES, $input);

        $merchantId = $input[Entity::MERCHANT_ID];
        unset($input[Entity::MERCHANT_ID]);
        unset($input[Entity::TYPE]);

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        return $this->core()->subBalanceAdjustment($input, $merchant);
    }

    public function fetchAdjustmentByDescription($description, $merchantId)
    {
         return $this->repo->adjustment->findAdjustmentByDescription($description, $merchantId);
    }

    public function createAdjustmentViaLedgerCronJob(array $blacklistIds, array $whitelistIds, int $limit)
    {
        return (new Adjustment\Core)->createAdjustmentViaLedgerCronJob($blacklistIds, $whitelistIds, $limit);
    }

    public function createAdjustmentInTransaction($input) :array
    {
        if((isset($input[Entity::ID]) === false) or (isset($input[Entity::TRANSACTION_ID]) === false))
        {
            throw new BadRequestValidationFailureException('Both id and transaction_id are required.');
        }

        $txnId = $input[Entity::TRANSACTION_ID];

        $adjustmentId = $input[Entity::ID];

        try
        {
            $adjustment = $this->repo->adjustment->findOrFail($adjustmentId);
        }
        catch (\Exception $e)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID,
                null,
                [
                    LedgerConstants::ADJUSTMENT_ID      => $adjustmentId,
                ]);
        }

        $txn = $this->repo->transaction(function() use ($adjustment, $txnId)
        {
            $txn = (new Transaction\Core)->createFromAdjustment($adjustment, $txnId);

            $this->repo->saveOrFail($txn);

            $this->repo->saveOrFail($adjustment);

            (new Transaction\Core)->dispatchEventForTransactionCreated($txn);

            return $txn;

        });

        $this->trace->info(TraceCode::ADJUSTMENT_TRANSACTION_CREATED,
            [
                LedgerConstants::ADJUSTMENT_ID      => $adjustmentId,
                LedgerConstants::JOURNAL_ID         => $txnId,
                LedgerConstants::API_TRANSACTION_ID => $txn->getId(),
        ]);

        return $adjustment->toArrayPublic();
    }
}
