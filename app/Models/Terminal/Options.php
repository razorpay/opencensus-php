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

    protected $skippedFilters = [
        'method',
        'network',
        'bank'
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

    public function getSkippedFilters()
    {
        return $this->skippedFilters;
    }

    public function setSkippedFilters(array $filters)
    {
        $this->skippedFilters = $filters;
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
