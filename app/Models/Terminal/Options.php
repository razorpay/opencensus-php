<?php

namespace RZP\Models\Terminal;

use RZP\Models\Gateway\Rule;

class Options
{
    const FAILED = 'failed';

    protected $chance;

    protected static $testChance;

    protected $hasMultiple = false;

    protected $failedTerminals = [];

    // Filters to be skipped for merchants with rule_filter
    // feature enabled
    protected $featureSkippedFilters = [
        'method',
        'network',
        'bank',
        'category',
    ];

    // Filters to be skipped for any merchant
    protected $globalSkippedFilters = [
        'amount',
        'iin',
        'billdesk_category',
        'billdesk_merchant',
        'incompatible',
        'pharma',
    ];

    // Maps the rule group name to the corresponding filter property.
    // This is temporary and will be used only in the migration phase
    // until all merchants are using rule filters
    protected $ruleGroupToFilterPropertyMap = [
        'min_amount_filter'        => 'amount',
        'prepaid_iin_filter'       => 'iin',
        'billdesk_category_filter' => 'billdesk_category',
        'billdesk_merchant_filter' => 'billdesk_merchant',
        'pharma_filter'            => 'pharma',
        'tpv_filter'               => 'incompatible',
    ];

    public function __construct()
    {
        $this->setChance();

        $this->setMultiple();
    }

    public function setMultiple($multiple = true)
    {
        $this->hasMultiple = $multiple;
    }

    public function getMultiple()
    {
        return $this->hasMultiple;
    }

    public function getChance()
    {
        return $this->chance;
    }

    public function setChance()
    {
        $chance = self::getTestChance();

        if ($chance === null)
        {
            $this->chance = rand(0, Rule\Entity::MAX_LOAD);
            return;
        }

        $this->chance = $chance;
    }

    public function getFeatureSkippedFilters()
    {
        return $this->featureSkippedFilters;
    }

    public function setFeatureSkippedFilters(array $filters)
    {
        $this->featureSkippedFilters = $filters;
    }

    public function getGlobalSkippedFilters()
    {
        return $this->globalSkippedFilters;
    }

    public function setGlobalSkippedFilters(array $filters)
    {
        $this->globalSkippedFilters = $filters;
    }

    public function getFilterPropertyForRuleGroup(string $group)
    {
        return $this->ruleGroupToFilterPropertyMap[$group] ?? null;
    }

    public function setFailedTerminals(array $exclude)
    {
        $this->failedTerminals = $exclude;
    }

    public function getFailedTerminals()
    {
        return $this->failedTerminals;
    }

    public static function setTestChance($testChance = 0)
    {
        static::$testChance = $testChance;
    }

    public static function getTestChance()
    {
        return static::$testChance;
    }

    public static function hasTestChance()
    {
        return static::getTestChance() !== null;
    }
}
