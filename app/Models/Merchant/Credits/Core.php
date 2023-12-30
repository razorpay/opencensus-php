<?php

namespace RZP\Models\Merchant\Credits;

use App;
use Mail;

use RZP\Exception;
use RZP\Jobs\Ledger\CreateLedgerJournal as LedgerEntryJob;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Constants;
use RZP\Models\Merchant\Metric;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Promotion;
use RZP\Constants\Product;
use RZP\Models\Admin\Action;
use RZP\Models\Merchant\Credits;
use RZP\Models\Transaction\Processor\Ledger;
use RZP\Models\Ledger\ReverseShadow\CreditLoading as ReverseShadowCreditLoading;
use RZP\Mail\Merchant\RazorpayX\Credits\ConfirmationForKycUsers;
use RZP\Mail\Merchant\RazorpayX\Credits\ConfirmationForChurnedUsers;
use RZP\Models\Ledger\MerchantCreditJournalEvents;
use Neves\Events\TransactionalClosureEvent;
use Razorpay\Trace\Logger as Trace;

class Core extends Base\Core
{

    const FUND_ADDITION_WEBHOOK_MUTEX_TTL = 60;
    const FUND_ADDITION_WEBHOOK_MUTEX_RETRIES = 40;
    const FUND_ADDITION_WEBHOOK_MUTEX_MIN_RETRY_DELAY = 500;
    const FUND_ADDITION_WEBHOOK_MUTEX_MAX_RETRY_DELAY = 1000;

    // This map indicates credit point to money ratio for a product.
    // example for banking, only payouts will be consuming credits and
    // so not adding the concept of a sub product like payouts, FAV etc
    // for payouts currently 1 CP = 1 Rupee.
    protected static $productSubProductCreditPointsToMoneyRatio = [
        Product::BANKING => [
            Entity::DEAFULT => 1
        ]
    ];

   // gives the relation of point to money
    public function getCreditInAmount($credits, $product = Product::BANKING, $subProduct = 'default')
    {
        $ratio = self::$productSubProductCreditPointsToMoneyRatio[$product][$subProduct] ?? null;

        if ($ratio === null)
        {
            return $credits;
        }

        return (int) ($credits * $ratio);
    }

    // gives the value of a certain amount in points
    public function getCreditInPoints($amount, $product = Product::BANKING, $subProduct = 'default')
    {
        $ratio = self::$productSubProductCreditPointsToMoneyRatio[$product][$subProduct] ?? null;

        if ($ratio === null)
        {
            return $amount;
        }

        return (int) ($amount * (1 / $ratio));
    }

    public function create($merchant, $input, $payment = null)
    {
        if (isset($input['type']) and $input['type'] === Credits\Type::FEE_CREDIT)
        {
            $input['product'] = Product::BANKING;
        }

        $creditsLog = (new Credits\Entity)->build($input);

        $creditsLog->getValidator()->validateCreditsValue($input);

        $creditsLog->setAuditAction(Action::CREATE_MERCHANT_CREDITS);

        $creditsLog->merchant()->associate($merchant);

        if ($input['type'] !== Type::FEE_CREDIT)
        {
            $balance = $this->repo->balance->getMerchantBalance($merchant);

            $creditsLog->getValidator()->validateCreditsType($balance, $creditsLog->getType());

            $currentMerchantCredits = $creditsLog->getMerchantCredits();

            $creditsLog->getValidator()->validateBalanceCredits(
                $creditsLog->getValue(), $currentMerchantCredits, $creditsLog->getType());
        }

        $this->app['workflow']->handle((new \stdClass), $creditsLog);

        $resource = "credits_create" . $merchant->getId() . '_' . $creditsLog->getType();

        $mutex = App::getFacadeRoot()['api.mutex'];

        return $mutex->acquireAndRelease(
            $resource,
            function() use ($creditsLog, $merchant, $payment) {
                $this->repo->transaction(function() use ($merchant, $creditsLog, $payment)
                {
                    $this->repo->saveOrFail($creditsLog);

                    $type = $creditsLog->getType();

                    $this->updateCreditsInMerchantAccount($merchant, $creditsLog->getValue(), $type);

                    $this->createLedgerEntriesForCreditLoadingReverseShadow($creditsLog, $payment);

                    $this->createLedgerEntriesForMerchantCreditLoading($creditsLog, $payment);

                    return $creditsLog;
                });

                return $creditsLog;
            },
            self::FUND_ADDITION_WEBHOOK_MUTEX_TTL,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
            self::FUND_ADDITION_WEBHOOK_MUTEX_RETRIES,
            self::FUND_ADDITION_WEBHOOK_MUTEX_MIN_RETRY_DELAY,
            self::FUND_ADDITION_WEBHOOK_MUTEX_MAX_RETRY_DELAY
        );
    }

