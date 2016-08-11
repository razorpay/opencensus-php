<?php

namespace RZP\Models\Terminal;

class Options
{
    protected $chance;

    protected static $testChance;

    protected $hasMultiple;

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