<?php


namespace RZP\Models\Merchant\Escalations;


class Utils
{
    public static function getClassShortName($clazz)
    {
        return substr(strrchr($clazz, '\\'), 1);
    }

    public static function getBreachedThreshold(int $amount)
    {
        $escalationMatrix = array_reverse(Constants::PAYMENTS_ESCALATION_MATRIX, true);

        foreach ($escalationMatrix as $threshold => $escalations)
        {
            if($amount >= $threshold)
            {
                return $threshold;
            }
        }
        return null;
    }
}
