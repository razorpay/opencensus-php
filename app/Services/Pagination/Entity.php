<?php

namespace RZP\Services\Pagination;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Admin\ConfigKey;

class Entity
{
    // Attributes keys
    const START_TIME = 'start_time';
    const END_TIME = 'end_time';
    const DURATION = 'duration';
    const LIMIT = 'limit';
    const WHITELIST_MERCHANT_IDS = 'whitelist_merchant_ids';
    const BLACKLIST_MERCHANT_IDS = 'blacklist_merchant_ids';
    const RUN_FOR = 'run_for';
    const JOB_COMPLETED = 'job_completed';

    /***************** Redis keys *****************/

    // Redis key prefix for pagination
    const PAGINATION_ATTRIBUTES_FOR = 'pagination_attributes_for';

    const PAGINATION_ATTRIBUTES_FOR_TRIM_SPACE = 'pagination_attributes_for_trim_space';

    /***************** Redis key ends *****************/

    // Attribute array
    protected $attributes = array();

    /***************** Constrains *****************/

    protected $currentStartTime;
    protected $currentEndTime;

    protected $finalMerchantList;

    /***************** Constrains End *****************/

    public function build()
    {
        $this->setTimeConstraints();

        $this->setFinalMerchantList();
    }

    /***************** Getters *****************/

    public function getLimit()
    {
        return $this->getAttribute(self::LIMIT);
    }

    public function getFinalMerchantList()
    {
        return $this->finalMerchantList;
    }

    public function getCurrentStartTime()
    {
        return $this->currentStartTime->getTimestamp();
    }

    public function getCurrentEndTime()
    {
        return $this->currentEndTime->getTimestamp();
    }

    public function getStartTime()
    {
        return $this->getAttribute(self::START_TIME);
    }

    public function getEndTime()
    {
        return $this->getAttribute(self::END_TIME);
    }

    public function getRunFor()
    {
        return $this->getAttribute(self::RUN_FOR);
    }

    public function getAllParams()
    {
        return $this->attributes;
    }

    public function getAttribute($attr)
    {
        if (isset($this->attributes[$attr]))
        {
            return $this->attributes[$attr];
        }

        return null;
    }

    public function getRedisKey()
    {
        return constant(
            ConfigKey::class . '::' .
            strtoupper(self::PAGINATION_ATTRIBUTES_FOR . '_' .
                $this->getAttribute(self::RUN_FOR)));
    }

    /***************** Getters End *****************/

    /***************** Setters *****************/

    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Create final merchant ids list using whitelist_merchant_ids and
     * black_list_merchant_ids.
     *
     */
    protected function setFinalMerchantList()
    {
        $whitelistMerchants = $this->getAttribute(Entity::WHITELIST_MERCHANT_IDS);

        $blackListMerchants = $this->getAttribute(Entity::BLACKLIST_MERCHANT_IDS);

        $this->finalMerchantList = [];

        if (is_null($blackListMerchants) === false)
        {
            foreach ($whitelistMerchants as $whitelistMerchant)
            {
                if (in_array($whitelistMerchant, $blackListMerchants, true) === false)
                {
                    array_push($this->finalMerchantList, $whitelistMerchant);
                }
            }
        }
        else
        {
            $this->finalMerchantList = $whitelistMerchants;
        }
    }

    /**
     * Initiate time parameters converting them from timestamp to carbon date time
     * and setting up initial constrains.
     *
     */
    protected function setTimeConstraints()
    {
        $startTimeTimestamp = $this->getAttribute(Entity::START_TIME);

        $startTime = Carbon::createFromTimestamp($startTimeTimestamp, Timezone::IST);

        $this->setAttribute(Entity::START_TIME, $startTime);

        $endTimeTimestamp = $this->getAttribute(Entity::END_TIME);

        $endTime = Carbon::createFromTimestamp($endTimeTimestamp, Timezone::IST);

        $this->setAttribute(Entity::END_TIME, $endTime);

        $duration = $this->getAttribute(Entity::DURATION);

        $this->currentStartTime = $startTime;

        $this->currentEndTime = $startTime->copy();

        $this->currentEndTime->addSeconds($duration);
    }

    /***************** Setters End *****************/

    public function IsEndTimeGreaterThanCurrent()
    {
        return $this->getEndTime()->greaterThan($this->currentEndTime);
    }
}
