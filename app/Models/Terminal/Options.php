<?php

namespace RZP\Models\Terminal;

use RZP\Models\Gateway\LoadRule;

class Options
{
    const FAILED = 'failed';

    protected $chance;

    protected static $testChance;

    protected $hasMultiple = false;

    protected $failedTerminals = [];

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
            $this->chance = rand(0, LoadRule\Entity::MAX_LOAD);
            return;
        }

        $this->chance = $chance;
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
