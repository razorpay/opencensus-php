<?php

namespace RZP\Models\Settlement\Ondemand\FeatureConfig;

use Mail;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\Error;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Mail\Merchant\FullES;
use RZP\Mail\Merchant\PartialES;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\Ondemand;
use RZP\Models\Pricing\Feature as PricingFeature;

class Service extends Base\Service
{
    const DEFAULT_ES_PRICING_PERCENT = 12;

    public function enableFeature(array $inputs)
    {
        $result = new Base\PublicCollection;

        foreach ($inputs as $input)
        {
            $this->app['api.mutex']->acquireAndReleaseStrict(
                'settlement_ondemand_feature_config'.$input[Entity::MERCHANT_ID],
                   function() use ($input, $result) {

                    $this->trace->info(TraceCode::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_CREATE, [
                        'input' => $input,
                    ]);

                    $idempotencyKey = $input[\RZP\Models\Batch\Constants::IDEMPOTENCY_KEY] ?? '';

                    unset($input[\RZP\Models\Batch\Constants::IDEMPOTENCY_KEY]);

                    try
                    {
                        (new Validator)->validateInput(Validator::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_INPUT, $input);

                        $this->repo->transaction(function () use ($input)
                        {
                            $merchant = $this->repo->merchant->find($input[Entity::MERCHANT_ID]);

                            /* creates ondemand pricing and es automatic restricted rule if not present,
                               else updates the present pricing rule with given pricing_percent */

                            (new Ondemand\Service)->createOrUpdatePricingRule($merchant, $input[Entity::PRICING_PERCENT],
                                                                        PricingFeature::SETTLEMENT_ONDEMAND);

                            $input[Entity::ES_PRICING_PERCENT] = (isset($input[Entity::ES_PRICING_PERCENT]) === true) ?
                                                                 $input[Entity::ES_PRICING_PERCENT]: self::DEFAULT_ES_PRICING_PERCENT;


                            (new Ondemand\Service)->createOrUpdatePricingRule($merchant, $input[Entity::ES_PRICING_PERCENT],
                                                                PricingFeature::ESAUTOMATIC_RESTRICTED);

                            $flagUpdate = $this->enableFeatureFlag($merchant, Feature\Constants::ES_ON_DEMAND);

                            if($input[Entity::FULL_ACCESS] === 'yes')
                            {
                                $flagUpdate = $this->disableFeatureFlag($merchant, Feature\Constants::ES_ON_DEMAND_RESTRICTED) || $flagUpdate;

                                if ($merchant->isFeatureEnabled(Feature\Constants::ES_AUTOMATIC_RESTRICTED) === true)
                                {
                                    $this->disableFeatureFlag($merchant, Feature\Constants::ES_AUTOMATIC_RESTRICTED);

                                    $this->enableScheduledEs($input[Entity::MERCHANT_ID]);
                                }
                            }
                            else if ($input[Entity::FULL_ACCESS] === 'no')
                            {
                                 $flagUpdate = $this->enableFeatureFlag($merchant, Feature\Constants::ES_ON_DEMAND_RESTRICTED) || $flagUpdate ;
                            }

                            $this->createOrUpdateFeatureConfig($input);

                            //Mails are sent only if there is any featureFlag updation
                            if ($flagUpdate === true)
                            {
                                try
                                {
                                    $this->sendMailsPostEnableOndemand($input[Entity::FULL_ACCESS], $merchant);
                                }
                                catch (\Throwable $exception)
                                {
                                    $this->trace->traceException(
                                        $exception,
                                        Trace::ERROR,
                                        TraceCode::ES_ONDEMAND_ENABLED_MERCHANT_NOT_NOTIFIED,
                                        [
                                            'merchant_id' => $input[Entity::MERCHANT_ID],
                                            'full_access' => $input[Entity::FULL_ACCESS]
                                        ]);
                                }
                            }

                        });

                        $result->push([
                            'idempotency_key'   => $idempotencyKey,
                            'success'           => true,
                        ]);
                    }
                    catch (\Throwable $e)
                    {
                        $result->push([
                            'idempotency_key'   => $idempotencyKey,
                            'success'           => false,
                            'error'             => [
                                Error::DESCRIPTION       => $e->getMessage(),
                                Error::PUBLIC_ERROR_CODE => $e->getCode(),
                            ]
                        ]);

                        $this->trace->traceException(
                            $e,
                            Trace::ERROR,
                            TraceCode::SETTLEMENT_ONDEMAND_FEATURE_CREATION_FAILURE,
                            [
                                'merchant_id' => $input['merchant_id']
                            ]);
                    }
                });

        }

        $this->trace->info(
            TraceCode::SETTLEMENT_ONDEMAND_FEATURE_CREATION_RESPONSE,
            [
                'response' => $result->toArrayWithItems(),
            ]);

        return $result->toArrayWithItems();
    }

    /**
     * @param $merchant
     * @param $feature
     * @return bool
     * @throws Exception\BadRequestException
     * @throws Exception\ServerErrorException
     */
    public function enableFeatureFlag($merchant, $feature): bool
    {
        if ($merchant->isFeatureEnabled($feature) === false)
        {
            $featureInput = [
                Feature\Entity::ENTITY_ID   => $merchant->getId(),
                Feature\Entity::ENTITY_TYPE => Feature\Constants::MERCHANT,
                Feature\Entity::NAME        => $feature,
            ];

            (new Feature\Core)->create($featureInput, true);

            return true;
        }

        return false;
    }

