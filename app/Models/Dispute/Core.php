<?php

namespace RZP\Models\Dispute;

use DB;
use Mail;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Jobs\NotifyRas;
use RZP\Models\Feature;
use RZP\Services\Mutex;
use RZP\Diag\EventCode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Dispute\Constants as DisputeConstants;
use RZP\Mail\Base\Constants;
use RZP\Models\Admin\Action;
use RZP\Models\Payment\Refund;
use RZP\Models\Dispute\Evidence;
use Razorpay\Trace\Logger as Trace;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Mail\Dispute as DisputeMailer;
use RZP\Constants\Entity as EntityConstants;
use RZP\Constants\{Entity as E, Timezone, Table};
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Models\Dispute\File\Core as DisputeFileCore;
use RZP\Models\
{Base, Payment, Merchant, Adjustment, Currency, Payment\Method};
use RZP\Models\Merchant\Webhook\Event as WebhookEvent;
use RZP\Models\Merchant\{Email as MerchantEmail, Entity as MerchantEntity};
use RZP\Models\Merchant\FreshdeskTicket\Constants as FreshdeskConstants;

class Core extends Base\Core
{
    use FileHandlerTrait;

    // 72 hours = 72*60*60
    const REFUND_PROCESS_REDIS_TTL         = 259200; // in seconds
    const REFUND_PROCESS_REDIS_KEY         = 'dispute_refund_process_%s';

    const DEBIT_ADJUSTMENT_DESCRIPTION  = 'Debit disputed amount V2';

    const CREDIT_ADJUSTMENT_DESCRIPTION = 'Credit to reverse a previous dispute debit';

    const DISPUTE_BULK_EMAIL_MUTEX     = 'DISPUTE_BULK_EMAIL_MUTEX';

    // NOTE: Going forward if the no of disputes increases, instead of taking a lock for 30 mins
    // change it so that the entire process runs async and for every merchant+phase we have an independent job.
    const DISPUTE_BULK_EMAIL_MUTEX_TTL = 1800;

    const DISPUTE_RISK_ASSESSMENT_MUTEX = 'DISPUTE_RISK_ASSESSMENT_MUTEX';

    const DISPUTE_RISK_ASSESSMENT_MUTEX_TTL = 300;

    const DISPUTE_BULK_UPDATE_LIMIT = 100;

    // Type of emails to be fetched from merchant_emails table
    const POC_EMAIL_TYPES = [
        MerchantEmail\Type::CHARGEBACK,
    ];

    const BULK_CREATE_DISPUTES_MAIL_DATA = [
        Entity::ID,
        Entity::PAYMENT_ID,
        Entity::AMOUNT,
        Entity::CURRENCY,
        Entity::GATEWAY_DISPUTE_ID,
        Entity::PHASE,
        Entity::RESPOND_BY,
        Entity::DEDUCT_AT_ONSET,
    ];

    const BULK_CREATE_DISPUTES_MAIL_REASON_DATA = [
        Reason\Entity::GATEWAY_CODE,
        Reason\Entity::GATEWAY_DESCRIPTION,
    ];

    const ELIGIBLE_CUSTOMER_TICKET_UPDATE_MIN_TS = 1598486400; // 27th Aug, 2020

    /**
     * @var Mutex
     */
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * @param Payment\Entity $payment
     * @param Reason\Entity  $reason
     * @param array          $input
     *
     * @return Entity
     * @throws Exception\BadRequestException
     */
    public function create(
        Payment\Entity $payment,
        Reason\Entity $reason,
        array $input): Entity
    {
        $this->trace->info(
            TraceCode::DISPUTE_CREATE_REQUEST,
            [
                'input'      => $input,
                'payment_id' => $payment->getId()
            ]);


        return $this->mutex->acquireAndRelease(
            $payment->getId(),
            function() use ($payment, $reason, $input)
            {
                $input = $this->preProcessInputForCreate($input);

                (new Validator)->validatePaymentForDispute($input, $payment);

                $parent = $this->checkAndGetParent($input);

                $dispute = new Entity;

                $this->setRelationsAndDerivedAttributes($dispute, $parent, $payment, $reason);

                $dispute->build($input);

                $dispute->setBackfill($input[Entity::BACKFILL]);

                (new Validator)->validateGatewayAmount($dispute);

                // entity id is required to create associated transaction
                $dispute->generateId();

                $this->app['workflow']
                     ->setEntityAndId($dispute->getEntity(), $dispute->getId())
                     ->handle((new \stdClass), $dispute);

                $dispute->setAuditAction(Action::CREATE_DISPUTE);

                $payment->setDisputed(true);

                $dispute = $this->repo->transaction(function() use ($dispute, $payment)
                {
                    if ($dispute->getDeductAtOnset() === true)
                    {
                        $this->createNegativeAdjustmentAndUpdateDispute($dispute);
                    }

                    $this->repo->saveOrFail($payment);

                    $this->repo->saveOrFail($dispute);

                    return $dispute;
                });

                $this->app['diag']->trackDisputeEvent(EventCode::DISPUTE_CREATED, $dispute);

                $this->firePaymentDisputeWebhookEvent($payment, $dispute, WebhookEvent::PAYMENT_DISPUTE_CREATED);

                return $dispute;
            });
    }

    /**
     * @param Entity $dispute
     * @param array  $input
     *
     * @return Entity
     * @throws Exception\BadRequestException
     */
    public function update(Entity $dispute, array $input): Entity
    {
        $this->trace->info(
            TraceCode::DISPUTE_EDIT_REQUEST,
            array_merge($input, [Entity::ID => $dispute->getId()])
        );

        $dispute->setBackfill($input[Entity::BACKFILL]);

        $paymentId = $dispute->getPaymentId();

        $input = $this->preProcessInputForUpdate($dispute, $input);

        $this->trace->info(
            TraceCode::DISPUTE_PREPROCESSED_EDIT_REQUEST,
            array_merge($input, [Entity::ID => $dispute->getId()])
        );

        return $this->mutex->acquireAndRelease(
            $paymentId,
            function() use ($dispute, $input) {

                $parent = $this->checkAndGetParent($input, $dispute);

                $dispute->edit($input);

                $dispute->setAuditAction(Action::EDIT_DISPUTE);

                if ($parent !== null)
                {
                    $dispute->parent()->associate($parent);
                }

                return $this->repo->transaction(function() use ($dispute, $input)
                {
                    $this->handleDisputeClosure($dispute, $input);

                    $this->fireDisputeStatusChangeWebhookEvent($dispute);

                    $this->repo->saveOrFail($dispute);

                    $this->updateCustomerTicketIfApplicable($dispute);

                    $this->generateDisputeEvent($dispute);

                    return $dispute;
                });
            });
    }

