<?php

namespace RZP\Models\Settlement\Ondemand;

use App;
use Config;
use Carbon\Carbon;

use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Metric;
use RZP\Exception;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Models\Pricing\Fee;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Models\User;
use RZP\Models\Payout;
use RZP\Models\Pricing;
use RZP\Models\Feature;
use RZP\Models\Reversal;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Services\KafkaProducer;
use RZP\Trace\TraceCode;
use RZP\Constants\Mode;
use RZP\Models\Admin\Org;
use RZP\Constants\Product;
use RZP\Models\Transaction;
use RZP\Constants\Timezone;
use RZP\Models\BankAccount;
use RZP\Models\VirtualAccount;
use RZP\Models\Merchant\Balance;
use RZP\Models\Currency\Currency;
use RZP\Models\Feature\Constants;
use RZP\Models\Merchant\FeeBearer;
use RZP\Models\Settlement\Ondemand\Bulk;
use RZP\Models\Settlement\OndemandPayout;
use RZP\Models\Settlement\Bucket;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\Settlement\Ondemand\Service as Service;
use RZP\Models\Settlement\Ondemand\FeatureConfig;
use RZP\Models\Pricing\Feature as PricingFeature;
use RZP\Jobs\SettlementOndemand\UpdateOndemandTriggerJob;
use RZP\Models\Ledger\ReverseShadow\Capital\Core as ReverseShadowCapitalCore;

class Core extends Base\Core
{
    const FULL_ES_DATALAKE_QUERY = "select merchant_id from hive.aggregate_pa.es_eligibility_list";

    const RESTRICTED_ES_DATALAKE_QUERY = "select merchant_id from hive.aggregate_pa.es_eligibility_day1";

    const ONDEMAND_PAYOUT_PROCESSED_EVENT = 'ondemand_payout.processed';

    const ONDEMAND_PAYOUT_REVERSED_EVENT  = 'ondemand_payout.reversed';

