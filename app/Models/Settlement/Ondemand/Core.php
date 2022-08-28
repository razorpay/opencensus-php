<?php

namespace RZP\Models\Settlement\Ondemand;

use App;
use Config;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Payout;
use RZP\Models\Pricing;
use RZP\Models\Feature;
use RZP\Models\Reversal;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
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
use RZP\Models\Settlement\Ondemand\FeatureConfig;
use RZP\Models\Pricing\Feature as PricingFeature;
use RZP\Jobs\SettlementOndemand\UpdateOndemandTriggerJob;

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
            [$settlementOndemand, $settlementOndemandPayouts] = $this->createSettlementOndemand($input, $merchant, $user);

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

                (new Reversal\Core)->partialReversalForSettlementOndemand($settlementOndemand, $settlementOndemandPayout);

                $settlementOndemandPayout = $this->updateOndemandPayoutOnPayoutReversal($settlementOndemandPayout, $reversalReason);

                $this->updateOndemandOnPayoutReversal($settlementOndemand, $settlementOndemandPayout);
            });
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

    public function updateOndemandPayoutOnPayoutReversal($settlementOndemandPayout, $reversalReason)
    {
        $settlementOndemandPayout->setFailureReason($reversalReason);

        $settlementOndemandPayout->setStatus(Status::REVERSED);

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

    public function updateOndemandPricingPercentByFeature($merchant, $percentRate, $pricingFeature)
    {
        $settlementOndemandPricing = $this->getOndemandPricingByFeature($merchant, $pricingFeature);

        if(empty($settlementOndemandPricing) === false)
        {
            $pricingArray = $settlementOndemandPricing->toArray();

            $pricingArray[Pricing\Entity::PERCENT_RATE] = $percentRate;

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

    public function addDefaultPricing($merchant, $percentRate, $pricingFeature = PricingFeature::SETTLEMENT_ONDEMAND)
    {
        if ($percentRate === null)
        {
            $percentRate = $this->findPricing($merchant, $pricingFeature);
        }
        $pricingPlanId = $merchant->getPricingPlanId();

        $this->repo->transactionOnLiveAndTest(function () use ($merchant, $pricingPlanId, $percentRate, $pricingFeature)
        {
            $pricingPlan = $this->repo->pricing->getPricingPlanByIdWithoutOrgId($pricingPlanId);

            // Replicates plan for this merchant if it was shared
            if ($this->repo->merchant->fetchMerchantsCountWithPricingPlanId($pricingPlanId) !== 1)
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

}