    public function updateCreditsInMerchantAccount($merchant, $credits, $type = Credits\Type::AMOUNT)
    {
        if ($type === Credits\Type::AMOUNT)
        {
            // Add the credits to merchant's main balance
            $merchantAmountCredits = $merchant->primaryBalance->reload()->getAmountCredits();

            $newCredits = $merchantAmountCredits + $credits;

            $this->repo->balance->editMerchantAmountCredits($merchant, $newCredits);

        }
        else if ($type === Credits\Type::FEE)
        {
            $merchantFeeCredits = $merchant->primaryBalance->reload()->getFeeCredits();

            $newCredits = $merchantFeeCredits + $credits;

            $this->repo->balance->editMerchantFeeCredits($merchant, $newCredits);
        }
        else if ($type === Credits\Type::REFUND)
        {
            $merchantRefundCredits = $merchant->primaryBalance->reload()->getRefundCredits();

            $newCredits = $merchantRefundCredits + $credits;

            $this->repo->balance->editMerchantRefundCredits($merchant, $newCredits);
        }
        else if ($type === Credits\Type::FEE_CREDIT)
        {
            $merchantFeeCredit = (new Repository())->getMerchantCreditsOfType($merchant->getId(), Type::FEE_CREDIT);

            $newCredits = $merchantFeeCredit + $credits;

            $this->trace->info(
                TraceCode::MERCHANT_FEE_CREDITS_LOADING_EVENT,
                [
                    'merchant'              => $merchant->getId(),
                    'merchant_fee_credit'   => $merchantFeeCredit,
                    'new_credit'            => $newCredits,
                ]);

            $creditBalance = (new Balance\Core)->createOrFetchCreditBalanceOfMerchant(
                $merchant,
                Credits\Type::FEE_CREDIT,
                Product::BANKING);

            $creditBalance->incrementBalance($credits);
        }
    }

    /*
     * Update credits in the credits Log and merchant credits.
     */
    public function updateCredits($creditsLog, $creditsValue)
    {
        //
        // When we update the credits, We need to subsequently add/subtract credits
        // from merchant balance.
        // Transaction is rolled back if merchant credit balance is less than zero.
        //
        $creditsLog->setAuditAction(Action::EDIT_MERCHANT_CREDITS);

        $mutex = App::getFacadeRoot()['api.mutex'];

        $mutexKey = Credits\Constants::MERCHANT_CREDIT_TYPE_MUTEX_PREFIX . $creditsLog->merchant->getId() . '_' . $creditsLog->getType();

        return $mutex->acquireAndRelease(
            $mutexKey,
            function() use ($creditsLog, $creditsValue)
            {
                $creditsLog->getValidator()->validateNewCreditsValue($creditsLog, (int) $creditsValue);

                return $this->repo->transaction(function() use ($creditsLog, $creditsValue)
                {
                    $creditsDifference = $creditsValue - $creditsLog->getValue();

                    $creditsLog->setValue($creditsValue);

                    $this->repo->saveOrFail($creditsLog);

                    $type = $creditsLog->getType();

                    $this->updateCreditsInMerchantAccount($creditsLog->merchant, $creditsDifference, $type);

                    return $creditsLog;
                });
            },
            Credits\Constants::MERCHANT_CREDIT_TYPE_MUTEX_TIMEOUT,
            ErrorCode::BAD_REQUEST_ANOTHER_CREDITS_OPERATION_IN_PROGRESS,
            Credits\Constants::MERCHANT_CREDIT_TYPE_MUTEX_ACQUIRE_RETRY_LIMIT
        );
    }

