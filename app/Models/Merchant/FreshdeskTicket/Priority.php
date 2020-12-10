<?php


namespace RZP\Models\Merchant\FreshdeskTicket;


class Priority
{
    const LOW       = 'Low';
    const MEDIUM    = 'Medium';
    const HIGH      = 'High';
    const URGENT    = 'Urgent';

    protected static $values = [
        self::LOW       => 1,
        self::MEDIUM    => 2,
        self::HIGH      => 3,
        self::URGENT    => 4,
    ];

    public static function getValueForPriorityString($priorityString)
    {
        return self::$values[$priorityString];
    }

    public static function getPriorityStringForValue($priorityValue)
    {
        $values = array_flip(self::$values);

        return $values[$priorityValue];
    }

    public static function isValidPriorityString($priortyString)
    {
        return array_key_exists($priortyString, self::$values) === true;
    }

    public static function isValidPriorityValue($priorityValue)
    {
        $values = array_flip(self::$values);

        return array_key_exists($priorityValue, $values) === true;
    }
}
