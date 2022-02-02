<?php

namespace RZP\Models\Settlement\Ondemand;

use Config;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Error\Error;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Adjustment;
use RZP\Models\Transaction;
use RZP\Exception\BadRequestException;
use RZP\Models\Settlement\OndemandPayout;
use RZP\Jobs\SettlementOndemand\AddFullES;
use RZP\Models\Settlement\Ondemand\FeatureConfig;
use RZP\Jobs\SettlementOndemand\MockPayoutOndemandWebhook;
use RZP\Jobs\SettlementOndemand\AddOndemandPricingIfAbsent;
use RZP\Jobs\SettlementOndemand\AddOndemandRestrictedFeature;
use RZP\Jobs\SettlementOndemand\PartialScheduledSettlementJob;
use RZP\Jobs\SettlementOndemand\CreateSettlementOndemandPayoutJobs;
use RZP\Jobs\SettlementOndemand\CreateSettlementOndemandBulkTransfer;

class Service extends Base\Service
{
    protected $settlementOndemandPayout;

    public function __construct()
    {
        parent::__construct();

        $this->settlementOndemandPayout = new OndemandPayout\Service();

        $this->user = $this->app['basicauth']->getUser();
    }

    public function calculateFees(array $input): array
    {
        (new Validator)->validateInput(Validator::SETTLEMENT_ONDEMAND_FEES_INPUT, $input);

        $this->core()->addDefaultOndemandPricingByFeatureIfNotPresent();

        $amount = $this->core()->getSettlementAmount($input, $this->merchant);

        $input[Entity::AMOUNT] = $amount;

        $finalFeesSplit= $this->core()->getFeesSplit($input, $this->merchant, $this->user);

        return $finalFeesSplit;
    }

    public function isMerchantWithXSettlementAccount($merchantId)
    {
        return $this->core()->isMerchantWithXSettlementAccount($merchantId);
    }