    public function assignCreditsToMerchant(array $input, Merchant\Entity $merchant)
    {

        $credit = $this->createCreditLogWithCreditBalance(
                                            $merchant,
                                            $input);
        return $credit;
    }

    protected function createCreditLogWithCreditBalance($merchant, $input)
    {
        $creditsExpiry = null;

        if (isset($input[Entity::EXPIRED_AT]) === true)
        {
            $creditsExpiry = $input[Entity::EXPIRED_AT];
        }

        $creditBalance = (new Balance\Core)->createOrFetchCreditBalanceOfMerchant(
                                                                $merchant,
                                                                $input[Entity::TYPE],
                                                                $input[Entity::PRODUCT],
                                                                $creditsExpiry);

        $creditsLog = (new Credits\Entity)->build($input);

        $creditsLog->setAuditAction(Action::CREATE_MERCHANT_CREDITS);

        $creditsLog->merchant()->associate($merchant);

        $creditsLog->balance()->associate($creditBalance);

        $currentMerchantCredits = $creditBalance->getBalance();

        $creditsLog->getValidator()->validateBalanceCredits(
            $creditsLog->getValue(), $currentMerchantCredits, $creditsLog->getType());

        $creditsLog = $this->repo->transaction(function() use ($merchant, $creditsLog, $creditBalance)
        {
            $this->repo->saveOrFail($creditsLog);

            $creditBalance->incrementBalance($creditsLog->getValue());

            return $creditsLog;
        });

        // check for reverse shadow for banking balance
        if ($creditsLog->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === true)
        {
            $this->processLedgerForReverseShadow($creditsLog);
        }
        else
        {
            $this->processLedgerForCreditAddition($creditsLog);
        }

        return $creditsLog;
    }

    public function checkMerchantStateAndSendCreditEmail($merchant, $credit)
    {
        $product = $credit->getProduct();

        if ($product !== Product::BANKING)
        {
            return;
        }

        $data = [
            Merchant\Constants::MERCHANT => [
                Merchant\Entity::EMAIL  => $merchant->getEmail(),
                Merchant\Entity::NAME   => $merchant->getName(),
            ],
            Entity::CREDITS  => $this->getCreditInAmount($credit->getValue(), $credit->getProduct()),
        ];

        $data[Entity::CREDITS] = $this->getFormattedAmount($data[Entity::CREDITS]);

        if ($data[Entity::CREDITS] < 0)
        {
            return;
        }
        // if merchant is already KYC verified that means he is already on X
        // but does not uses the X platform. So we will sending them churned
        // user email
        if ($merchant->isActivated() === true)
        {
            $mail = new ConfirmationForChurnedUsers($data);
        }
        else
        {
            // if merchant is not KYC verified that means he is on X
            // but has not submitted his kyc or basically not completed
            // his KYC, so we are sending him email to prompt for it
            $mail = new ConfirmationForKycUsers($data);
        }

        Mail::queue($mail);
    }

    public function checkIfCreditsAlreadyAppliedForBankingPromotion(Promotion\Entity $promotion, Merchant\Entity $merchant)
    {
        $credit = null;

        if ($promotion->getProduct() === Product::BANKING)
        {
            $credit = $this->repo->credits->findExistingCreditsForMerchantAndPromotion($promotion, $merchant);
        }

        return $credit;
    }

