<?php

namespace RZP\Models\Settlement\Bucket;

use Cache;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\Settlement\Holidays;

class Preference extends Base\Core
{
    /**
     * Will give the timestamp for next bucket for the given timestamp
     * if the hour anchor is given, then it'll make sure that this is added in the hour bucket
     *
     * @param     $timestamp
     * @param int $hour
     * @return int
     */
    public static function getNextBucket($timestamp, int $hour = 0): int
    {
        $timestamp = Carbon::createFromTimestamp($timestamp, Timezone::IST);

        // If no hour anchor is given then used the calculated timestamp
        if ($hour === 0)
        {
            return $timestamp->getTimestamp();
        }

        // Add hour anchor (next eligible hour)
        // if anchor is present then it should go only on working days
        if ($timestamp->hour > $hour)
        {
            $timestamp = Holidays::getNextWorkingDay($timestamp);
        }

        $timestamp = $timestamp->setDateTime($timestamp->year, $timestamp->month, $timestamp->day, $hour, 0);

        return $timestamp->getTimestamp();
    }

    public static function getCeilTimestamp(Carbon $timestamp): Carbon
    {
        $hourOffset = 1;

        // if the schedule is already anchored to some hour then don't add offset
        if (($timestamp->minute === 0))
        {
            $hourOffset = 0;
        }

        return  $timestamp->subSeconds($timestamp->second)
                          ->subMinutes($timestamp->minute)
                          ->addHours($hourOffset);
    }

    /**
     * check if settlement should be skipped for this merchant
     *
     * @param string $merchantId
     * @return bool
     */
    public function skipMerchantSettlement(string $merchantId)
    {
        if (in_array($merchantId, Merchant\Preferences::NO_SETTLEMENT_MIDS, true) === true)
        {
            return true;
        }

        // MIDs that have the block_settlements/daily_settlement feature enabled
        $isFeatureEnabled = $this->repo
                                 ->feature
                                 ->findMerchantWithFeatures(
                                     $merchantId,
                                     [
                                         Feature\Constants::BLOCK_SETTLEMENTS,
                                         Feature\Constants::DAILY_SETTLEMENT
                                     ])
                                 ->pluck(Feature\Entity::NAME)
                                 ->toArray();

        return (empty($isFeatureEnabled) === false);
    }

    /**
     * get suitable bucket timestamp for early settlement.
     * if early settlement is not enabled for the merchant then it'll return false
     * so rest of the precess will execute as expected
     *
     * @param string $merchantId
     * @param        $settlementTime
     * @return array
     */
    public function getEarlySettlementBucketIfApplicable(string $merchantId, $settlementTime): array
    {
        // fetch ES feature list for the given merchant
        $featureList = $this->getSettlementFeatureList($merchantId);

        // If ES feature is enabled to the merchant then,
        // add the merchant to ES bucket based on the settlement time given
        if (empty($featureList) === true)
        {
            return [false, 0];
        }

        // this is a mandatory flag to enable early settlement
        if (in_array(Feature\Constants::ES_AUTOMATIC, $featureList, true) === false)
        {
            return [false, 0];
        }

        $timestamp = $this->getEarlySettlementBucket($merchantId, $featureList, $settlementTime);

        return [true, $timestamp];
    }

    /**
     * adds merchant to settlement bucket if merchant has special preferences
     *
     * @param string          $merchantId
     * @param                 $settlementTime
     * @return array
     */
    public function getMerchantSpecificBucket(string $merchantId, $settlementTime): array
    {
        $merchant = $this->repo->merchant->find($merchantId);

        $settlementTime = Carbon::createFromTimestamp($settlementTime, Timezone::IST);

        $settlementTime = $this->getCeilTimestamp($settlementTime);

        $data = $this->getDSPMerchantBucket($merchant, $settlementTime);

        if ($data[0] === true)
        {
            return $data;
        }

        $data = $this->getMutualFundBucket($merchant, $settlementTime);

        if ($data[0] === true)
        {
            return $data;
        }

        $data = $this->getKarvySettlementBucket($merchant, $settlementTime);

        if ($data[0] === true)
        {
            return $data;
        }

        $data = $this->getScripBoxMerchantBucket($merchant, $settlementTime);

        if ($data[0] === true)
        {
            return $data;
        }

        $data = $this->getEtMoneyMerchantBucket($merchant, $settlementTime);

        if ($data[0] === true)
        {
            return $data;
        }

        return [false, $settlementTime->getTimestamp()];
    }