    /**
     * @throws Exception\BadRequestValidationFailureException
     * @throws BadRequestException
     */
    public function create(array $input, $merchantId = null, $scheduled = false, $mode = null): array
    {
        if($scheduled == true && $merchantId != null)
        {
            $this->app['basicauth']->setModeAndDbConnection($mode);
            $merchant = $this->repo->merchant->findOrFail($merchantId);
            $this->app['basicauth']->setMerchant($merchant);

            $this->mode = $mode;
            $this->merchant = $merchant;
        }

        return $this->app['api.mutex']->acquireAndRelease(
        'settlement_ondemand'.$this->merchant->getId(),
        function() use ($input, $scheduled)
        {
            return $this->repo->transaction(function () use ($input, $scheduled)
            {
                $this->trace->info(TraceCode::SETTLEMENT_ONDEMAND_CREATE, [
                    'merchant_id' => $this->merchant->getId(),
                    'user_id'     => isset($this->user) ? ($this->user->getId()) : null,
                    'input'       => $input,
                ]);

                (new Validator)->validateInput(Validator::SETTLEMENT_ONDEMAND_INPUT, $input);

                $this->validateIfOndemandMerchant();

                $this->validateIfDisabledByCollections();

                $amount = $this->core()->getSettlementAmount($input, $this->merchant);

                //If es_on_demand_restricted feature is enabled for the merchant
                //additional checks will be done based on config values
                if($this->merchant->isFeatureEnabled(Feature\Constants::ES_ON_DEMAND_RESTRICTED) === true)
                {
                    $featureConfig = (new FeatureConfig\Core)->getFeatureConfigByMerchantId($this->merchant->getId());

                    [$amount, $settleableAmount] = $this->core()->getSettlementAmountAndSettleableAmount($input, $amount, $featureConfig, $scheduled);

                    $this->configCheck($featureConfig, $amount, $scheduled, $settleableAmount);
                }

                $input[Entity::AMOUNT] = $amount;

                [$settlementOndemand, $settlementOndemandPayouts, $txn] = $this->core()->createSettlementOndemand(
                                                                                            $input,
                                                                                            $this->merchant,
                                                                                            $this->user,
                                                                                            $scheduled);

                if($this->mode === 'live')
                {
                    $ondemandXMerchantId = Config::get('applications.razorpayx_client.live.ondemand_x_merchant.id');

                    $adjInput = [
                        Adjustment\Entity::MERCHANT_ID => $ondemandXMerchantId,
                        Adjustment\Entity::AMOUNT      => $settlementOndemand->getAmountToBeSettled(),
                        Adjustment\Entity::DESCRIPTION => 'adding funds to Ondemand-X merchant for OndemandID - ' .
                            $settlementOndemand->getId(),
                        Adjustment\Entity::CURRENCY    => 'INR',
                        Adjustment\Entity::TYPE        => Merchant\Balance\Type::BANKING,
                    ];

                    (new Adjustment\Service)->addAdjustment($adjInput);
                }

                if ($this->isMerchantWithXSettlementAccount($this->merchant->getId()) === true)
                {
                    $merchantAdjInput = [
                        Adjustment\Entity::MERCHANT_ID  => $this->merchant->getId(),
                        Adjustment\Entity::AMOUNT       => $settlementOndemand->getAmountToBeSettled(),
                        Adjustment\Entity::DESCRIPTION  => 'ondemand settlement - ' .
                            $settlementOndemand->getPublicId(),
                        Adjustment\Entity::CURRENCY     => 'INR',
                        Adjustment\Entity::TYPE         => Merchant\Balance\Type::BANKING,
                    ];

                    $settlementOndemand->setStatus(Status::INITIATED);

                    $adj = (new Adjustment\Service)->addAdjustment($merchantAdjInput);

                    (new OndemandPayout\Core)->setAdjustmentId($settlementOndemandPayouts, $adj['id']);

                    foreach ($settlementOndemandPayouts as $settlementOndemandPayout)
                    {
                        $this->core()->handleOndemandPayoutProcessed($settlementOndemand, $settlementOndemandPayout);
                    }


                    if ((new OndemandPayout\Core)->isOutsideBankingHoursWithBufferTime())
                    {
                        (new Transfer\Service)->processXSettlementTransfer($settlementOndemand);
                    }
                    else
                    {
                        (new Bulk\Core)->createSettlementOndemandBulk($settlementOndemand, $settlementOndemand->getAmountToBeSettled());
                    }
                }
                else
                {
                    CreateSettlementOndemandPayoutJobs::dispatch($this->mode, $settlementOndemand->getId(),
                        $settlementOndemand->getMerchantId())->delay(10);

                    $settlementOndemand->setStatus(Status::INITIATED);

                    $this->repo->saveOrFail($settlementOndemand);

                    $mockRazorpayX = Config::get('applications.razorpayx_client.' . $this->mode . '.mock_webhook');

                    if ($mockRazorpayX === true)
                    {
                        foreach ($settlementOndemandPayouts as $settlementOndemandPayout)
                        {
                            MockPayoutOndemandWebhook::dispatch($this->mode, $settlementOndemandPayout)->delay(20);
                        }
                    }
                }



                if (isset($input['expand']) === true && boolval($input['expand']) === true)
                {
                    return $this->getResponse($settlementOndemand, $settlementOndemandPayouts);
                }
                else
                {
                    return $this->getResponse($settlementOndemand);
                }

            });

        });

    }

    public function createSettlementOndemandReversal($settlementOndemandId, $merchantId, $reversalReason)
    {
        $this->repo->transaction(function() use ($settlementOndemandId, $merchantId, $reversalReason)
        {
            $settlementOndemand = (new Repository)->findByIdAndMerchantIdWithLock($settlementOndemandId, $merchantId);

            $settlementOndemandPayoutIds = (new OndemandPayout\Repository)
                                            ->fetchIdsByOndemandIdAndMerchantId($settlementOndemand->getId(),
                                                                                $settlementOndemand->getMerchantId());

            foreach($settlementOndemandPayoutIds as $settlementOndemandPayoutId)
            {
                $this->createPartialReversal($settlementOndemandPayoutId, $merchantId, $reversalReason);

            }
        });
    }