    public function getTypeAggregatedMerchantCredits(string $merchantId, string $product)
    {
        $credits = $this->repo->credits->getTypeAggregatedMerchantCreditsForProduct($merchantId, $product);

        return $credits;
    }

    protected function getFormattedAmount($amount)
    {
        $formattedAmount = number_format($amount / 100, 2, '.', '');

        return $formattedAmount;
    }

    protected function processLedgerForCreditAddition(Entity $creditLogs)
    {
        if (($this->app['config']->get('applications.ledger.enabled') === false) or
            ($creditLogs->getProduct() !== Balance\Product::BANKING))
        {
            return;
        }

        $ledgerJournalWritesEnabled = $creditLogs->merchant->isFeatureEnabled(Feature\Constants::LEDGER_JOURNAL_WRITES);

        $daLedgerJournalWritesEnabled = $creditLogs->merchant->isFeatureEnabled(Feature\Constants::DA_LEDGER_JOURNAL_WRITES);

        if ($ledgerJournalWritesEnabled === false and $daLedgerJournalWritesEnabled === false)
        {
            return;
        }

        $event = Ledger\Rewards::FUND_LOADING_PROCESSED;

        (new Ledger\Rewards)->pushTransactionToLedger($creditLogs,
                                                      $event);
    }

    /**
     * @param Entity $creditLogs
     *
     *
     * This function processes txn in reverse shadow mode
     * We call ledger in sync and use ledger response to
     * create txn in api db in async
     *
     */
    protected function processLedgerForReverseShadow(Entity $creditLogs)
    {

        if ($creditLogs->getProduct() !== Balance\Product::BANKING)
        {
            return;
        }
        // create journal in sync
        $ledgerPayload = (new Ledger\Rewards)->createPayloadForJournalEntry($creditLogs, Ledger\Rewards::FUND_LOADING_PROCESSED);
        try {
            (new Ledger\Rewards)->createJournalEntry($ledgerPayload);
        }
        catch (\Throwable $ex)
        {
            // trace and ignore exception
            $alertPayload = [
                'credits_id'            => $creditLogs->getId(),
                'ledger_payload'        => $ledgerPayload,
            ];
            $this->trace->traceException(
                $ex,
                TraceCode::LEDGER_JOURNAL_CREATE_FAILED_REVERSE_SHADOW,
                $alertPayload
            );
        }
    }

