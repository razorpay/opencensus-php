<?php

namespace RZP\Models\Terminal;

use RZP\Models\Feature;
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
    ];

    // Rule group names  to be used for terminal filtering for all merchants
    protected $globallyApplicableRuleGroups = [
        'min_amount_filter',
        'prepaid_iin_filter',
        'billdesk_category_filter',
        'billdesk_merchant_filter',
        'tpv_filter',
        'category_filter',
        'gateway_exclusion_filter',
        'billdesk_education_filter',
        'maestro_filter',
        'currency_filter',
        'debit_recurring_filter',
        'atom_filter',
        'sbi_emi_filter',
        'routing_filter',
        'org_filter',
    ];

    protected $ruleGroupMapToFeature = [
        'tpv_filter' => Feature\Constants::TPV
    ];


    protected $authenticationRuleGroups = [
        'authentication',
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

    public function getRuleGroupMapToFeature()
    {
        return $this->ruleGroupMapToFeature;
    }

    public function getGloballyApplicableRuleGroups()
    {
        return $this->globallyApplicableRuleGroups;
    }

    public function getAuthenticationRuleGroups()
    {
        return $this->authenticationRuleGroups;
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