    protected function generateDisputeEvent(Entity $dispute)
    {
        if ($dispute->isClosed() === false)
        {
            $this->app['diag']->trackDisputeEvent(EventCode::DISPUTE_PROCESSED, $dispute);
        }
    }

    /**
     * @param Entity $dispute
     * @param array  $input
     *
     * @return array
     */
    public function updateFilesAndInputForMerchant(Entity $dispute, array $input): array
    {
        $this->trace->info(
            TraceCode::DISPUTE_EDIT_REQUEST_FOR_MERCHANT,
            [Entity::ID => $dispute->getId()]);

        $dispute->getValidator()->validateForMerchantUpdate($input);

        $files = [];

        $fileCore = new File\Core;

        if (array_key_exists(DisputeFileCore::FILES, $input) === true)
        {
            $files = $input[DisputeFileCore::FILES];

            $files = $fileCore->checkFilesInput($files);

            unset($input[DisputeFileCore::FILES]);
        }

        $response = $this->repo->transaction(function() use ($dispute, $fileCore, $files, $input)
        {
            if (empty($input) === false)
            {
                $dispute = $this->updateForMerchant($dispute, $input);
            }

            if (empty($files) === false)
            {
                $fileCore->uploadFiles($dispute, $files);
            }

            return $dispute->toArrayPublic();
        });

        $response[File\Core::ALL_FILES] = $fileCore->getFilesForEntity($dispute);

        return $response;
    }

    /**
     * @param Entity $dispute
     * @param array  $input
     *
     * @return Entity
     * @throws Exception\BadRequestException
     */
    public function updateForMerchant(Entity $dispute, array $input): Entity
    {
        $this->trace->info(
            TraceCode::DISPUTE_EDIT_REQUEST_FOR_MERCHANT,
            array_merge($input, [Entity::ID => $dispute->getId()])
        );

        (new Validator)->validateInput(Validator::OPERATION_MERCHANT_EDIT, $input);

        $generatedInput = $this->generateInputForMerchantEdit($dispute, $input);

        $dispute = $this->update($dispute, $generatedInput);

        $this->sendDisputeMailToAdmin($dispute, $input);

        return $dispute;
    }

    /**
     * @param $file
     *
     * @return array
     */
    public function migrateOldAdjustments($file): array
    {
        $fileContents = $this->parseExcelFile($file);

        $this->trace->info(
            TraceCode::DISPUTE_ADJUSTMENT_MIGRATE_REQUEST,
            [
                'total_adjustments' => count($fileContents)
            ]);

        $failed = 0;
        $failedIds = [];
        $succeeded = 0;
        $processed = 0;

        foreach ($fileContents as $adjustment)
        {
            $this->trace->info(
                TraceCode::DISPUTE_ADJUSTMENT_MIGRATE_REQUEST,
                [
                    'id'         => $adjustment[Adjustment\Entity::ID],
                    'payment_id' => $adjustment[Entity::PAYMENT_ID]
                ]);

            $id = (new Entity)->generateUniqueId();

            try
            {
                DB::transaction(function() use ($id, $adjustment) {
                    DB::table(Table::DISPUTE)->insert(
                        [
                            Entity::ID                 => $id,
                            Entity::MERCHANT_ID        => $adjustment[Adjustment\Entity::MERCHANT_ID],
                            Entity::PAYMENT_ID         => $adjustment[Entity::PAYMENT_ID],
                            Entity::REASON_ID          => 'NotAvailable00',
                            Entity::AMOUNT             => abs($adjustment[Adjustment\Entity::AMOUNT]),
                            Entity::CURRENCY           => $adjustment[Adjustment\Entity::CURRENCY],
                            Entity::REASON_CODE        => 'not_available',
                            Entity::REASON_DESCRIPTION => 'Not Available',
                            Entity::PHASE              => $adjustment[Entity::PHASE],
                            Entity::STATUS             => Status::LOST,
                            Entity::DEDUCT_AT_ONSET    => 0,
                            Entity::CREATED_AT         => $adjustment[Adjustment\Entity::CREATED_AT],
                            Entity::UPDATED_AT         => $adjustment[Adjustment\Entity::UPDATED_AT],
                            Entity::RAISED_ON          => $adjustment[Adjustment\Entity::CREATED_AT],
                            Entity::EXPIRES_ON         => $adjustment[Adjustment\Entity::UPDATED_AT],
                        ]
                    );

                    DB::table(Table::ADJUSTMENT)
                        ->where(Adjustment\Entity::ID, $adjustment[Adjustment\Entity::ID])
                        ->update(
                            [
                                Adjustment\Entity::ENTITY_TYPE => E::DISPUTE,
                                Adjustment\Entity::ENTITY_ID   => $id,
                            ]
                        );
                });

                $succeeded++;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::DISPUTE_ADJUSTMENT_MIGRATE_ERROR,
                    [
                        'id'         => $adjustment[Adjustment\Entity::ID],
                        'payment_id' => $adjustment[Entity::PAYMENT_ID]
                    ]);

                $failed++;

                $failedIds[] = $adjustment[Adjustment\Entity::ID];
            }

            $processed++;
        }