    private function createLedgerEntriesForCreditLoadingReverseShadow(Entity $creditsLog, $payment)
    {
        try
        {
            if ($creditsLog->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === false)
            {
                return;
            }

            (new ReverseShadowCreditLoading\Core())->createReverseShadowLedgerEntries($creditsLog, $payment);
        }
        catch(\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PG_LEDGER_ENTRY_FAILED,
                [
                    'credit_id' => $creditsLog->getId(),
                    'type'      => Feature\Constants::PG_LEDGER_REVERSE_SHADOW,
                ]);
            throw $e;
        }
    }

    private function createLedgerEntriesForMerchantCreditLoading(Entity $creditsLog, $payment)
    {
        try
        {

            if($creditsLog->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_JOURNAL_WRITES) === false)
            {
                return;
            }

            // use case where both shadow and reverse shadow are enabled. Since reverse shadow takes priority, we return from here itself
            if($creditsLog->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true)
            {
                return;
            }

            if($creditsLog->getType() === Credits\Type::AMOUNT )
            {
                $transactionMessage = MerchantCreditJournalEvents::createTransactionMessageForMerchantAmountCreditLoading($creditsLog);

                \Event::dispatch(new TransactionalClosureEvent(function () use ($transactionMessage) {
                    LedgerEntryJob::dispatchNow($this->mode, $transactionMessage);
                }));

                $this->trace->info(
                    TraceCode::MERCHANT_AMOUNTS_CREDITS_LOADING_EVENT,
                    [
                        'merchant' => $creditsLog->getMerchantId(),
                        'transactionMessage' => $transactionMessage,
                        'payment' => $payment,
                    ]);
            }
            else
            {
                $transactionMessage = MerchantCreditJournalEvents::createBulkTransactionMessageForMerchantCreditLoading($creditsLog, $payment);

                \Event::dispatch(new TransactionalClosureEvent(function () use ($transactionMessage) {
                    LedgerEntryJob::dispatchNow($this->mode, $transactionMessage, true);
                }));

                $this->trace->info(
                    TraceCode::MERCHANT_CREDITS_LOADING_EVENT,
                    [
                        'merchant' => $creditsLog->getMerchantId(),
                        'transactionMessage' => $transactionMessage,
                        'payment' => $payment,
                    ]);
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PG_LEDGER_ENTRY_FAILED,
                ['credit_id'             => $creditsLog->getId(),]);
        }
    }


    /**
     * registers reminders for amount credits to be expired in next 2 days
     *
     * @return array
     */
    public function registerReminderForExpiringAmountCredit($input): array
    {
        $now = time();

        $startTimestamp = $input[Credits\Constants::START_TIME] ?? $now - Constants::AMOUNT_CREDIT_REGISTER_DEFAULT_START_TIME_BUFFER;
        $endTimestamp = $input[Credits\Constants::END_TIME] ?? $now + Constants::AMOUNT_CREDIT_REGISTER_DEFAULT_END_TIME_BUFFER;

        $amountCredits = $this->repo->credits->fetchAmountCreditsExpiringWithinDateRange($startTimestamp, $endTimestamp);

        $this->trace->info(TraceCode::FETCH_EXPIRING_AMOUNT_CREDITS,
            [
                Constants::ENTRY_COUNT => count($amountCredits),
            ]
        );

        foreach ($amountCredits as $entry) {
            $entry->reload();

            $this->registerAmountCreditExpiryReminder($entry);
        }

        return [];
    }

    protected function registerAmountCreditExpiryReminder(Credits\Entity $credit)
    {
        $namespace = Constants::AMOUNT_CREDITS_EXPIRY_NAMESPACE;

        $url = sprintf('reminders/send/%s/credit/%s/%s', $this->mode, $namespace, $credit->getId());

        $request = [
            LedgerConstants::NAMESPACE     => $namespace,
            LedgerConstants::ENTITY_ID     => $credit->getId(),
            LedgerConstants::ENTITY_TYPE   => Constants::CREDIT,
            Constants::REMINDER_DATA => [
                Constants::EXPIRED_AT => $credit->getExpiredAt(),
            ],
            Constants::CALLBACK_URL  => $url,
        ];

        $merchant = $credit->merchant;

        // Reminders not created for merchants that are not onboarded onto reverse shadow
        if($merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === false)
        {
            return;
        }

        try
        {
            $response = $this->app['reminders']->createReminder($request, Merchant\Account::SHARED_ACCOUNT );

            $reminderId = array_get($response, 'id');

            $this->trace->count(Metric::PG_LEDGER_AMOUNT_CREDIT_EXPIRY_REMINDER_CREATED, [
                'mode' => $this->mode,
            ]);

            $this->trace->info(TraceCode::PG_LEDGER_AMOUNT_CREDIT_EXPIRY_REMINDER_CREATED, [
                'credit_id'   => $credit->getId(),
                'reminder_id' => $reminderId,
            ]);
        }
        catch (\Throwable $exception) {

            $this->trace->count(Metric::PG_LEDGER_AMOUNT_CREDIT_EXPIRY_REMINDER_FAILURE, [
                'mode' => $this->mode,
            ]);

            $this->trace->traceException(
                $exception,
                Trace::CRITICAL,
                TraceCode::PG_LEDGER_AMOUNT_CREDIT_EXPIRY_REMINDER_FAILURE,
                [
                    'entity_id'     => $credit->getId(),
                ]);
        }
    }
}