    public function createSettlementOndemand(array $input, Merchant\Entity $merchant, User\Entity $user = null, array $requestDetails = [])
    {
        if ($input[Entity::AMOUNT] > $merchant->primaryBalance->getBalance())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INSUFFICIENT_BALANCE,
                null,
                [
                    'amount'  => $input[Entity::AMOUNT],
                    'balance' => $merchant->primaryBalance->getBalance(),
                ]);
        }

        $this->checkMerchantFundsOnHold();

        $input = $input + [
            Entity::TOTAL_AMOUNT_SETTLED           => 0,
            Entity::TOTAL_AMOUNT_REVERSED          => 0,
            Entity::STATUS                         => Status::CREATED,
            Entity::CURRENCY                       => $input[Entity::CURRENCY] ?? Currency::INR,
            Entity::MAX_BALANCE                    => $input['settle_full_balance'] ?? 0,
            Entity::NOTES                          => $input[Entity::NOTES] ?? null,
            Entity::NARRATION                      => $input['description'] ?? null,
            Entity::SCHEDULED                      => isset($requestDetails['scheduled'])?$requestDetails['scheduled']: false,
            Entity::SETTLEMENT_ONDEMAND_TRIGGER_ID => isset($requestDetails['settlement_ondemand_trigger_id'])?$requestDetails['settlement_ondemand_trigger_id']: null
        ];


        $data = $input;

        unset($data['expand']);
        unset($data['settle_full_balance']);
        unset($data['description']);

        /** @var Entity $settlementOndemand */
        $settlementOndemand = (new Entity)->build($data);

        $settlementOndemand->generateId();

        $settlementOndemand->merchant()->associate($merchant);

        if (isset($user) === true)
        {
            $settlementOndemand->user()->associate($user);
        }

        $settlementOndemandPayouts = (new OndemandPayout\Service)
                                        ->createSettlementOndemandPayout($settlementOndemand, $requestDetails);

        $txn = $this->createTransaction($settlementOndemand);

        $settlementOndemand->setTotalAmountPending($settlementOndemand->getAmountToBeSettled());

        $this->repo->saveOrFail($settlementOndemand);

        return [$settlementOndemand, $settlementOndemandPayouts, $txn];
    }

    public function createSettlementOndemandWithReverseShadowOnLedger(array $input, Merchant\Entity $merchant, User\Entity $user = null, array $requestDetails = [], $skipLedgerOutboxEntry = false)
    {

        $reverseShadowCapital = new ReverseShadowCapitalCore();

        $validationResponse = $reverseShadowCapital -> validateBalance($input, $merchant);

        if ( !$validationResponse[Constants::IS_AMOUNT_VALID] )
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INSUFFICIENT_BALANCE,
                null,
                [
                    'amount' => $input[Entity::AMOUNT],
                    'balance' => $validationResponse[Constants::MERCHANT_BALANCE],
                ]
            );
        }

        $this->checkMerchantFundsOnHold();

        $input = $input + [
                Entity::TOTAL_AMOUNT_SETTLED           => 0,
                Entity::TOTAL_AMOUNT_REVERSED          => 0,
                Entity::STATUS                         => Status::CREATED,
                Entity::CURRENCY                       => $input[Entity::CURRENCY] ?? Currency::INR,
                Entity::MAX_BALANCE                    => $input['settle_full_balance'] ?? 0,
                Entity::NOTES                          => $input[Entity::NOTES] ?? null,
                Entity::NARRATION                      => $input['description'] ?? null,
                Entity::SCHEDULED                      => isset($requestDetails['scheduled'])?$requestDetails['scheduled']: false,
                Entity::SETTLEMENT_ONDEMAND_TRIGGER_ID => isset($requestDetails['settlement_ondemand_trigger_id'])?$requestDetails['settlement_ondemand_trigger_id']: null
            ];


        $data = $input;

        unset($data['expand']);
        unset($data['settle_full_balance']);
        unset($data['description']);

        /** @var Entity $settlementOndemand */
        $settlementOndemand = (new Entity)->build($data);

        $settlementOndemand->generateId();

        $settlementOndemand->merchant()->associate($merchant);

        if (isset($user) === true)
        {
            $settlementOndemand->user()->associate($user);
        }

        $settlementOndemandPayouts = (new OndemandPayout\Service)
            ->createSettlementOndemandPayout($settlementOndemand, $requestDetails);

        [$totalFees , $totalTax] = $this->calculateFees($settlementOndemandPayouts);

        $settlementOndemand->setFees($totalFees);

        $settlementOndemand->setTax($totalTax);

        $settlementOndemand->setTotalAmountPending($settlementOndemand->getAmountToBeSettled());

        $this->repo->saveOrFail($settlementOndemand);

        if(!$skipLedgerOutboxEntry) {
            $reverseShadowCapital->createLedgerEntryForSettlementOndemandProcessedInReverseShadow($settlementOndemand);
        }

        return [$settlementOndemand, $settlementOndemandPayouts];
    }

    public function calculateFees($settlementOndemandPayouts)
    {
        $totalFees = 0;

        $totalTax = 0;

        foreach ($settlementOndemandPayouts as $settlementOndemandPayout) {

            [$fees, $tax] = (new Pricing\Fee)->calculateMerchantFees($settlementOndemandPayout);

            $totalFees += $fees;

            $totalTax += $tax;
        }

        return [$totalFees, $totalTax];
    }

    public function isMerchantWithXSettlementAccount($merchantId) : bool
    {
        //1. First the settlement account for this mid is found out
        //2. Then the existence of a bank account connected to a virtual account with the settlement account details is checked
        //3. Finally the existence of a virtual account associated with X balance is checked with related bank_account_id
        $settlementBankAccount = (new BankAccount\Repository)->getSettlementAccountDetails($merchantId);

        if($settlementBankAccount != null)
        {
            try
            {
                $vaBankAccount = (new BankAccount\Repository)
                                                        ->getXVirtualAccountOrFail(
                                                            $merchantId,
                                                            $settlementBankAccount->getAccountNumber(),
                                                            $settlementBankAccount->getIfscCode());

                (new VirtualAccount\Repository)->findVirtualAccountWithXBalanceOrFail($vaBankAccount->getId());
            }
            catch (\Throwable $e)
            {
                return false;
            }

            return true;
        }

        return false;
    }

    public function createTransaction($settlementOndemand)
    {
        [$txn, $feeSplit] = (new Transaction\Processor\SettlementOndemand($settlementOndemand))
                                    ->createTransaction();

        $settlementOndemand->setFees($txn->getFee());

        $settlementOndemand->setTax($txn->getTax());

        $this->repo->saveOrFail($txn);

        return $txn;
    }

    public function getFeesSplit($input, $merchant, $user)
    {
        return $this->repo->beginTransactionAndRollback(function () use ($input, $merchant, $user)
        {
            if(($this->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true)) {

                $requestDetails = [];

                [$settlementOndemand, $settlementOndemandPayouts] = $this->createSettlementOndemandWithReverseShadowOnLedger(
                    $input,
                    $this->merchant,
                    $this->user,
                    $requestDetails,
                    true
                );
            }
            else
            {
                [$settlementOndemand, $settlementOndemandPayouts] = $this->createSettlementOndemand($input, $merchant, $user);
            }

            [$fees, $tax, $feesSplit] = (new Pricing\Fee)->calculateMerchantFees($settlementOndemandPayouts[0]);

            $feesSplit = $feesSplit->toArrayPublic();

            $feesSplit['items'][0]['amount'] = $settlementOndemand->getTotalFees() - $settlementOndemand->getTotalTax();

            $feesSplit['items'][1]['amount'] = $settlementOndemand->getTotalTax();

            return $feesSplit;
        });
    }

    public function createPartialReversal($settlementOndemandPayout, $reversalReason)
    {
        if ($settlementOndemandPayout->getStatus() === OndemandPayout\Status::REVERSED)
        {
            return;
        }

        $this->repo->transaction(
            function() use ($settlementOndemandPayout, $reversalReason)
            {
                /** @var Entity $settlementOndemand */
                $settlementOndemand = (new Repository)->findByIdAndMerchantIdWithLock(
                                            $settlementOndemandPayout->getOndemandId(),
                                            $settlementOndemandPayout->getMerchantId());

                $merchant = $this->repo->merchant->findOrFail($settlementOndemand->getMerchantId());

                (new Reversal\Core)->partialReversalForSettlementOndemand($settlementOndemand, $settlementOndemandPayout, $merchant);

                if($merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true) {
                    $this->updateOndemandPayoutOnPayoutReversal($settlementOndemandPayout, OndemandPayout\Status::REVERSAL_INITIATED, $reversalReason);
                }
                else {
                    $this->handleReversalTransactionCreated($settlementOndemand, $settlementOndemandPayout, OndemandPayout\Status::REVERSED, $reversalReason);
                }
            });
    }

    public function handleReversalTransactionCreated(Entity $settlementOndemand, OndemandPayout\Entity $settlementOndemandPayout, string $ondemandPayoutStatus, $reversalReason = null) {
        $settlementOndemandPayout = $this->updateOndemandPayoutOnPayoutReversal($settlementOndemandPayout, $ondemandPayoutStatus, $reversalReason);

        $this->updateOndemandOnPayoutReversal($settlementOndemand, $settlementOndemandPayout);
    }

    public function handleOndemandPayoutProcessed($settlementOndemand, $settlementOndemandPayout)
    {
        $settlementOndemand->deductFromTotalAmountPending($settlementOndemandPayout->getAmountToBeSettled());

        $settlementOndemand->addToTotalAmountSettled($settlementOndemandPayout->getAmountToBeSettled());

        if ($settlementOndemand->getTotalAmountPending() === 0)
        {
            if ($settlementOndemand->getTotalAmountReversed() === 0)
            {
                $settlementOndemand->setStatus(Status::PROCESSED);
            }
            else
            {
                $settlementOndemand->setStatus(Status::PARTIALLY_PROCESSED);
            }
        }
        else
        {
            $settlementOndemand->setStatus(Status::PARTIALLY_PROCESSED);
        }

        $this->repo->saveOrFail($settlementOndemand);

        if ($settlementOndemand->getSettlementOndemandTriggerId() != null && $this->mode === Mode::LIVE)
        {
            UpdateOndemandTriggerJob::dispatch($settlementOndemand->getId(),
                                               self::ONDEMAND_PAYOUT_PROCESSED_EVENT,
                                               $settlementOndemandPayout->getAmountToBeSettled())->delay(10);
        }
    }

    public function updateOndemandOnPayoutReversal($settlementOndemand, $settlementOndemandPayout)
    {
        $settlementOndemand->addToTotalAmountReversed($settlementOndemandPayout->getAmount());

        //this is in the case RazorpayX send the status as reversed after it have already sent processed status
        if (is_null($settlementOndemandPayout->getProcessedAt()) === true)
        {
            $settlementOndemand->deductFromTotalAmountPending($settlementOndemandPayout->getAmountToBeSettled());
        }
        else
        {
            $settlementOndemand->deductFromTotalAmountSettled($settlementOndemandPayout->getAmountToBeSettled());
        }

        if ($settlementOndemand->getTotalAmountReversed() === $settlementOndemand->getAmount())
        {
            $settlementOndemand->setStatus(Status::REVERSED);
        }
        else if ($settlementOndemand->getTotalAmountSettled() > 0)
        {
            $settlementOndemand->setStatus(Status::PARTIALLY_PROCESSED);
        }
        else
        {
            $settlementOndemand->setStatus(Status::INITIATED);
        }

        $settlementOndemand->deductFromTotalTax($settlementOndemandPayout->getTax());

        $settlementOndemand->deductFromTotalFees($settlementOndemandPayout->getFees());

        $this->repo->saveOrFail($settlementOndemand);

        if ($settlementOndemand->getSettlementOndemandTriggerId() != null && $this->mode === Mode::LIVE)
        {
            UpdateOndemandTriggerJob::dispatch($settlementOndemand->getId(),
                                              self::ONDEMAND_PAYOUT_REVERSED_EVENT,
                                              $settlementOndemandPayout->getAmountToBeSettled())->delay(10);
        }

        return $settlementOndemand;
    }

    public function updateOndemandPayoutOnPayoutReversal(OndemandPayout\Entity $settlementOndemandPayout, $status, $reversalReason = null)
    {
        if(isset($reversalReason)) {
            $settlementOndemandPayout->setFailureReason($reversalReason);
        }

        $settlementOndemandPayout->setStatus($status);

        $settlementOndemandPayout->setReversedAt(Carbon::now(Timezone::IST)->getTimestamp());

        $this->repo->saveOrFail($settlementOndemandPayout);

        return $settlementOndemandPayout;
    }

    public function checkMerchantFundsOnHold()
    {
        if ($this->merchant->getHoldFunds() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FUNDS_ON_HOLD);
        }
    }

    public function getOndemandPricingByFeature($merchant, $pricingFeature)
    {
        $pricingPlanId = $merchant->getPricingPlanId();

        return $this->repo->pricing
                          ->getPricingRulesByPlanIdProductFeaturePaymentMethodOrgId($pricingPlanId,
                                                                               Product::PRIMARY,
                                                                               $pricingFeature,
                                                                               Payout\Method::FUND_TRANSFER,
                                                                               $merchant->getSignedOrgId());
    }

    public function updateOndemandPricingPercentByFeature($merchant, $percentRate, $pricingFeature, $pricingPercentScaleFactor = null)
    {
        $settlementOndemandPricing = $this->getOndemandPricingByFeature($merchant, $pricingFeature);

        if(empty($settlementOndemandPricing) === false)
        {
            $pricingArray = $settlementOndemandPricing->toArray();

            $pricingArray[Pricing\Entity::PERCENT_RATE] = $percentRate;

            $pricingArray[Pricing\Entity::PERCENT_RATE_SCALE_FACTOR] = $pricingPercentScaleFactor;

            $pricingArray['idempotency_key'] ='random';

            $pricingArray[Pricing\Entity::MERCHANT_ID] = $merchant->getId();

            $pricingArray['update'] = true;

            $inputArray = [];

            array_push($inputArray, $pricingArray);

            (new Pricing\Service)->postAddBulkPricingRules($inputArray, $settlementOndemandPricing->getOrgId());
        }
    }

    public function getSettlementAmount($input, $merchant)
    {
        if (isset($input['settle_full_balance']) === true && boolval($input['settle_full_balance']) === true)
        {
            return $merchant->primaryBalance->getBalance();
        }
        else
        {
            return $input[Entity::AMOUNT];
        }
    }

    public function getSettlementAmountAndSettleableAmount($input, $amount, $featureConfig, $scheduled = false): array
    {
        [$settleableAmount, $amountLeftForToday] = (new FeatureConfig\Service)->getAllowedSettlementAmount($featureConfig);

        if ($scheduled === true and isset($input['settle_full_balance']) === true and boolval($input['settle_full_balance']) === true)
        {
            $amount = $amount > $settleableAmount ? $settleableAmount : $amount;
        }
        return [$amount, $settleableAmount];
    }

    public function addDefaultOndemandPricingByFeatureIfNotPresent($merchantId = null, $percentRate = null, $pricingFeature = PricingFeature::SETTLEMENT_ONDEMAND)
    {
        if ($merchantId !== null)
        {
            $this->merchant = $this->repo->merchant->findOrFail($merchantId);
        }

        $merchant = $this->merchant;



        $settlementOndemandPricing = $this->getOndemandPricingByFeature($merchant, $pricingFeature);

        if ($settlementOndemandPricing === null)
        {
            $this->addDefaultPricing($merchant, $percentRate, $pricingFeature);
        }
    }

    private function findPricing(Merchant\Entity $merchant, $pricingFeature): int
    {
        try
        {
            /** @var FeatureConfig\Entity $featureConfig */
            $featureConfig = (new FeatureConfig\Repository)->getConfigByMerchantId($merchant->getId());

            if ($pricingFeature === PricingFeature::SETTLEMENT_ONDEMAND and empty($featureConfig->getPricingPercent()) === false)
            {
                return $featureConfig->getPricingPercent();
            }
            else if ($pricingFeature === PricingFeature::ESAUTOMATIC_RESTRICTED and empty($featureConfig->getEsPricingPercent()) === false)
            {
                return $featureConfig->getEsPricingPercent();
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->debug(TraceCode::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_MISSING, [
                'message'    => 'pricing config not found. default pricing set',
                'error'      => $e->getMessage(),
            ]);
        }

        //if es_on_demand_restricted flag is enabled (ondemand day 1 merchant), use pricing from config if present, else use 30bps
        //if not ondemand day 1 merchant, use 25 bps
        if ($pricingFeature === PricingFeature::ESAUTOMATIC_RESTRICTED)
        {
            return FeatureConfig\Service::DEFAULT_ES_PRICING_PERCENT;
        }
        else if ($merchant->isFeatureEnabled(Feature\Constants::ES_ON_DEMAND_RESTRICTED) === true)
        {
            return 30;
        }

        return 25;
    }


    public function findOndemandRestrictedEligilbleMerchants()
    {
        $dataLakeData = $this->app['datalake.presto']->getDataFromDataLake(self::RESTRICTED_ES_DATALAKE_QUERY);

        $this->trace->info(TraceCode::RESTRICTED_ES_ELIGIBLE_MERCHANTS,[
            'data' => $dataLakeData
        ]);

        $merchantIdList = [];

        foreach ($dataLakeData as $data)
        {
            $merchantIdList[] = $data['merchant_id'];
        }

        return $merchantIdList;
    }

    public function addDefaultPricing($merchant, $percentRate, $pricingFeature = PricingFeature::SETTLEMENT_ONDEMAND, $pricingPercentScaleFactor = null)
    {
        if ($percentRate === null)
        {
            $percentRate = $this->findPricing($merchant, $pricingFeature);
        }
        if ($pricingPercentScaleFactor === null || $pricingPercentScaleFactor === '')
        {
            $pricingPercentScaleFactor = 100;
        }
        $pricingPlanId = $merchant->getPricingPlanId();

        $this->repo->transactionOnLiveAndTestAndAsv(function () use ($merchant, $pricingPlanId, $percentRate, $pricingPercentScaleFactor, $pricingFeature)
        {
            $pricingPlan = $this->repo->pricing->getPricingPlanByIdWithoutOrgId($pricingPlanId);

            // Replicates plan for this merchant if it was shared
            if ($this->repo->merchant->checkMerchantsCountWithPricingPlanIdNotEqualOne($pricingPlanId))
            {
                $newPlan = (new Pricing\Service())->replicatePlanAndAssign($merchant, $pricingPlan);

                $merchant->refresh();

                $pricingPlanId = $newPlan->getId();
            }

            $settlementOndemandPricingRule = [
                Pricing\Entity::PRODUCT => Product::PRIMARY,
                Pricing\Entity::FEATURE => $pricingFeature,
                Pricing\Entity::PAYMENT_METHOD => Payout\Method::FUND_TRANSFER,
                Pricing\Entity::PERCENT_RATE => $percentRate,
                Pricing\Entity::PERCENT_RATE_SCALE_FACTOR => $pricingPercentScaleFactor,
                Pricing\Entity::AMOUNT_RANGE_ACTIVE => 0,
                Pricing\Entity::AMOUNT_RANGE_MAX => 0,
                Pricing\Entity::AMOUNT_RANGE_MIN => 0,
                Pricing\Entity::FEE_BEARER => $merchant->getFeeBearer(),
            ];

            $updatedPlanRule = (new Pricing\Service())->addPlanRule($pricingPlanId, $settlementOndemandPricingRule, $pricingPlan->getOrgId());

            $this->trace->info(TraceCode::ADD_ONDEMAND_PRICING_IF_ABSENT, [
                'merchant_id'     => $merchant->getId(),
                'pricing_type'    => 'settlement_ondemand',
                'pricing_feature' => $pricingFeature
            ]);

        });
    }

    public function findFullESEligilbleMerchants()
    {
        $dataLakeData = $this->app['datalake.presto']->getDataFromDataLake(self::FULL_ES_DATALAKE_QUERY);

        $this->trace->info(TraceCode::FULL_ES_ELIGIBLE_MERCHANTS,[
            'data' => $dataLakeData
        ]);

        $merchantIdList = [];

        foreach ($dataLakeData as $data)
        {
            $merchantIdList[] = $data['merchant_id'];
        }

        return $merchantIdList;
    }

    public function isOndemandBlocked():bool
    {
        $ondemandXMerchantId = Config::get('applications.razorpayx_client.live.ondemand_x_merchant.id');

        $merchant = $this->repo->merchant->findOrFail($ondemandXMerchantId);

        if ($merchant->isFeatureEnabled(Feature\Constants::BLOCK_ES_ON_DEMAND) === true)
        {
            return true;
        }

        return false;
    }

    protected function getTransactionMutexresource(Base\Entity $baseEntity)
    {
        return $baseEntity->getId()."_transaction";
    }

    /**
     * @throws BadRequestException
     */
    public function handleLedgerEventsOnAcknowledgment($journal, string $transactorId, string $event, string $entityId, bool $accountAlreadyExistsForCapitalInNewLedger) {
        $ledgerEntries = $journal["ledger_entry"];
        $merchantId = (count($ledgerEntries) > 0) ? $ledgerEntries[0]["merchant_id"] : "";

        if (isset($merchantId)) {
            if ($event === \RZP\Models\LedgerOutbox\Constants::LEDGER_OUTBOXER_ONDEMAND_SETTLEMENT_PROCESSED) {
                $txn = $this->handleOndemandSettlementProcessedEventOnAcknowledgment($journal, $transactorId, $entityId, $merchantId, $accountAlreadyExistsForCapitalInNewLedger);
            } else {
                $txn = $this->handleOndemandSettlementReversedEventOnAcknowledgment($journal, $transactorId, $entityId, $merchantId);
            }
            return $txn;
        }
        else {
            $this->trace->debug(
                TraceCode::MERCHANT_ID_NOT_FOUND,
                [
                    LedgerConstants::MESSAGE => "merchant id not found for this ledger",
                    LedgerConstants::TRANSACTOR_ID => $transactorId
                ]);

            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_ID_NOT_FOUND);
        }
    }

    private function handleOndemandSettlementProcessedEventOnAcknowledgment($journal, string $transactorId, string $settlementOndemandId, string $merchantId, bool $accountAlreadyExistsForCapitalInNewLedger){
        $journalId = $journal['id'];

        $this->repo->transaction(function () use ($settlementOndemandId, $merchantId, $journalId, $accountAlreadyExistsForCapitalInNewLedger,$journal) {

            $settlementOndemand = (new Repository)->findByIdAndMerchantIdWithLock($settlementOndemandId, $merchantId);
            if($settlementOndemand->getStatus() === 'created') {

                $this->dispatchToSettlementFromJournalIfApplicable($journal,$settlementOndemand->merchant);

                $settlementOndemandPayouts = (new OndemandPayout\Repository)
                    ->fetchByOndemandIdAndMerchantId($settlementOndemand->getId(),
                        $settlementOndemand->getMerchantId())->all();

                if ($accountAlreadyExistsForCapitalInNewLedger === false) {
                    (new Service)->handleJobPushPostTransactionCreation($settlementOndemand, $settlementOndemandPayouts, $this->mode, $merchantId);
                }
            }
        });

        return $this->repo->transaction(function () use ($settlementOndemandId, $merchantId, $journalId, $accountAlreadyExistsForCapitalInNewLedger) {
            $settlementOndemand = (new Repository)->findByIdAndMerchantIdWithLock($settlementOndemandId, $merchantId);
            $resource = $this->getTransactionMutexresource($settlementOndemand);

            list($txn, $feeSplit) = $this->app['api.mutex']->acquireAndRelease(
                $resource,
                function () use ($settlementOndemand, $journalId) {
                    list($txn, $feeSplit) = (new Transaction\Processor\SettlementOndemand($settlementOndemand))
                        ->createTransaction($journalId);
                    $this->repo->saveOrFail($txn);
                });
            return $txn;
        });
    }

    private function handleOndemandSettlementReversedEventOnAcknowledgment($journal, string $transactorId, string $reversalId, string $merchantId): Transaction\Entity {
        $journalId = $journal['id'];

        return $this->repo->transaction(function () use ($reversalId, $merchantId, $journalId,$journal) {

            $reversal = $this->repo->reversal->findById($reversalId);

            $this->dispatchToSettlementFromJournalIfApplicable($journal,$reversal->merchant);

            $resource = $this->getTransactionMutexresource($reversal);

            $txn = $this->app['api.mutex']->acquireAndRelease(
                $resource,
                function () use ($reversal, $journalId)
                {
                    $txn = (new Transaction\Core)->createFromOndemandPartialReversal($reversal, $journalId);

                    $this->repo->saveOrFail($txn);

                    // update txn id in reversal entity
                    $this->repo->saveOrFail($reversal);

                    return $txn;
                });

            $settlementOndemandPayout = (new OndemandPayout\Repository)->findByIdAndMerchantIdWithLock($reversal->getEntityId(), $merchantId);

            $settlementOndemand = (new Repository)->findByIdAndMerchantIdWithLock($settlementOndemandPayout->getOndemandId(), $merchantId);

            $this->handleReversalTransactionCreated($settlementOndemand, $settlementOndemandPayout, OndemandPayout\Status::REVERSED);

            return $txn;
        });
    }

    public function handleLedgerEventsOnFailure(string $event, string $entityId)
    {
        if ($event === \RZP\Models\LedgerOutbox\Constants::LEDGER_OUTBOXER_ONDEMAND_SETTLEMENT_PROCESSED) {
            $this->handleOndemandSettlementProcessedEventOnFailure($entityId);
        } else {
            $this->handleOndemandSettlementReversedEventOnFailure($entityId);
        }
    }

    private function handleOndemandSettlementProcessedEventOnFailure(string $settlementOndemandId)
    {
        $settlementOndemand = (new Repository)->findById($settlementOndemandId);

        $settlementOndemand->setStatus(Status::FAILED);

        $this->repo->saveOrFail($settlementOndemand);

    }

    private function handleOndemandSettlementReversedEventOnFailure(string $reversalId)
    {
        $reversal = $this->repo->reversal->findById($reversalId);

        $settlementOndemandPayout = (new OndemandPayout\Repository)->findByIdAndMerchantId($reversal->getEntityId(), $reversal->getMerchantId());

        $settlementOndemandPayout->setStatus(OndemandPayout\Status::REVERSAL_FAILED);

        $this->repo->saveOrFail($settlementOndemandPayout);
    }

    private function dispatchToSettlementFromJournalIfApplicable($journal,$merchant)
    {
        $transactorEvent = $journal[LedgerConstants::TRANSACTOR_EVENT];

        $isExpEnabled = $this->checkIfEarlyDispatchOfTxnForSettlementsExperimentIsEnabledForODS($merchant);

        $bucketCore = new Bucket\Core;

        if ($isExpEnabled === true)
        {
            if (($transactorEvent === LedgerConstants::LEDGER_ONDEMAND_SETTLEMENT_PROCESSED))
            {
                $virtualPaymentTransaction = $this->transformJournalResponseToTransactionEntity($journal);

                $status = $bucketCore->shouldProcessViaNewService($virtualPaymentTransaction->getMerchantId());

                if ($status === true)
                {
                    $bucketCore->publishForSettlement($virtualPaymentTransaction);
                }
            }else if(($transactorEvent === LedgerConstants::LEDGER_ONDEMAND_SETTLEMENT_REVERSED)) {

                $virtualPaymentTransaction = $this->transformJournalResponseToTransactionEntityForReversal($journal);

                $status = $bucketCore->shouldProcessViaNewService($virtualPaymentTransaction->getMerchantId());

                if ($status === true)
                {
                    $bucketCore->publishForSettlement($virtualPaymentTransaction);
                }
            }
        }
    }

    public function checkIfEarlyDispatchOfTxnForSettlementsExperimentIsEnabledForODS($merchant): bool
    {
        $variant = App::getFacadeRoot()->razorx->getTreatment(
            $merchant->getId(),
            Merchant\RazorxTreatment::EARLY_DISPATCH_OF_TXNS_FOR_SETTLEMENTS_USING_JOURNAL_ODS,
            $this->mode ?? Mode::LIVE
        );

        $isExperimentEnabled = ($variant === 'on');

        $this->trace->info(TraceCode::EARLY_DISPATCH_OF_TXNS_FOR_SETTLEMENTS_EXP_CHECK_FOR_ODS,
            [
                'merchant'               => $merchant->getId(),
                'isExperimentEnabled'    => $isExperimentEnabled,
            ]);

        return $isExperimentEnabled;
    }

    public function transformJournalResponseToTransactionEntity($journalResponse)
    {

        $reverseShadowCapital = new ReverseShadowCapitalCore();

        $baseTransactionEntity = $reverseShadowCapital->transformJournalResponseToTransactionEntityBaseForODSForProcessed($journalResponse);

        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        $baseTransactionEntity->setSettledAt($settledAt);

        return $baseTransactionEntity;
    }

    public function transformJournalResponseToTransactionEntityForReversal($journalResponse)
    {

        $reverseShadowCapital = new ReverseShadowCapitalCore();

        $baseTransactionEntity = $reverseShadowCapital->transformJournalResponseToTransactionEntityBaseForReversal($journalResponse);

        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        $baseTransactionEntity->setSettledAt($settledAt);

        return $baseTransactionEntity;
    }

}