    public function createPartialReversal($settlementOndemandPayoutId, $merchantId, $reversalReason)
    {
        $settlementOndemandPayout = (new OndemandPayout\Repository)->findByIdAndMerchantIdWithLock
                                                ($settlementOndemandPayoutId, $merchantId);

        $this->core()->createPartialReversal($settlementOndemandPayout, $reversalReason);
    }

    public function validateIfOndemandMerchant()
    {
        if ($this->merchant->isFeatureEnabled(Feature\Constants::ES_ON_DEMAND) === false)
        {
            throw new Exception\BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_NON_ES_ON_DEMAND_MERCHANTS_NOT_ALLOWED);
        }
    }

    public function validateIfDisabledByCollections()
    {
        if ($this->merchant->isFeatureEnabled(Feature\Constants::DISABLE_ONDEMAND_FOR_LOC)=== true ||
            $this->merchant->isFeatureEnabled(Feature\Constants::DISABLE_ONDEMAND_FOR_LOAN) === true ||
            $this->merchant->isFeatureEnabled(Feature\Constants::DISABLE_ONDEMAND_FOR_CARD) === true )
            {
                throw new Exception\BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_ES_ON_DEMAND_DISABLED_BY_COLLECTIONS);
            }
    }

    public function fetch(string $id, array $input): array
    {
        $settlementOndemand = (new Repository)->findByPublicIdAndMerchant($id, $this->merchant);

        if (isset($input['expand']) === true && boolval($input['expand']) === true)
        {
            $settlementOndemandPayouts = (new OndemandPayout\Repository)
                                         ->fetchByOndemandIdAndMerchantId($settlementOndemand->getId(),
                                                                        $settlementOndemand->getMerchantId())->all();

            return $this->getResponse($settlementOndemand, $settlementOndemandPayouts);
        }
        else
        {
            return $this->getResponse($settlementOndemand);
        }
    }

    public function fetchMultiple($input)
    {
        (new Validator)->validateInput(Validator::FETCH_BY_TIMESTAMP_INPUT, $input);

        if (!isset($input['to']) === true)
        {
            $input['to'] = Carbon::now()->getTimestamp();
        }

        if (empty($input['expand']) === false)
        {
            if (($key = array_search('ondemand_payouts', $input['expand'], true)) !== false)
            {
                unset($input['expand'][$key]);
                $input['expand'][] = 'settlement_ondemand_payouts';
            }
        }

        /** @var Base\PublicCollection $settlementOndemandList */
        $settlementOndemandList = (new Repository)->fetch($input, $this->merchant->getId())->toArrayPublic();

        if (empty($settlementOndemandList['items']) === false)
        {
            foreach ($settlementOndemandList['items'] as $key => $item)
            {
                if (isset($item['settlement_ondemand_payouts']) === true)
                {
                    $settlementOndemandList['items'][$key]['ondemand_payouts'] = $settlementOndemandList['items'][$key]['settlement_ondemand_payouts'];
                    unset($settlementOndemandList['items'][$key]['settlement_ondemand_payouts']);
                }
            }
        }

        return $settlementOndemandList;
    }

    public function getResponse($settlementOndemand, $settlementOndemandPayouts = null)
    {
        $settlementOndemandArray = $settlementOndemand->toArrayPublic();

        if (isset($settlementOndemandArray['settlement_ondemand_payouts']) === true)
        {
            unset($settlementOndemandArray['settlement_ondemand_payouts']);
        }

        if (isset($settlementOndemandPayouts) === true)
        {
            return $settlementOndemandArray + [
                'ondemand_payouts'   => $settlementOndemand->settlementOndemandPayouts->toArrayPublic(),
            ];
        }
        else
        {
            return $settlementOndemandArray;
        }
    }

    public function addOndemandPricingIfAbscent()
    {
        AddOndemandPricingIfAbsent::dispatch($this->mode);

        $response = [
            'response'  => 'AddOndemandPricingIfAbsent job dispatched',
        ];

        return $response;
    }

    public function addDefaultOndemandPricingIfNotPresent($merchantId)
    {
        $this->core()->addDefaultOndemandPricingByFeatureIfNotPresent($merchantId);
    }

    /**
     * @throws BadRequestException
     */
    public function configCheck($featureConfig, $amount, $scheduled, $settleableAmount)
    {
        if ($scheduled === false)
        {
            $attemptsLeftToday = (new FeatureConfig\Service)->getAttemptsLeft($featureConfig);

            if ($attemptsLeftToday === 0)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_ONDEMAND_SETTLEMENT_LIMIT_EXCEEDED,
                    null,
                    [
                        'merchantId'                         => $this->merchant->getId(),
                        'settlement_ondemand_feature_config' => $featureConfig,
                        'attempts_left_today'                => $attemptsLeftToday
                    ],
                    'No more attempts left for today');
            }
        }
        else
        {
            Validator::validateOndemandSettlementAmount($amount);
        }

        //Checks if the requested amount is greater than the maximum allowed amount that can be settled
        if($amount > $settleableAmount)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ONDEMAND_SETTLEMENT_AMOUNT_MAX_LIMIT_EXCEEDED,
                null,
                [
                    'merchantId'                         => $this->merchant->getId(),
                    'settlement_ondemand_feature_config' => $featureConfig,
                    'settlable_amount'                   => $settleableAmount,

                ],
            'Maximum amount that can be settled(in paisa) is '.$settleableAmount);
        }
    }

    public function enqueueJob(string $id)
    {
        $settlementOndemand = (new Repository)->findOrFail($id);

        CreateSettlementOndemandPayoutJobs::dispatch($this->mode, $settlementOndemand->getId(),
            $settlementOndemand->getMerchantId());

        $settlementOndemand->setStatus(Status::INITIATED);

        $this->repo->saveOrFail($settlementOndemand);

        return [];
    }

    public function processPartialSettlementScheduled(): array
    {
        PartialScheduledSettlementJob::dispatch($this->mode);

        return [
            'response' => 'PartialScheduledSettlementJob job dispatched',
        ];
    }

    public function addOndemandRestrictedFeature()
    {
        AddOndemandRestrictedFeature::dispatch($this->mode);

        $response = [
            'response'  => 'AddOndemandRestrictedFeature job dispatched',
        ];

        return $response;
    }

    public function createOrUpdatePricingRule($merchant, $pricingPercent, $pricingFeature)
    {
        $pricing = $this->core()->getOndemandPricingByFeature($merchant, $pricingFeature);

        if($pricing === null)
        {
            try
            {
                $this->core()->addDefaultPricing($merchant, $pricingPercent, $pricingFeature);
            }
            catch(\Throwable $e)
            {
                throw new Exception\ServerErrorException(
                    'Failed to create pricing rule',
                    ErrorCode::SERVER_ERROR_PRICING_RULE_CREATION_FAILURE,
                    null,
                    $e
                );
            }
        }
        else
        {
            try
            {
                $this->core()->updateOndemandPricingPercentByFeature($merchant, $pricingPercent, $pricingFeature);
            }
            catch(\Throwable $e)
            {
                throw new Exception\ServerErrorException(
                    'Failed to update pricing rule',
                    ErrorCode::SERVER_ERROR_PRICING_RULE_UPDATION_FAILURE,
                    null,
                    $e
                );
            }
        }
    }

    public function enableFullESFromRestricted(): array
    {
        AddFullES::dispatch($this->mode);

        return [
            'response' => 'AddFullES job dispatched',
        ];
    }
}
