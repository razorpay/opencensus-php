<?php

namespace RZP\Models\FeeRecovery;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Reversal;
use RZP\Models\BankingAccount;
use RZP\Models\Merchant\Balance;
use RZP\Models\Currency\Currency;
use Razorpay\Trace\Logger as Trace;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function handlePayoutStatusUpdate(Payout\Entity $payout,
                                             Reversal\Entity $reversal = null)
    {
        $payoutStatus = $payout->getStatus();

        // We shall make a new entry in the fee_recovery table of type credit when
        if ($payoutStatus === Payout\Status::FAILED)
        {
            $this->createFeeRecoveryEntityForSource($payout);
        }
        else if ($payoutStatus === Payout\Status::REVERSED)
        {
            $this->createFeeRecoveryEntityForSource($reversal);
        }

        // If the payout is a fee_recovery payout, we need to update all the fee_recovery entries
        // corresponding to this fee_recovery payout
        if ($payout->getPurpose() === Payout\Purpose::RZP_FEES)
        {
            $feeRecoveryPayoutId = $payout->getId();

            $feeRecoveryStatus = Status::getFeeRecoveryStatusFromPayoutStatus($payoutStatus);

            $this->repo->fee_recovery->updateFeeRecoveryOnPayoutStatusUpdate($feeRecoveryPayoutId,
                                                                             $feeRecoveryStatus);


            $this->trace->info(
                TraceCode::FEE_RECOVERY_UPDATE_AFTER_RZP_FEES_PAYOUT_STATUS_UPDATE,
                [
                    'fee_recovery_payout_id'        => $feeRecoveryPayoutId,
                    'fee_recovery_status'           => $feeRecoveryStatus,
                    'fee_recovery_payout_status'    => $payout->getStatus(),
                ]);
        }
    }

    /**
     * Creates an entry into the fee_recovery table corresponding to a Payout/Reversal.
     * This function gets invoked at payout initiation and reversal creation.
     *
     * @param Base\PublicEntity $entity
     *
     */
    public function createFeeRecoveryEntityForSource(Base\PublicEntity $entity)
    {
        $this->mutex->acquireAndRelease(
            'fee_recovery_' . $entity->getId(),
            function () use ($entity)
        {
            $feeRecoveryEntity = (new Entity)->build();

            Validator::validateSourceEntity($entity);

            $type = (new Type)->getTypeFromSourceEntity($entity);

            $feeRecoveryEntity->setType($type);

            $feeRecoveryEntity->setStatus(Status::UNRECOVERED);

            $feeRecoveryEntity->entity()->associate($entity);

            $skipCreation = $this->skipIfExistingFeeRecoveryDataExists($feeRecoveryEntity);

            if ($skipCreation === true)
            {
                return;
            }

            $this->repo->saveOrFail($feeRecoveryEntity);

            $this->trace->info(
                TraceCode::FEE_RECOVERY_ENTITY_CREATED,
                [
                    'source_id'       => $entity->getId(),
                    'source_type'     => $entity->getEntityName(),
                    'fee_recovery_id' => $feeRecoveryEntity->getId()
                ]);
        },
        60,
        ErrorCode::BAD_REQUEST_FEE_RECOVERY_ANOTHER_OPERATION_IN_PROGRESS);
    }

    /**
     * This function picks up all payouts, failed payouts and reversals between a certain period,
     * calculates fees that needs to be recovered for these entities (positive for debit, negative for credit)
     * and makes a payout to a designated rzp_fees fund account with the calculated amount
     *
     * @param array $input
     *
     * @return Payout\Entity
     *
     * @throws Exception\BadRequestException
     */
    public function createFeeRecoveryPayout(array $input): Payout\Entity
    {
        $this->trace->info(
            TraceCode::FEE_RECOVERY_INITIATED,
            [
                'input' => $input,
            ]);

        (new Validator)->validateInput(Validator::CREATE_FEE_RECOVERY_PAYOUT, $input);

        $balanceId = $input[Entity::BALANCE_ID];

        $startTimeStamp = $input[Entity::FROM];

        $endTimeStamp = $input[Entity::TO];

        $balance = $this->repo->balance->findOrFailById($balanceId);

        (new Validator)->validateBalanceTypeAndTimeStamps($balance, $startTimeStamp, $endTimeStamp);

        $feeRecoveryPayout = $this->processFeeRecovery($balance, $startTimeStamp, $endTimeStamp);

        return $feeRecoveryPayout;
    }

    protected function skipIfExistingFeeRecoveryDataExists(Entity $feeRecovery): bool
    {
        /** @var Base\PublicEntity $source */
        $source = $feeRecovery->entity;

        $params = [
            Entity::ENTITY_ID       => $source->getId(),
            Entity::TYPE            => $feeRecovery->getType(),
        ];

        $this->repo->fee_recovery->setMerchantIdRequiredForMultipleFetch(false);

        $existingData = $this->repo->fee_recovery->fetch($params);

        if ($existingData->count() > 1)
        {
            throw new Exception\LogicException('More than one entry in fee_recovery for given source entity',
                                               ErrorCode::BAD_REQUEST_LOGIC_ERROR_FEE_RECOVERY_DUPLICATE_DATA,
                                               [
                                                   'source_id'      => $source->getId(),
                                                   'source_type'    => $source->getEntityName(),
                                                   'count'          => $existingData->count(),
                                               ]);
        }

        if ($existingData->count() === 1)
        {
            $this->trace->info(
                TraceCode::FEE_RECOVERY_FOR_GIVEN_ENTITY_ALREADY_EXISTS,
                [
                    'source_id'                 => $feeRecovery->getEntityId(),
                    'existing_fee_recovery_id'  => $existingData->first->getEntityId()
                ]);

            return true;
        }

        // In case of reversals, we also need to check for existing credit entry for a payout.
        // This is because we are now allowing transition of payout status from FAILED -> REVERSED
        // In case of failed payouts, we already have a credit entry corresponding to this failed payout
        // When the payout gets reversed, we shall not create another credit entry and will simply skip it
        if ($feeRecovery->getEntityType() === Entity::REVERSAL)
        {
            $fetchParams = [
                Entity::ENTITY_ID => $feeRecovery->reversal->getEntityId(),
                Entity::TYPE      => Type::CREDIT
            ];

            $existingEntry = $this->repo->fee_recovery->fetch($fetchParams);

            if($existingEntry->count() > 0)
            {
                $this->trace->info(
                    TraceCode::FEE_RECOVERY_FAILED_PAYOUT_TO_REVERSAL,
                    [
                        'reversal_id' => $feeRecovery->getEntityId()
                    ]);

                return true;
            }
        }

        return false;
    }

    protected function processFeeRecovery(Balance\Entity $balance,
                                                 int $startTimestamp,
                                                 int $endTimestamp)
    {
        $feeRecoveryPayout = $this->mutex->acquireAndRelease(
            'process_fee_recovery_' . $balance->getId(),
            function() use ($balance, $startTimestamp, $endTimestamp)
        {
            list ($payouts, $failedPayouts, $reversals) = $this->getPayoutAndReversalEntitiesForFeeRecovery($balance,
                                                                                                            $startTimestamp,
                                                                                                            $endTimestamp);

            $amount = $this->getFeesForFeeRecovery($payouts, $failedPayouts, $reversals);

            if ($amount <= 0)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_FEE_RECOVERY_AMOUNT_INSUFFICIENT,
                    null,
                    [
                        'balance_id'            => $balance->getId(),
                        'start_timestamp'       => $startTimestamp,
                        'end_timestamp'         => $endTimestamp,
                        'fee_recovery_amount'   => $amount
                    ]);
            }

            return $this->processAndGetFeeRecoveryPayout($payouts,
                                                         $failedPayouts,
                                                         $reversals,
                                                         $balance,
                                                         $amount);
        },
        600,
        ErrorCode::BAD_REQUEST_FEE_RECOVERY_ANOTHER_OPERATION_IN_PROGRESS);

        return $feeRecoveryPayout;
    }

    protected function getPayoutAndReversalEntitiesForFeeRecovery(Balance\Entity $balance,
                                                                  int $startTimestamp,
                                                                  int $endTimestamp)
    {
        $merchant = $balance->merchant;

        $balanceId = $balance->getId();

        // TODO : Add a limit to make sure that these fetch statements don't choke the network

        $payouts = $this->repo->payout->fetchFeesAndIdOfPayoutsForGivenBalanceIdForPeriod(
            $merchant->getId(),
            $balanceId,
            $startTimestamp,
            $endTimestamp
        );

        $failedPayouts = $this->repo->payout->fetchFeesAndIdOfFailedPayoutsForGivenBalanceIdForPeriod(
            $merchant->getId(),
            $balanceId,
            $startTimestamp,
            $endTimestamp
        );

        $reversals = $this->repo->reversal->fetchFeesAndIdOfReversalsForGivenBalanceIdForPeriod(
            $merchant->getId(),
            $balanceId,
            $startTimestamp,
            $endTimestamp
        );

        return [$payouts, $failedPayouts, $reversals];
    }

    protected function getFeesForFeeRecovery(Base\PublicCollection $payouts,
                                             Base\PublicCollection $failedPayouts,
                                             Base\PublicCollection $reversals)
    {
        $payoutsFees = 0;
        $reversalsFees = 0;
        $failedPayoutsFees = 0;

        foreach ($payouts as $payout)
        {
            $payoutsFees += $payout[Payout\Entity::FEES];
        }

        foreach ($failedPayouts as $failedPayout)
        {
            $failedPayoutsFees += $failedPayout[Payout\Entity::FEES];
        }

        foreach ($reversals as $reversal)
        {
            $reversalsFees += $reversal[Payout\Entity::FEES];
        }

        $amount = $payoutsFees - $failedPayoutsFees - $reversalsFees;

        return $amount;
    }

    /**
     * @param Base\PublicCollection $payouts
     * @param Base\PublicCollection $failedPayouts
     * @param Base\PublicCollection $reversals
     * @param Balance\Entity        $balance
     * @param                       $amount
     *
     * @return mixed
     * @throws Exception\BadRequestException
     */
    protected function processAndGetFeeRecoveryPayout(Base\PublicCollection $payouts,
                                                      Base\PublicCollection $failedPayouts,
                                                      Base\PublicCollection $reversals,
                                                      Balance\Entity $balance,
                                                      $amount)
    {
        $payoutIds          = $payouts->getIds();
        $failedPayoutIds    = $failedPayouts->getIds();
        $reversalIds        = $reversals->getIds();

        $this->validateNoExistingFeeRecoveryInProcess($payoutIds, $failedPayoutIds, $reversalIds);

        return $this->repo->transaction(
            function() use ($balance, $payoutIds, $failedPayoutIds, $reversalIds, $amount)
            {
                $merchant = $balance->merchant;

                $payoutPayload = $this->getPayloadForFeeRecoveryPayout($balance, $amount);

                $this->trace->info(
                    TraceCode::FEE_RECOVERY_PAYOUT_CREATE_REQUEST,
                    [
                        'payload' => $payoutPayload
                    ]);

                $feeRecoveryPayout = (new Payout\Core)->createPayoutToFundAccount($payoutPayload,
                                                                                  $merchant,
                                                                                  null,
                                                                                  true);

                $this->trace->info(
                    TraceCode::FEE_RECOVERY_PAYOUT_CREATED,
                    [
                        'payout_data' => $feeRecoveryPayout->toArrayPublic()
                    ]);

                $this->updateFeesRecoveryStatus($payoutIds, $failedPayoutIds, $reversalIds, $feeRecoveryPayout);

                return $feeRecoveryPayout;
            });
    }

    /**
     * * This function matches the count of payoutIds, failedPayoutIds and reversalIds to their corresponding entries
     * in the fee_recovery table. Ideally we would want to match every Id to its corresponding entry.
     * Doing that is very costly. By matching every Id, we could throw error for only certain payouts/reversals
     * but in this case, we shall throw an error for an entire range of payouts. This would only occur if there is
     * some sort of data inconsistency. In both cases, corresponding fee_recovery payout should fail.
     *
     * @param $payoutIds
     * @param $failedPayoutIds
     * @param $reversalIds
     *
     * @throws Exception\BadRequestException
     */
    protected function validateNoExistingFeeRecoveryInProcess($payoutIds,
                                                              $failedPayoutIds,
                                                              $reversalIds)
    {
        $unRecoveredPayoutCount = $this->repo->fee_recovery->fetchUnrecoveredFeeRecoveryCount($payoutIds,
                                                                                              Entity::PAYOUT,
                                                                                              Type::DEBIT);

        $unRecoveredFailedPayoutCount = $this->repo->fee_recovery->fetchUnrecoveredFeeRecoveryCount($failedPayoutIds,
                                                                                                    Entity::PAYOUT,
                                                                                                    Type::CREDIT);

        $unRecoveredReversalCount = $this->repo->fee_recovery->fetchUnrecoveredFeeRecoveryCount($reversalIds,
                                                                                                Entity::REVERSAL,
                                                                                                Type::CREDIT);

        $payoutIdsCount = count($payoutIds);
        $reversalIdsCount = count($reversalIds);
        $failedPayoutIdsCount = count($failedPayoutIds);

        if (($unRecoveredPayoutCount !== $payoutIdsCount) or
            ($unRecoveredFailedPayoutCount !== $failedPayoutIdsCount) or
            ($unRecoveredReversalCount !== $reversalIdsCount))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_FEE_RECOVERY_ALREADY_INITIATED,
                null,
                [
                    'payout_id_count'                   => $payoutIdsCount,
                    'unrecovered_payout_count'          => $unRecoveredPayoutCount,
                    'failed_payout_id_list'             => $failedPayoutIdsCount,
                    'unrecovered_failed_payout_count'   => $unRecoveredFailedPayoutCount,
                    'reversal_id_list'                  => $reversalIdsCount,
                    'unrecovered_reversal_count'        => $unRecoveredReversalCount,
                ]);
        }
    }

    /**
     * This function also ends up creating a new rzp_fees type contact and fund account if none currently exist
     * Ideally, this should never occur but may occur if someone manually activates a merchant for business banking.
     *
     * @param Balance\Entity $balance
     * @param $amount
     *
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\InvalidArgumentException
     * @throws Exception\LogicException
     */
    protected function getPayloadForFeeRecoveryPayout(Balance\Entity $balance, $amount)
    {
        $merchant = $balance->merchant;

        $feeRecoveryContact = $this->fetchOrCreateRzpFeesTypeContact($merchant, $balance);

        $feeRecoveryFundAccount = $this->repo->fund_account->fetchRzpFeesFundAccount($merchant->getId(),
                                                                                     $feeRecoveryContact->getId());

        $fundAccountId = $feeRecoveryFundAccount->getPublicId();

        $payoutPayload = [
            Payout\Entity::FUND_ACCOUNT_ID      => $fundAccountId,
            Payout\Entity::MODE                 => Payout\Mode::IFT,
            Payout\Entity::CURRENCY             => Currency::INR,
            Payout\Entity::BALANCE_ID           => $balance->getId(),
            Payout\Entity::PURPOSE              => Payout\Purpose::RZP_FEES,
            Payout\Entity::QUEUE_IF_LOW_BALANCE => true,
            Payout\Entity::AMOUNT               => $amount,
        ];

        return $payoutPayload;
    }

    protected function updateFeesRecoveryStatus($payoutIds,
                                                $failedPayoutIds,
                                                $reversalIds,
                                                $feeRecoveryPayout,
                                                $currentAttemptNumber = 0)
    {
        $this->trace->info(
            TraceCode::FEE_RECOVERY_STATUS_UPDATE,
            [
                'fee_recovery_payout_id' => $feeRecoveryPayout->getPublicId(),
            ]);

        $updatedPayoutsCount = $this->repo->fee_recovery
                                          ->updateBulkStatusAndRecoveryPayoutId($payoutIds,
                                                                                Entity::PAYOUT,
                                                                                Type::DEBIT,
                                                                                $feeRecoveryPayout->getId(),
                                                                                Status::PROCESSING,
                                                                                $currentAttemptNumber);

        $updatedFailedPayoutsCount = $this->repo->fee_recovery
                                                ->updateBulkStatusAndRecoveryPayoutId($failedPayoutIds,
                                                                                      Entity::PAYOUT,
                                                                                      Type::CREDIT,
                                                                                      $feeRecoveryPayout->getId(),
                                                                                      Status::PROCESSING,
                                                                                      $currentAttemptNumber);

        $updatedReversalsCount = $this->repo->fee_recovery
                                            ->updateBulkStatusAndRecoveryPayoutId($reversalIds,                                                  Entity::REVERSAL,
                                                                                  Type::CREDIT,
                                                                                  $feeRecoveryPayout->getId(),
                                                                                  Status::PROCESSING,
                                                                                  $currentAttemptNumber);

        if (($updatedPayoutsCount !== count($payoutIds)) or
            ($updatedFailedPayoutsCount !== count($failedPayoutIds)) or
            ($updatedReversalsCount !== count($reversalIds)))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_FEE_RECOVERY_BULK_UPDATE_ERROR,
                null,
                [
                    'payouts_count'                 => count($payoutIds),
                    'updated_payouts_count'         => $updatedPayoutsCount,
                    'failed_payouts_count'          => count($failedPayoutIds),
                    'updated_failed_payouts_count'  => $updatedFailedPayoutsCount,
                    'reversals_count'               => count($reversalIds),
                    'updated_reversals_count'       => $updatedReversalsCount
                ]);
        }
    }

    /**
     * This function fetches the 'rzp_fees' type contact.
     * If it does not exist, it creates the contact and corresponding fund account.
     *
     * @param Merchant\Entity $merchant
     *
     * @return mixed
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\InvalidArgumentException
     * @throws Exception\LogicException
     */
    protected function fetchOrCreateRzpFeesTypeContact(Merchant\Entity $merchant, Balance\Entity $balance)
    {
        $rzpFeesContacts = $this->repo->contact->fetch([
                                                           Contact\Entity::TYPE => Contact\Type::RZP_FEES
                                                       ],
                                                       $merchant->getId());

        if ($rzpFeesContacts->count() > 1)
        {
            throw new Exception\LogicException('Merchant has more than one rzp_fees type contact',
                                               ErrorCode::BAD_REQUEST_LOGIC_ERROR_MULTIPLE_RZP_FEES_CONTACT,
                                               [
                                                   'merchant_id' => $merchant->getId(),
                                                   'count'       => $rzpFeesContacts->count(),
                                               ]);
        }

        if (empty($rzpFeesContacts) === true)
        {
            (new BankingAccount\Core)->createRZPFeesContactAndFundAccount($merchant);

            $this->trace->error(TraceCode::RZP_FEES_CONTACT_FUND_ACCOUNT_DOES_NOT_EXIST,
                                [
                                    'merchant_id'           => $merchant->getId(),
                                    'balance_id'            => $balance->getId(),
                                ]);

            $rzpFeesContacts = $this->repo->contact->fetch([
                                                               Contact\Entity::TYPE => Contact\Type::RZP_FEES
                                                           ],
                                                           $merchant->getId(),
                                                           true);
        }

        $feeRecoveryContact = $rzpFeesContacts->first();

        return $feeRecoveryContact;
    }
}