        return [
            'success' => $succeeded,
            'failure' => $failed,
            'total' => $processed,
            'failed' => $failedIds
        ];
    }

    protected function setRelationsAndDerivedAttributes(
        Entity $dispute,
        Entity $parent = null,
        Payment\Entity $payment,
        Reason\Entity $reason)
    {
        $merchant = $payment->merchant;

        $dispute->associateReason($reason);

        $dispute->payment()->associate($payment);

        $dispute->merchant()->associate($merchant);

        $dispute->parent()->associate($parent);
    }

    protected function handleDisputeClosure(Entity $dispute, array $input)
    {
        if ($dispute->isClosed() === false)
        {
            return;
        }

        $payment = $dispute->payment;

        $dispute->setResolvedAt(Carbon::now()->getTimestamp());

        $dispute->setDeductionReversalAt(null);

        $payment->setDisputed(false);

        $this->repo->saveOrFail($payment);

        $skipDeduct = (isset($input[Entity::SKIP_DEDUCTION])) ? boolval($input[Entity::SKIP_DEDUCTION]) : false;

        if (($skipDeduct === false) and ($dispute->isLost() === true))
        {
            $this->handleDeduction($input, $dispute);
        }

        if ($this->shouldReverse($dispute) === true)
        {
            $this->createPositiveAdjustmentAndUpdateDispute($dispute);
        }
    }

    protected function handleDeduction(array $input, Entity $dispute): void
    {
        $recoveryMethod = (isset($input[Entity::RECOVERY_METHOD])) ? $input[Entity::RECOVERY_METHOD] : RecoveryMethod::ADJUSTMENT;

        if ($recoveryMethod === RecoveryMethod::ADJUSTMENT)
        {
            $this->handleLostDisputeAdjustments($dispute, $input);

        }
        else
        {
            if ($recoveryMethod === RecoveryMethod::REFUND)
            {
                $this->handleLostDisputeRefunds($dispute, $input);
            }
        }
    }

    public function handleLostDisputeRefunds(Entity $dispute, array $input)
    {
        $acceptedDisputeAmount = $this->getAcceptedDisputeAmount($dispute, $input);

        $payment = $this->repo->payment->findOrFail($dispute->getPaymentId());

        // need to explicitly set and save here because if a payment is already in disputed state
        // we dont allow refunds on it. in case of an error, this is in a txn block and the txn will be rolled back
        $payment->setDisputed(false);

        $this->repo->payment->saveOrFail($payment);

        $this->createRefundAndUpdateDispute($dispute, $acceptedDisputeAmount);
    }

    protected function handleLostDisputeAdjustments(Entity $dispute, array $input)
    {
        $acceptedDisputeAmount = $this->getAcceptedDisputeAmount($dispute, $input);

        if ($dispute->getAmountDeducted() === 0)
        {
            $this->createNegativeAdjustmentAndUpdateDispute($dispute, $acceptedDisputeAmount);
        }
        else
        {
            // If amount_deducted is not zero, it is equal to the disputed amount only

            if (($dispute->getAmountDeducted() - $acceptedDisputeAmount) > 0)
            {
                $this->createPositiveAdjustmentAndUpdateDispute($dispute,
                    $dispute->getAmountDeducted() - $acceptedDisputeAmount);
            }
        }

        $dispute->setInternalStatus(InternalStatus::LOST_MERCHANT_DEBITED);
    }

    protected function shouldReverse(Entity $dispute): bool
    {
        return (($dispute->isWon() === true) and
                ($dispute->getAmountDeducted() > 0) and
                ($dispute->getAmountReversed() === 0));
    }

    /**
     * @param Entity $dispute
     * @param $acceptedAmount is in INR always. Hence, compare against baseAmount
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     */
    public function createRefundAndUpdateDispute(Entity $dispute, $acceptedAmount)
    {
        // https://docs.google.com/spreadsheets/d/1znRQjMiV7WFywAo1a7qb5WCHky96D6iycCbcYulyD7s/edit#gid=1471838983&range=C16
        if ($acceptedAmount > $dispute->payment->getBaseAmountUnRefunded())
        {
            $message = 'Cannot create refund for dispute accept because dispute amount is greater than unrefunded amount';

            throw new Exception\BadRequestValidationFailureException($message);
        }

        $refundCreateInput = [
            Refund\Entity::AMOUNT  => $acceptedAmount, //there is a validation that accept_amount does not support non INR
            Refund\Entity::NOTES  => [
                'reason' => $dispute->getPublicId(),
            ],
        ];

        $refundId = (new Payment\Service)->refund(Payment\Entity::getSignedId($dispute->getPaymentId()), $refundCreateInput)[Refund\Entity::ID];

        $dispute->setAmountDeducted($dispute->getBaseAmount() ?? $dispute->getAmount());

        $dispute->setInternalStatus(InternalStatus::LOST_MERCHANT_DEBITED);

        $this->updateDeductionSourceTypeAndId($dispute,
            EntityConstants::REFUND,
        Refund\Entity::verifyIdAndStripSign($refundId));
    }

    protected function createNegativeAdjustmentAndUpdateDispute(Entity $dispute, int $amount = 0)
    {
        if ($amount === 0)
        {
            $amount = $dispute->getBaseAmount() ?: $dispute->getAmount();
        }

        $input = [
            Adjustment\Entity::CURRENCY    => $dispute->getBaseCurrency() ?: $dispute->getCurrency(),
            Adjustment\Entity::AMOUNT      => 0 - $amount,
            Adjustment\Entity::DESCRIPTION => self::DEBIT_ADJUSTMENT_DESCRIPTION,
        ];

        if ($dispute->isBackfill() === false)
        {
            $adjustment = (new Adjustment\Core)->createAdjustmentForSource($input, $dispute);

            $this->updatePaymentRefundedAmount($dispute);

            $this->updateDeductionSourceTypeAndId($dispute, $adjustment->getEntityName(), $adjustment->getId());
        }

        $dispute->setAmountDeducted($amount);
    }

    protected function createPositiveAdjustmentAndUpdateDispute(Entity $dispute, int $amount = 0)
    {
        if ($amount === 0)
        {
            $amount = $dispute->getAmountDeducted();
        }

        $input = [
            Adjustment\Entity::CURRENCY    => $dispute->getBaseCurrency() ?: $dispute->getCurrency(),
            Adjustment\Entity::AMOUNT      => $amount,
            Adjustment\Entity::DESCRIPTION => self::CREDIT_ADJUSTMENT_DESCRIPTION,
        ];

        if ($dispute->isBackfill() === false)
        {
            (new Adjustment\Core)->createAdjustmentForSource($input, $dispute);
        }

        $dispute->setAmountReversed($amount);

        $this->reversePaymentRefundAttributesDueToPositiveAdjustment($dispute);

        $dispute->resetDeductionSourceAttributes();
    }

    protected function getAcceptedDisputeAmount(Entity $dispute, array $input)
    {
        $disputeBaseAmount = $dispute->getBaseAmount() ?: $dispute->getAmount();

        if (isset($input[Entity::ACCEPTED_AMOUNT]) === false)
        {
            return $disputeBaseAmount;
        }

        $dispute->getValidator()->validateAcceptedDisputeAmount($disputeBaseAmount, $input);

        return $input[Entity::ACCEPTED_AMOUNT];
    }

    /**
     *  Checks if the new parent is not same as existing parent
     *  and is eligible to become a parent (has no child)
     *
     * @param array $input
     * @param Entity|null $dispute
     * @return null
     */
    protected function checkAndGetParent(array $input, Entity $dispute = null)
    {
        if (isset($input[Entity::PARENT_ID]) === false)
        {
            return null;
        }

        if ($dispute !== null)
        {
            // Check if new parent is existing parent

            if (($dispute->isChildDispute() === true) and
                ($dispute->getParentId() === $input[Entity::PARENT_ID]))
            {
                $this->trace->info(
                    TraceCode::DISPUTE_SAME_PARENT_LINKING,
                    [
                        'input'      => $input,
                        'dispute_id' => $dispute->getId()
                    ]);

                unset($input[Entity::PARENT_ID]);

                return null;
            }
        }

        $parent = $this->repo->dispute->findOrFailPublic($input[Entity::PARENT_ID]);

        $parent->getValidator()->validateDisputeCanBecomeParent();

        return $parent;
    }

    protected function sendDisputeMailToAdmin(Entity $dispute, array $input)
    {
        $submit        = (bool) ($input[Entity::SUBMIT] ?? false);
        $acceptDispute = (bool) ($input[Entity::ACCEPT_DISPUTE] ?? false);


        $data = $this->getSendDisputeMailToAdminData($dispute);

        if ($dispute->hasMerchantAcceptedStatus($acceptDispute) === true)
        {
            Mail::queue(new DisputeMailer\Admin\AcceptedAdmin($data));
        }

        if (($submit === true) and ($dispute->getStatus() === Status::UNDER_REVIEW))
        {
            Mail::queue(new DisputeMailer\Admin\SubmittedAdmin($data));
        }
    }

    /**
     *  Get all the default mails for disputes. Dispute PoCs and merchant email
     *
     * @param Merchant\Entity $merchant
     *
     * @return array
     */

    public function getDefaultEmailsForDispute(Merchant\Entity $merchant) : array
    {
        $emails = [];

        $merchantEmailMap = (new MerchantEmail\Service)->fetchEmailByMerchantIdsAndTypes(
            [$merchant->getId()], self::POC_EMAIL_TYPES
        );

        if (empty($merchantEmailMap[$merchant->getId()]) === false)
        {
            foreach ($merchantEmailMap[$merchant->getId()] as $emailType => $emailArray)
            {
                $emails = array_merge($emails, $emailArray);
            }
        }
        else
        {
            $emails[] = $merchant->getEmail();
        }

        $emails = array_unique($emails);

        return $emails;
    }

    public function getEmailsForCreateNotify(Merchant\Entity $merchant, array $input) : array
    {
        if (empty($input[Entity::MERCHANT_EMAILS]) === false)
        {
            $emails = $input[Entity::MERCHANT_EMAILS];

            $emails = array_unique($emails);
        }
        else
        {
            // ToDo : Phase 2 : Add cc field in dashboard and support to fetch here (rzpinternal in merchant emails)
            // Fetching merchant chargeback PoC. If not available, fetches merchant registered email
            $emails = $this->getDefaultEmailsForDispute($merchant);
        }

        return $emails;
    }

    /**
     * @param array $merchantData
     * @return array
     */
    private function getMerchantPocEmails(array $merchantData) : array
    {
        $merchantIds = array_keys($merchantData);

        $merchantEmailMap = (new MerchantEmail\Service)->fetchEmailByMerchantIdsAndTypes(
            $merchantIds, self::POC_EMAIL_TYPES
        );

        foreach ($merchantEmailMap as $merchantId => $emailMap)
        {
            $emails = [];

            foreach ($emailMap as $emailType => $emailArray)
            {
                $emails = array_merge($emails, $emailArray);
            }

            if (empty($emails) === false)
            {
                $emails = array_unique($emails);

                $merchantData[$merchantId][MerchantEntity::EMAIL] = $emails;
            }
        }

        return $merchantData;
    }

    /**
     * @param array $merchantData
     * @param array $disputeData
     */
    public function sendAggregatedEmails(array $merchantData, array $disputeData)
    {
        // Update merchant data with poc emails
        $merchantData = $this->getMerchantPocEmails($merchantData);

        foreach ($merchantData as $merchantId => $data)
        {
            $bulkMailData[EntityConstants::MERCHANT][MerchantEntity::ID]    = $merchantId;
            $bulkMailData[EntityConstants::MERCHANT][MerchantEntity::NAME]  = $data[MerchantEntity::NAME];
            $bulkMailData[EntityConstants::MERCHANT][MerchantEntity::EMAIL] = $data[MerchantEntity::EMAIL];

            foreach ($data[Constants::DISPUTES] as $disputePhase => $publicDisputeIds)
            {
                $bulkMailData[Entity::PHASE] = $disputePhase;

                $bulkMailData['hasDeductAtOnset'] = false;

                $bulkMailData[Constants::DISPUTES] = [];

                $disputeIds = [];
                foreach ($publicDisputeIds as $publicDisputeId)
                {
                    $dispute = $disputeData[$publicDisputeId];

                    if ((isset($dispute[Entity::DEDUCT_AT_ONSET]) === true) and
                        (boolval($dispute[Entity::DEDUCT_AT_ONSET]) === true))
                    {
                        $bulkMailData['hasDeductAtOnset'] = true;
                    }

                    $bulkMailData[Constants::DISPUTES][] = $dispute;

                    $disputeIds[] = Entity::stripDefaultSign($publicDisputeId);
                }

                $bulkMailData['totalPayments'] = count($publicDisputeIds);

                try
                {
                    Mail::queue(new DisputeMailer\BulkCreation($bulkMailData));

                    $this->trace->info(
                        TraceCode::DISPUTE_BULK_MAIL_QUEUED,
                        [
                            'dispute_ids' => $disputeIds,
                            'merchant_id' => $merchantId,
                            'phase'       => $disputePhase,
                        ]);

                    $this->repo->transaction(function() use ($disputeIds)
                    {
                        $listOfDisputeIdList = array_chunk($disputeIds, self::DISPUTE_BULK_UPDATE_LIMIT);

                        foreach ($listOfDisputeIdList as $disputeIdList)
                        {
                            $this->repo->dispute->markOpenDisputesAsNotified($disputeIdList);
                        }
                    });

                    $this->trace->info(
                        TraceCode::DISPUTE_BULK_NOTIFICATION_STATUS_UPDATED,
                        [
                            'dispute_ids' => $disputeIds,
                            'merchant_id' => $merchantId,
                            'phase'       => $disputePhase,
                        ]);
                }
                catch (\Throwable $e)
                {
                    $this->trace->traceException(
                        $e,
                        Trace::ERROR,
                        TraceCode::DISPUTE_BULK_MAIL_PROCESSING_ERROR,
                        [
                            'merchant_id' => $merchantId,
                            'phase'       => $disputePhase,
                            'dispute_ids' => $disputeIds,
                        ]
                    );
                }
            }
        }
    }

    protected function generateInputForMerchantEdit(Entity $dispute, array $input): array
    {
        $submit        = (bool) ($input[Entity::SUBMIT] ?? false);
        $acceptDispute = (bool) ($input[Entity::ACCEPT_DISPUTE] ?? false);

        if ($acceptDispute === true)
        {
            $input[Entity::STATUS] = Status::LOST;

            if (in_array($dispute->getPhase(), Phase::getNonTransactionalPhases(), true) === true)
            {
                $input[Entity::STATUS] = Status::CLOSED;
            }
        }
        else if ($submit === true)
        {
            $input[Entity::STATUS] = Status::UNDER_REVIEW;
        }

        unset($input[Entity::ACCEPT_DISPUTE], $input[Entity::SUBMIT]);

        return $input;
    }

    protected function fireDisputeStatusChangeWebhookEvent(Entity $dispute)
    {
        $status = $dispute->getStatus();

        if (($dispute->isDirty(Entity::STATUS) === false) or
            (isset(Status::$webhookEventMap[$status]) === false))
        {
            return;
        }

        $eventName = Status::$webhookEventMap[$status];

        $this->firePaymentDisputeWebhookEvent($dispute->payment, $dispute, $eventName);
    }

    protected function firePaymentDisputeWebhookEvent(Payment\Entity $payment, Entity $dispute, string $event)
    {
        if ($dispute->isBackfill() === true)
        {
            return;
        }

        //
        // `reason_description` should not be exposed on API or webhook responses.
        // However, since the dispute is created via admin dashboard, the publicSetter
        // used to under reason_description will not work in this flow
        //
        $dispute->makeHidden(Entity::REASON_DESCRIPTION);

        $eventPayload = [
            ApiEventSubscriber::MAIN => $payment,
            ApiEventSubscriber::WITH => [
                E::DISPUTE => $dispute,
            ],
        ];

        $eventName = 'api.' . $event;

        $this->app['events']->dispatch($eventName, $eventPayload);
    }

    public function initiateMerchantEmails()
    {
        return $this->mutex->acquireAndRelease(
            self::DISPUTE_BULK_EMAIL_MUTEX,
            function() {
                $this->trace->info(TraceCode::DISPUTE_BULK_MAIL_CRON_START);

                $startTime = microtime(true);

                $result = $this->processMerchantEmails();

                $result['time_taken'] = get_diff_in_millisecond($startTime);

                $result['success'] = true;

                $this->trace->info(TraceCode::DISPUTE_BULK_MAIL_CRON_END);

                return $result;
            },
            self::DISPUTE_BULK_EMAIL_MUTEX_TTL,
            ErrorCode::BAD_REQUEST_DISPUTE_BULK_EMAIL_OPERATION_IN_PROGRESS);
    }

    private function processMerchantEmails()
    {
        $disputes = $this->repo->dispute->getOpenDisputesForNotification();
        $merchantData = $disputeData = [];

        foreach ($disputes as $dispute)
        {
            $payment = $dispute->payment;
            $merchant = $dispute->merchant;
            $disputeEntity = $dispute->toArrayAdmin();
            $disputeReason = $dispute->reason->toArrayAdmin();

            $merchantData[$disputeEntity[Entity::MERCHANT_ID]][MerchantEntity::NAME]  = $merchant->getName();
            $merchantData[$disputeEntity[Entity::MERCHANT_ID]][MerchantEntity::EMAIL] = $merchant->getEmail();
            $merchantData[$disputeEntity[Entity::MERCHANT_ID]][Constants::DISPUTES][$disputeEntity[Entity::PHASE]][] = $disputeEntity[Entity::ID];

            $disputeData[$disputeEntity[Entity::ID]] = $this->getDisputeDataForMail($disputeEntity, $disputeReason);

            // add payment notes field
            $disputeData[$disputeEntity[Entity::ID]]['payment_notes']    = $payment->getNotes()->toArray();
            $disputeData[$disputeEntity[Entity::ID]]['customer_contact'] = $payment->getContact();
            $disputeData[$disputeEntity[Entity::ID]]['order_receipt']    = '';

            if ($payment->hasOrder() === true)
            {
                $disputeData[$disputeEntity[Entity::ID]]['order_receipt'] = $payment->order->getReceipt();
            }
        }

        $this->sendAggregatedEmails($merchantData, $disputeData);

        return ['total_disputes' => count($disputes)];
    }

    private function getDisputeDataForMail(array $dispute, array $reason) : array
    {
        $disputeData = [];

        foreach (self::BULK_CREATE_DISPUTES_MAIL_DATA as $key)
        {
            $disputeData[$key] = $dispute[$key];
        }

        foreach (self::BULK_CREATE_DISPUTES_MAIL_REASON_DATA as $key)
        {
            $disputeData[$key] = $reason[$key];
        }

        return $disputeData;
    }

    private function updateCustomerTicketIfApplicable(Entity $dispute)
    {
        if (self::ELIGIBLE_CUSTOMER_TICKET_UPDATE_MIN_TS > $dispute->getCreatedAt())
        {
            return;
        }

        if ($dispute->isCustomerDispute() === false)
        {
            return;
        }

        $customerSupportTicketID = substr($dispute->getGatewayDisputeId(), 7);

        $fdInstance = $this->getFreshdeskInstance($dispute);

        $response = $this->app['freshdesk_client']->fetchTicketById(
            $customerSupportTicketID, $fdInstance);

        $ticketTags = $response['tags'] ?? [];

        array_push($ticketTags, Customer\FreshdeskTicket\Constants::FD_TAGS_TRIGGERED_BY_RZP_DISPUTE_FLOW);

        $updateTicketContent = [
            'status'       => Customer\FreshdeskTicket\Constants::FD_TICKET_STATUS_OPEN,
            'tags'         => $ticketTags,
            'responder_id' => null,
        ];

        $this->app['freshdesk_client']->updateTicketV2(
            $customerSupportTicketID, $updateTicketContent, $fdInstance);
    }

    // https://razorpay.slack.com/archives/C9AKQB8BH/p1609309054496000
    public function processDisputeRefunds(array $input)
    {
        (new Validator)->validateInput('processDisputeRefund', $input);

        $paymentIdList = $this->repo->dispute->getPaymentIdsForLostDispute($input['from'], $input['to']);

        $this->trace->info(TraceCode::DISPUTE_REFUND_JOB_START, [
            'payment_id_count' => count($paymentIdList),
        ]);

        foreach ($paymentIdList as $paymentId)
        {
            $this->mutex->acquireAndRelease(
                $paymentId,
                function () use ($paymentId) {
                    $redis = $this->app['redis']->connection();

                    $key = sprintf(self::REFUND_PROCESS_REDIS_KEY, $paymentId);

                    try
                    {
                        $redisRes = $redis->set($key, 1, 'nx', 'ex', self::REFUND_PROCESS_REDIS_TTL);

                        if ($redisRes === null)
                        {
                            $this->trace->info(TraceCode::DISPUTE_REFUND_PAYMENT_PROCESS_SKIP, [
                                'payment_id' => $paymentId,
                                'reason'     => 'redis',
                            ]);
                            return;
                        }

                        $this->processDisputeRefundPayment($paymentId);
                    }
                    catch (\Throwable $e)
                    {
                        $redis->del($key);

                        $this->trace->traceException($e, Trace::ERROR, TraceCode::DISPUTE_REFUND_PAYMENT_PROCESS_REFUND_AMOUNTS, [
                            'payment_id' => $paymentId,
                        ]);
                    }
                }
            );
        }

        $this->trace->info(TraceCode::DISPUTE_REFUND_JOB_END);
    }

    public function processDisputeRefundPayment(string $paymentId)
    {
        $this->trace->info(TraceCode::DISPUTE_REFUND_PAYMENT_PROCESS_START, [
            'payment_id' => $paymentId,
        ]);

        $disputes = $this->repo->dispute->getDisputesByPaymentId($paymentId);

        $this->trace->info(TraceCode::DISPUTE_REFUND_PAYMENT_PROCESS_DISPUTE_INFO, [
            'payment_id' => $paymentId,
            'disputes'   => $disputes,
        ]);

        /** @var Payment\Entity $payment */
        $payment = $this->repo->payment->findOrFail($paymentId);

        [$totalRefundAmount, $totalRefundBaseAmount] = $this->getTotalRefundAmount($disputes, $payment);

        $this->trace->info(TraceCode::DISPUTE_REFUND_PAYMENT_PROCESS_REFUND_AMOUNTS, [
            'payment_id'  => $paymentId,
            'amount'      => $totalRefundAmount,
            'base_amount' => $totalRefundBaseAmount,
        ]);

        // process only if nothing refunded yet.
        if ($payment->getAmountRefunded() === 0)
        {
            $this->trace->info(TraceCode::DISPUTE_REFUND_PAYMENT_PROCESS_NO_PREVIOUS_REFUND, [
                'payment_id' => $paymentId,
            ]);

            if ($totalRefundAmount > $payment->getAmount())
            {
                $this->trace->info(TraceCode::DISPUTE_REFUND_PAYMENT_PROCESS_REFUND_AMOUNT_EXCEED, [
                    'payment_id'                 => $paymentId,
                    'current_amount_refunded'    => 0,
                    'total_refund_exceed_amount' => $totalRefundAmount,
                    'total_refund_amount'        => $payment->getAmount(),
                ]);

                $totalRefundAmount = $payment->getAmount();

                $totalRefundBaseAmount = $payment->getBaseAmount();
            }

            $this->refundPayment($payment, $totalRefundAmount, $totalRefundBaseAmount);
        }
        else
        {
            $previousAmountRefunded = $payment->getAmountRefunded();
            $previousBaseAmountRefunded = $payment->getBaseAmountRefunded();

            $amountUnrefunded = $payment->getAmountUnrefunded();

            if ($totalRefundAmount > $amountUnrefunded)
            {
                $this->trace->info(TraceCode::DISPUTE_REFUND_PAYMENT_PROCESS_REFUND_AMOUNT_EXCEED, [
                    'payment_id'          => $paymentId,
                    'total_refund_amount' => $totalRefundAmount,
                    'amount_unrefunded'   => $amountUnrefunded,
                ]);

                $totalRefundAmount = $amountUnrefunded;

                $totalRefundBaseAmount = $payment->getBaseAmountUnrefunded();
            }

            $this->refundPayment($payment, $totalRefundAmount, $totalRefundBaseAmount);

            $this->trace->info(TraceCode::DISPUTE_REFUND_PAYMENT_PROCESS_WITH_PREVIOUS_REFUND, [
                'payment_id'                     => $paymentId,
                'after_job_amount_refunded'      => $payment->getAmountRefunded(),
                'after_job_base_amount_refunded' => $payment->getBaseAmountRefunded(),
                'previous_amount_refunded'       => $previousAmountRefunded,
                'previous_base_amount_refunded'  => $previousBaseAmountRefunded,
            ]);
        }

        $this->trace->info(TraceCode::DISPUTE_REFUND_PAYMENT_PROCESS_END, [
            'payment_id' => $paymentId,
        ]);
    }

    private function refundPayment(Payment\Entity $payment, int $totalRefundAmount, int $totalRefundBaseAmount)
    {
        if ($totalRefundAmount <= 0)
        {
            $this->trace->info(TraceCode::DISPUTE_REFUND_ZERO_REFUND_AMOUNT_SKIPPED, [
                'payment_id'               => $payment->getId(),
                'total_refund_amount'      => $totalRefundAmount,
                'total_refund_base_amount' => $totalRefundBaseAmount,
            ]);

            return;
        }

        $payment->refundAmount($totalRefundAmount, $totalRefundBaseAmount);

        $this->repo->saveOrFail($payment);
    }

    private function reversePaymentRefundAttributesDueToPositiveAdjustment(Entity  $dispute)
    {
        $payment = $dispute->payment;

        $newAmountRefunded = max($payment->getAmountRefunded() - $dispute->getAmount(), 0);

        $newBaseAmountRefunded = max($payment->getBaseAmountRefunded() - $dispute->getBaseAmount(), 0);

        $payment->setAmountRefunded($newAmountRefunded);

        $payment->setBaseAmountRefunded($newBaseAmountRefunded);

        if ($payment->getAmountUnrefunded() === $payment->getAmount())
        {
            $payment->setRefundStatus(null);
        }
        else if ($payment->getAmountUnrefunded() < $payment->getAmount())
        {
            $payment->setRefundStatus(Payment\RefundStatus::PARTIAL);
        }

        $this->repo->saveOrFail($payment);
    }

    private function getTotalRefundAmount($disputes, Payment\Entity $payment): array
    {
        $totalRefundAmount = 0;
        $totalRefundBaseAmount = 0;

        foreach ($disputes as $dispute)
        {
            [$refundAmount, $refundBaseAmount] = $this->getAmountForRefund($dispute);

            $disputeAmountDeducted = $dispute->getAmountDeducted();
            $disputeAmountReversed = $dispute->getAmountReversed();

            $this->trace->info(TraceCode::DISPUTE_REFUND_DISPUTE_ADJUSTMENT_INFO, [
                'payment_id'                           => $payment->getId(),
                'dispute_id'                           => $dispute->getId(),
                'refund_amount_from_adjustments'       => $refundAmount,
                'refund_base_amount_from_adjustments'  => $refundBaseAmount,
                'dispute_amount_deducted'              => $disputeAmountDeducted,
                'dispute_amount_reversed'              => $disputeAmountReversed,
                'dispute_amount_reversed_deduced_diff' => $disputeAmountDeducted - $disputeAmountReversed,
            ]);

            $totalRefundAmount += $refundAmount;
            $totalRefundBaseAmount += $refundBaseAmount;
        }

        return [$totalRefundAmount, $totalRefundBaseAmount];
    }

    private function getAmountForRefund(Entity $dispute): array
    {
        // There are 2 ways to pass amount for creating new disputes
        // 1. pass `amount`
        // 2. pass `gateway_amount` & `gateway_currency`

        // For case 1,
        // Adjustment is done with dispute->amount
        // As per current data, all the disputes raised with `amount` (case 1) have currency = INR.
        // For this case, we can do:
        // payment->refundAmount(adjustment->amount, adjustment->amount)

        // For case 2,
        // Adjustments are done with dispute->baseAmount. Meaning: adjustment->amount = dispute->baseAmount
        // Where baseAmount = INR Amount of gateway_amount. if gateway_currency != INR, we charge 1% conversion fee.
        // For this case, we can do:
        // $conversionRate = $disputeAmount / $disputeBaseAmount
        // payment->refundAmount(adjustment->amount * $conversionRate, adjustment->amount)

        $adjustmentBaseAmount = 0;
        $adjustmentAmount = 0;

        $adjustments =  $dispute->adjustments;

        foreach ($adjustments as $adjustment)
        {
            if ($adjustment->getDescription() === self::DEBIT_ADJUSTMENT_DESCRIPTION)
            {
                $this->trace->info(TraceCode::DISPUTE_REFUND_DISPUTE_ADJUSTMENT_SKIPPED, [
                    'payment_id'    => $dispute->payment->getId(),
                    'dispute_id'    => $dispute->getId(),
                    'adjustment_id' => $adjustment->getId(),
                ]);

                continue;
            }

            if (is_null($dispute->getGatewayAmount()) === true)
            {
                // Case 1
                $singleAdjustmentAmount = $adjustment->getAmount();
                $singleAdjustmentBaseAmount = $singleAdjustmentAmount;
            }
            else
            {
                // Case 2
                $singleAdjustmentBaseAmount = $adjustment->getAmount();

                // convert baseAmount(Always INR) to amount(payment Currency)
                $disputeAmount = $dispute->getAmount();
                $disputeBaseAmount = $dispute->getBaseAmount();
                $conversionRate = $disputeAmount / $disputeBaseAmount;

                $singleAdjustmentAmount = $singleAdjustmentBaseAmount * $conversionRate;

                $singleAdjustmentAmount = (int) ceil($singleAdjustmentAmount);
            }

            $adjustmentAmount += $singleAdjustmentAmount;
            $adjustmentBaseAmount += $singleAdjustmentBaseAmount;
        }

        return [-$adjustmentAmount, -$adjustmentBaseAmount];
    }

    private function updatePaymentRefundedAmount(Entity $dispute)
    {
        /* If gateway_amount is not set, then
           - base_amount wont be set
           - amount will be logically same as base_amount (if it were to be set)
           - payment_currency and dispute_currency will be in INR

           else
            - base_amount would be set and will be in INR
            - amount will be the set in payment currency
        */

        $refundAmount = $refundBaseAmount = $dispute->getAmount();

        if (is_null($dispute->getGatewayAmount()) === false)
        {
            $refundBaseAmount = $dispute->getBaseAmount();
        }

        $payment = $dispute->payment;

        $payment->refundAmount($refundAmount, $refundBaseAmount);

        $this->repo->saveOrFail($payment);

        $dispute->payment->reload();
    }

    public function initiateRiskAssessment()
    {
        return $this->mutex->acquireAndRelease(
            self::DISPUTE_RISK_ASSESSMENT_MUTEX,
            function() {
                $this->trace->info(TraceCode::DISPUTE_RISK_ASSESSMENT_CRON_START);

                $result = $this->doRiskAnalysisAndNotifyRas();

                $this->trace->info(TraceCode::DISPUTE_RISK_ASSESSMENT_CRON_END);

                $result['success'] = true;

                return $result;
            },
            self::DISPUTE_RISK_ASSESSMENT_MUTEX_TTL,
            ErrorCode::BAD_REQUEST_DISPUTE_RISK_ASSESSMENT_OPERATION_IN_PROGRESS);
    }

    public function getDisputeDocumentTypesMetadata()
    {
        return Evidence\Document\Types::getTypesMetadata();
    }

    public function patchDisputeContestById($disputeId, $input)
    {
        $disputeId = Entity::verifyIdAndStripSign($disputeId);

        $dispute = $this->repo->dispute->findByIdAndMerchantId($disputeId, $this->merchant->getId());

        $dispute = $this->repo->transaction(function () use ($dispute, $input) {
            $evidence = (new Evidence\Core)->handlePatchDisputeEvidence($dispute, $input);

            return $dispute->refresh();
        });

        return $dispute;
    }

    public function postDisputeAcceptById($disputeId, $input)
    {
        $disputeId = Entity::verifyIdAndStripSign($disputeId);

        $dispute = $this->repo->dispute->findByIdAndMerchantId($disputeId, $this->merchant->getId());

        $dispute = $this->repo->transaction(function() use ($dispute, $input) {
             (new Evidence\Core)->handlePatchDisputeEvidence($dispute, [
              Evidence\Constants::ACTION => Evidence\Action::ACCEPT,
           ]);

            return $dispute->refresh();

        });

        return $dispute;
    }

    public function deductionReversalCron()
    {
        $this->trace->info(TraceCode::DISPUTE_DEDUCTION_REVERSAL_CRON, [
            'message' => 'started',
        ]);

        $disputes = $this->repo->dispute->getDisputesForDeductionReversal();

        $this->trace->info(TraceCode::DISPUTE_DEDUCTION_REVERSAL_CRON, [
            'message'       => 'retrieved disputes',
            'dispute_ids'   => $disputes->pluck('id'),
        ]);

        $result = [];

        foreach ($disputes as $dispute)
        {
            try
            {
                $this->update($dispute, [
                    Entity::STATUS      => Status::WON,
                    Entity::BACKFILL    => false,
                ]);

                $result[] = [
                    'id'        => $dispute->getId(),
                    'success'   => true,
                ];
            }
            catch (\Exception $exception)
            {
                $this->trace->traceException($exception);

                $result[] = [
                    'id'        => $dispute->getId(),
                    'success'   => false,
                    'exception' => $exception->getMessage(),
                ];
            }
        }


        return $result;
    }

    private function doRiskAnalysisAndNotifyRas()
    {
        $yesterdayTimestamp = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $todayTimestamp = Carbon::today(Timezone::IST)->getTimestamp();

        $merchantIds = $this->repo->dispute->getMerchantIdsForRiskAnalysis($yesterdayTimestamp, $todayTimestamp);

        $this->trace->info(
            TraceCode::DISPUTE_RISK_ASSESSMENT_MERCHANTS_TO_PROCESS,
            [
                'count' => count($merchantIds),
            ]
        );

        $totalMerchantsWithDisputesNotifiedToRas = 0;

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $this->trace->info(
                    TraceCode::DISPUTE_RISK_ASSESSMENT_NOTIFY_RAS_INITIATED,
                    [
                        'count' => count($merchantIds),
                        'from'  => $yesterdayTimestamp,
                        'to'    => $todayTimestamp,
                    ]
                );

                $merchant = $this->repo->merchant->findOrFail($merchantId);

                $merchantAppsExemptFromRiskCheck = $merchant->isFeatureEnabled(Feature\Constants::APPS_EXTEMPT_RISK_CHECK);

                $rasAlertRequest = [
                    'merchant_id'     => $merchantId,
                    'entity_type'     => 'dispute_raised',
                    'entity_id'       => $merchantId,
                    'category'        => 'dispute',
                    'source'          => 'api_service',
                    'event_type'      => 'daily_notification',
                    'event_timestamp' => $todayTimestamp - 1,
                    'data'            => [
                        'apps_exempt_risk_check' => ($merchantAppsExemptFromRiskCheck === true ? '1' : '0'),
                    ],
                ];

                NotifyRas::dispatch($this->mode, $rasAlertRequest);

                $totalMerchantsWithDisputesNotifiedToRas++;
            }
            catch(\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::DISPUTE_RISK_ASSESSMENT_NOTIFY_RAS_FAILED,
                    [
                        'merchant_id' => $merchantId,
                        'from'        => $yesterdayTimestamp,
                        'to'          => $todayTimestamp,
                    ]
                );
            }
        }

        $result = [
            'total_merchants_identified'          => count($merchantIds),
            'total_merchant_notifications_to_ras' => $totalMerchantsWithDisputesNotifiedToRas,
        ];

        return $result;
    }

    public function getSendDisputeMailToAdminData(Entity $dispute): array
    {
        $org = $dispute->merchant->org;

        $data = [
            'dispute'            => $dispute->toArrayAdmin(),
            'payment'            => $dispute->payment->toArrayAdmin(),
            'dashboard_hostname' => $org->getPrimaryHostName(),
        ];

        return $data;
    }

    /**
     *  Reference: https://docs.google.com/spreadsheets/d/1Uh_s0rm3PO9GOdiNVo6xRWdaG13wsD6W_YwJMZ_4OEE/edit?ts=60f15177#gid=0
     * Tldr:
     * 1. if its customer dispute -> refund
     * 2. if not, follow above spreadsheet
     */
    public function getRecoveryMethodForDisputeAccept(Entity $dispute): string
    {
        $payment = $dispute->payment;

        if (($payment === null) or
            ($payment->isInternational() === true)
        )
        {
            return RecoveryMethod::RISK_OPS_REVIEW;
        }

        if ($dispute->isCustomerDispute() === true)
        {
            return RecoveryMethod::REFUND;
        }

        switch ($payment->getMethod())
        {
            case Method::CARD:
                return $this->getRecoveryMethodForCardDispute($dispute);
            case Method::NETBANKING:
                return $this->getRecoveryMethodForNetbankingDispute($dispute);
            case Method::UPI:
                return $this->getRecoveryMethodForUpiDispute($dispute);
            case Method::WALLET:
                return $this->getRecoveryMethodForWalletDispute($dispute);
        }

        return RecoveryMethod::RISK_OPS_REVIEW;
    }

    protected function getRecoveryMethodForCardDispute(Entity $dispute): string
    {
        return RecoveryMethod::ADJUSTMENT;
    }

    protected function getRecoveryMethodForNetbankingDispute(Entity $dispute): string
    {
        if (in_array($dispute->payment->getGateway(), RecoveryMethod::NETBANKING_RECOVER_VIA_REFUND_GATEWAYS, true) === true)
        {
            return RecoveryMethod::REFUND;
        }

        return RecoveryMethod::RISK_OPS_REVIEW;
    }

    protected function getRecoveryMethodForUpiDispute(Entity $dispute): string
    {
        if (in_array($dispute->payment->getGateway(), RecoveryMethod::UPI_RECOVER_VIA_ADJUSTMENT_GATEWAYS, true) === true)
        {
            return RecoveryMethod::ADJUSTMENT;
        }

        return RecoveryMethod::RISK_OPS_REVIEW;
    }

    protected function getRecoveryMethodForWalletDispute(Entity $dispute): string
    {
        if (in_array($dispute->payment->getGateway(), RecoveryMethod::WALLET_RECOVER_VIA_ADJUSTMENT_GATEWAYS, true) === true)
        {
            return RecoveryMethod::ADJUSTMENT;
        }

        if (in_array($dispute->payment->getGateway(), RecoveryMethod::WALLET_RECOVER_VIA_REFUND_GATEWAYS, true) === true)
        {
            return RecoveryMethod::REFUND;
        }


        return RecoveryMethod::RISK_OPS_REVIEW;
    }

    protected function updateDeductionSourceTypeAndId(Entity $dispute, string $entityType, string $entityId)
    {
        $dispute->setDeductionSourceType($entityType);

        $dispute->setDeductionSourceId($entityId);
    }

    protected function getFreshdeskInstance(Entity $dispute)
    {
        $fdInstance = FreshdeskConstants::URL;

        if ($dispute->getCreatedAt() > DisputeConstants::FD_IND_INSTANCE_ROLLOUT_TS)
        {
            $fdInstance = FreshdeskConstants::URLIND;
        }

        return $fdInstance;
    }

    protected function preProcessInputForCreate(array $input) : array
    {
        $input[Entity::INTERNAL_RESPOND_BY] = $input[Entity::INTERNAL_RESPOND_BY] ?? time() + DisputeConstants::DEFAULT_INTERNAL_RESPOND_BY_IN_SECONDS;

        return $input;
    }

    /**
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function preProcessInputForUpdate(Entity $dispute, array $input)
    {
       $input = $this->preProcessInputForUpdateStatusAndInternalStatusAttributes($dispute, $input);

        return $this->preProcessInputForDefaultDeductionReversalSchedule($dispute, $input);
    }

    /**
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function preProcessInputForUpdateStatusAndInternalStatusAttributes(Entity $dispute, array $input): array
    {
        if ((isset($input[Entity::STATUS]) === false) and
            (isset($input[Entity::INTERNAL_STATUS]) === false))
        {
            return $input;
        }

        if ((isset($input[Entity::STATUS]) === true) and
            (isset($input[Entity::INTERNAL_STATUS]) === false))
        {
            $newStatus = $input[Entity::STATUS];

            $input[Entity::INTERNAL_STATUS] = InternalStatus::getInternalStatusCorrespondingToStatus($newStatus, $dispute);

            return $input;
        }

        if ((isset($input[Entity::STATUS]) === false) and
            (isset($input[Entity::INTERNAL_STATUS]) === true))
        {
            $newInternalStatus = $input[Entity::INTERNAL_STATUS];

            $input[Entity::STATUS] = InternalStatus::getStatusCorrespondingToInternalStatus($newInternalStatus);

            return $input;
        }

        return $input;
    }

    protected function preProcessInputForDefaultDeductionReversalSchedule(Entity $dispute, array $input)
    {
        if ($dispute->getDeductAtOnset() === false)
        {
            return $input;
        }

        if (isset($input[Entity::DEDUCTION_REVERSAL_AT]) === true)
        {
            return $input;
        }

        if ((isset($input[Entity::INTERNAL_STATUS]) === false) or
            ($input[Entity::INTERNAL_STATUS] !== InternalStatus::REPRESENTED))
        {
            return $input;
        }

        $input[Entity::DEDUCTION_REVERSAL_AT] = time() + DisputeConstants::DEFAULT_DEDUCTION_REVERSAL_AT_IN_SECONDS;

        return $input;
    }


}
