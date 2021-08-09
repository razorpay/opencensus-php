<?php


namespace RZP\Models\Merchant\AutoKyc\Escalations;


use RZP\Models\Merchant\Constants as MConstants;

class Utils
{
    /**
     * Method to determine what is the next level for the given type
     * - Reads from config
     * @param string $type
     * @param int $currentLevel
     * @return int|null
     */
    public static function getNextEscalationLevel(string $type, int $currentLevel)
    {
        $nextLevel = $currentLevel + 1;

        if(isset(Constants::ESCALATION_CONFIG[$type][$nextLevel]) === true)
        {
            return $nextLevel;
        }

        return null;
    }

    /**
     * Method to determine if next escalation is possible at a given time
     * - reads duration from config based on current type and level
     *   and checks the diff from previous escalation timestamp
     * @param $escalation
     * @param $cronTime
     * @param string $type
     * @param int|null $level
     * @return bool
     */
    public static function isEscalationPossible($escalation, $cronTime, string $type, ?int $level)
    {
        if($level == null)
        {
            return false;
        }

        $previousEscalationTime = $escalation->getCreatedAt();

        $duration = Constants::ESCALATION_CONFIG[$type][$level]['duration'];
        $durationInSeconds = $duration * 60;

        return ($cronTime - $previousEscalationTime) >= $durationInSeconds;
    }

    public static function appendValueToKey($value, $key, &$map)
    {
        if(array_key_exists($key, $map))
        {
            $map[$key][] = $value;
        }
        else
        {
            $map[$key] = [$value];
        }
    }

    public static function getMerchantIdList($merchants)
    {
        $merchantIdList = [];

        foreach($merchants as $merchant)
        {
            $merchantIdList[] = $merchant->getId();
        }

        return $merchantIdList;
    }
    /**
     * Method to determine what is the milestone for give level and the given type
     * - Reads from config
     * @param string $type
     * @param int $level
     * @return string|null
     */
    public static function getEscalationMilestone(string $type, int $level)
    {
        if(isset(Constants::ESCALATION_CONFIG[$type][$level]) === true and isset(Constants::ESCALATION_CONFIG[$type][$level][MConstants::MILESTONE])===true)
        {
            return Constants::ESCALATION_CONFIG[$type][$level][MConstants::MILESTONE];
        }

        return null;
    }
}