    /**
     * fetches settlement related feature on given merchant ID
     * It'll also cache the response for 300 sec for frequent uses
     *
     * @param string $merchantId
     * @return array
     */
    protected function getSettlementFeatureList(string $merchantId)
    {
        // fetch the data and cache it for future use
        $featureList = $this->repo
                            ->feature
                            ->findMerchantWithFeatures(
                                $merchantId,
                                [
                                    Feature\Constants::ES_AUTOMATIC,
                                    Feature\Constants::ES_AUTOMATIC_THREE_PM,
                                ])
                            ->pluck(Feature\Entity::NAME)
                            ->toArray();;

        return $featureList;
    }

    /**
     * If merchant has early settlement enabled them find the best bucket
     * and add merchant to that bucket
     *
     * @param string $merchantId
     * @param array  $featureList
     * @param        $settlementTime
     * @return int
     */
    protected function getEarlySettlementBucket(string $merchantId, array $featureList, $settlementTime): int
    {
        $settlementTime = Carbon::createFromTimestamp($settlementTime, Timezone::IST);

        $settlementTime = $this->getCeilTimestamp($settlementTime);

        $settlementHour = $settlementTime->hour;

        // 9AM or 5PM bucket (which ever is nearest)
        $hour = (($settlementHour <= Constants::NINE_AM) or ($settlementHour > Constants::FIVE_PM)) ?
            Constants::NINE_AM : Constants::FIVE_PM;

        // Nearest 3PM bucket
        if (in_array(Feature\Constants::ES_AUTOMATIC_THREE_PM, $featureList, true) === true)
        {
            if ($hour === Constants::FIVE_PM)
            {
                $hour = (($settlementHour <= Constants::THREE_PM) or ($settlementHour > Constants::FIVE_PM)) ?
                    Constants::THREE_PM : Constants::FIVE_PM;
            }
        }

        return self::getNextBucket($settlementTime->getTimestamp(), $hour);
    }

    /**
     * check karvy merchant preference based if applicable
     * karvy wants only 2 settlements (1 and 3 PM)
     *
     * @param Merchant\Entity $merchant
     * @param Carbon          $settlementTime
     * @return array
     */
    protected function getKarvySettlementBucket(Merchant\Entity $merchant, Carbon $settlementTime): array
    {
        if ($merchant->getParentId() !== Merchant\Preferences::MID_KARVY)
        {
            return [false, 0];
        }

        $hour = Constants::THREE_PM;

        if (($settlementTime->hour <= Constants::ONE_PM) or ($settlementTime->hour > Constants::THREE_PM))
        {
            $hour = Constants::ONE_PM;
        }

        $timestamp = self::getNextBucket($settlementTime->getTimestamp(), $hour);

        return [true, $timestamp];
    }

    /**
     * Mutual fund merchant preferences
     * This has multiple requirements like
     * 1. Only one settlement per day (@ 1 PM)
     * 2. Only two settlements per day (@ 1 and 2 PM)
     *
     * @param Merchant\Entity $merchant
     * @param Carbon          $settlementTime
     * @return array
     */
    protected function getMutualFundBucket(Merchant\Entity $merchant, Carbon $settlementTime): array
    {
        // Mutual Fund Marketplace Merchant ids
        $merchantIds = [
            Merchant\Preferences::MID_GOALWISE_TPV,
            Merchant\Preferences::MID_GOALWISE_NON_TPV,
            Merchant\Preferences::MID_WEALTHAPP,
            Merchant\Preferences::MID_WEALTHY,
            Merchant\Preferences::MID_PAISABAZAAR,
        ];

        $parentMerchantId = $merchant->getParentId();

        if (($merchant->isLinkedAccount() === false) or
            (in_array($parentMerchantId, $merchantIds, true) === false))
        {
            return [false, 0];
        }

        //
        // Maps the mids that want to receive only 1 settlement per day.
        // They need all transactions till 1 pm to be settled in the 1 pm cycle.
        //
        $oneSetlAt1PmMids = [
            Merchant\Preferences::MID_WEALTHY,
            Merchant\Preferences::MID_PAISABAZAAR,
        ];

        //Ref: https://razorpay.slack.com/archives/C01F6GQPD9A/p1605701696001600?thread_ts=1605701546.001400&cid=C01F6GQPD9A
        // please go through the above thread before updating
        if ($parentMerchantId === Merchant\Preferences::MID_WEALTHY)
        {
            $hour = Constants::ONE_PM;
        }
        // matched merchant will have only one settlement per day (@ 2 PM)
        //Ref: https://razorpay.slack.com/archives/C49FL903W/p1607494426496100
        else if ($parentMerchantId === Merchant\Preferences::MID_PAISABAZAAR)
        {
            $hour = Constants::TWO_PM;
        }
        else
        {
            // These merchants will have two settlements per day (@ 1 and 2 PM)
            $hour = Constants::TWO_PM;

            if (($settlementTime->hour <= Constants::ONE_PM) or ($settlementTime->hour > Constants::TWO_PM))
            {
                $hour = Constants::ONE_PM;
            }
        }

        if (($parentMerchantId === Merchant\Preferences::MID_WEALTHY) and
            ($settlementTime->dayOfWeek === Carbon::SATURDAY))
        {
            $timestamp = Holidays::getNextWorkingDay($settlementTime);

            $timestamp = self::getNextBucket($timestamp->getTimestamp(), Constants::NINE_AM);

            return [true, $timestamp];
        }

        $timestamp = self::getNextBucket($settlementTime->getTimestamp(), $hour);

        return [true, $timestamp];
    }