    /**
     * @param $merchant
     * @param $feature
     * @return bool
     */
    public function disableFeatureFlag($merchant, $feature): bool
    {
        if ($merchant->isFeatureEnabled($feature) === true)
        {
            $feature = (new Feature\Repository)
                                    ->findByEntityTypeEntityIdAndNameOrFail(Feature\Constants::MERCHANT,
                                                                            $merchant->getId(),
                                                                            $feature);

            (new Feature\Core)->delete($feature, true);

            return true;
        }

        return false;
    }

    public function createOrUpdateFeatureConfig($input)
    {
        try
        {
            $featureConfig = $this->core()->getFeatureConfigByMerchantId($input["merchant_id"]);

            $this->core()->updateFeatureConfig($featureConfig, $input);
        }
        catch (\Exception $e)
        {
            $this->repo->transactionOnLiveAndTest(function() use ($input) {

                $this->core()->createFeatureConfig($input);

            });
        }
    }

    public function validateWithFeatureConfig()
    {
        if ($this->merchant->isFeatureEnabled(Feature\Constants::ES_ON_DEMAND_RESTRICTED) === true)
        {
            $featureConfig = $this->core()->getFeatureConfigByMerchantId($this->merchant->getId());

            [$allowedSettlementAmount, $amountLeftForToday] = $this->getAllowedSettlementAmount($featureConfig);

            $attemptsLeft = ($amountLeftForToday > 0) ? $this->getAttemptsLeft($featureConfig) : 0;

            return [
                Entity::SETTLABLE_AMOUNT        => $allowedSettlementAmount,
                Entity::ATTEMPTS_LEFT           => $attemptsLeft,
                Entity::MAX_AMOUNT_LIMIT        => $featureConfig->getMaxAmountLimit(),
                Entity::SETTLEMENTS_COUNT_LIMIT => $featureConfig->getSettlementsCountLimit()
            ];
        }

        return null;
    }

    //merchant has a daily limit of settlement amount and limit per each settlement according to his/her live balance
    //The merchant will be allowed to settle only the minimum of live balance limit and daily amount left for settlement
    //which is fetched in this function
    public function getAllowedSettlementAmount($featureConfig)
    {
        $amountSettledToday = (new Ondemand\Repository)->findAmountSettledTodayByMerchantId($this->merchant->getId());

        $maxAmountLimitPerDay = $featureConfig->getMaxAmountLimit();

        $amountLeftForToday = ($maxAmountLimitPerDay - $amountSettledToday) > 0 ? ($maxAmountLimitPerDay - $amountSettledToday) : 0;

        $amountLimitPerSettlement = PHP_INT_MAX;

        if($amountLeftForToday > 0)
        {
            $amountLimitPerSettlement = ceil(($this->merchant->primaryBalance->getBalance() * $featureConfig->getPercentageOfBalanceLimit())/100);
        }

        $settlableAmount = min($amountLeftForToday, $amountLimitPerSettlement);

        return [$settlableAmount, $amountLeftForToday];
    }

    public function getAttemptsLeft($featureConfig)
    {
        $settlementsCountToday = (new Ondemand\Repository)->findSettlementsCountTodayByMerchantId($this->merchant->getId());

        $settlementsCountLimit = $featureConfig->getSettlementsCountLimit();

        $attemptsLeft = ($settlementsCountLimit - $settlementsCountToday) > 0 ? ($settlementsCountLimit - $settlementsCountToday) : 0;

        return $attemptsLeft;
    }

    /**
     * @throws Exception\LogicException
     * @throws Exception\BadRequestException
     */
    public function enableScheduledEs($merchantId): void
    {
        $merchant = $this->repo->merchant->findOrFail($merchantId);
        $this->app['basicauth']->setMerchant($merchant);
        (new \RZP\Models\Merchant\Service)->enableScheduledEs(true, false);
    }

    public function enableFullESFromRestricted($merchantId)
    {
        $merchant = $this->repo->merchant->find($merchantId);

        $flagUpdate = false;

        $flagUpdate = $flagUpdate || $this->enableFeatureFlag($merchant, Feature\Constants::ES_ON_DEMAND);

        $flagUpdate = $flagUpdate || $this->disableFeatureFlag($merchant, Feature\Constants::ES_ON_DEMAND_RESTRICTED);

        if ($merchant->isFeatureEnabled(Feature\Constants::ES_AUTOMATIC_RESTRICTED) === true)
        {
            $this->disableFeatureFlag($merchant, Feature\Constants::ES_AUTOMATIC_RESTRICTED);

            $this->enableScheduledEs($merchantId);
        }

        if ($flagUpdate === true)
        {
            try
            {
                $this->sendMailsPostEnableOndemand('yes', $merchant);
            }
            catch (\Throwable $exception)
            {
                $this->trace->traceException(
                    $exception,
                    Trace::ERROR,
                    TraceCode::FEATURE_ENABLE_PARTIAL_ES_MAIL_FAILED,
                    [
                        'merchant_id' => 'random'
                    ]);
            }
        }
    }

    public function sendMailsPostEnableOndemand($fullAccess, $merchant)
    {
        $data['contact_name']  = $merchant->getName();
        $data['contact_email'] = $merchant->getEmail();;

        $mail = null;

        if ($fullAccess === 'yes')
        {
            $mail = new FullES($data);
        }
        else
        {
            $mail = new PartialES($data);
        }

        Mail::queue($mail);

        $this->trace->info(
            TraceCode::ES_ONDEMAND_ENABLED_MERCHANT_NOTIFIED,
            [
                'merchant_id' => $merchant->getId(),
                'full_access' => $fullAccess
            ]);
    }
}
