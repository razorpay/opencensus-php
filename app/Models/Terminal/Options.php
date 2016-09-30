<?php

namespace RZP\Models\Terminal;

use RZP\Constants\Mode;

class Options
{
    protected $chance;

    protected static $testChance;

    protected $hasMultiple = false;

    protected $exclusionList = [];

    public function __construct()
    {
        $this->setChance();

        $this->setMultiple();
    }

    public function setMultiple()
    {
        $this->hasMultiple = true;
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
            $this->chance = rand(0, 100);
            return;
        }

        $this->chance = $chance;
    }

    public function setExclusionList(array $exclude)
    {
        $this->exclusionList = $exclude;
    }

    public function getExclusionList()
    {
        return $this->exclusionList;
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