    /**
     * DSP merchant preference
     * this merchant wants settlement only between 10AM - 4PM
     *
     * @param Merchant\Entity $merchant
     * @param Carbon          $settlementTime
     * @return array
     */
    protected function getDSPMerchantBucket(Merchant\Entity $merchant, Carbon $settlementTime): array
    {
        $merchantId = $merchant->getId();

        if ($merchantId === Constants::MERCHANT_DSP)
        {
            $hour = 0;

            if (($settlementTime->hour > Constants::THREE_PM) or ($settlementTime->hour <= Constants::TEN_AM))
            {
                $hour = Constants::TEN_AM;
            }

            $timestamp = self::getNextBucket($settlementTime->getTimestamp(), $hour);

            return [true, $timestamp];
        }

        return [false, 0];
    }

    /**
     * ScripBox Merchant Preference
     * This merchant wants the settlement for T-1 1PM to T 11 AM on T 11 AM
     * and for T 11 AM to T 1PM on T 1PM
     * @param Merchant\Entity $merchant
     * @param Carbon $settlementTime
     * @return array
     */
    protected function getScripBoxMerchantBucket(Merchant\Entity $merchant, Carbon $settlementTime): array
    {
        $merchantId = $merchant->getId();

        $parentMerchantId = $merchant->getParentId();

        if ($merchantId !== Merchant\Preferences::MID_SCRIP_BOX)
        {
            if($parentMerchantId !== Merchant\Preferences::MID_SCRIP_BOX)
            {
                return [false, 0];
            }
        }

        $hour = Constants::ONE_PM;

        if (($settlementTime->hour <= Constants::ELEVEN_AM) or ($settlementTime->hour > Constants::ONE_PM))
        {
            $hour = Constants::ELEVEN_AM;
        }

        $timestamp = self::getNextBucket($settlementTime->getTimestamp(), $hour);

        return [true, $timestamp];
    }

    /**
     * ET-Money Merchant Preference
     * want the settlement at 12PM(T-1 - 2PM to T - 12PM) and 2PM(T - 12PM to T - 2PM)
     * ref: https://razorpay.slack.com/archives/CAW3Z5Y6P/p1609150557366700
     * @param Merchant\Entity $merchant
     * @param Carbon $settlementTime
     * @return array
     */
    protected function getEtMoneyMerchantBucket(Merchant\Entity $merchant, Carbon $settlementTime): array
    {
        $merchantId = $merchant->getId();

        $parentMerchantId = $merchant->getParentId();

        if ($merchantId !== Merchant\Preferences::MID_ET_MONEY)
        {
            if($parentMerchantId !== Merchant\Preferences::MID_ET_MONEY)
            {
                return [false, 0];
            }
        }

        $hour = Constants::TWO_PM;

        if (($settlementTime->hour <= Constants::TWELVE_PM) or ($settlementTime->hour > Constants::TWO_PM))
        {
            $hour = Constants::TWELVE_PM;
        }

        $timestamp = self::getNextBucket($settlementTime->getTimestamp(), $hour);

        return [true, $timestamp];
    }
}
