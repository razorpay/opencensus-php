<?php


namespace RZP\Models\Merchant\AutoKyc\Escalations\Types;


use RZP\Models\Merchant\AutoKyc\Escalations\Constants;

class Factory
{
    /**
     * Returns an instance of appropriate escalation type based on type and level
     * @param string $type
     * @param int $level
     * @return Email|Workflow
     */
    public static function getInstance(string $type, int $level)
    {
        $method = self::getEscalationMethodForTypeAndLevel($type, $level);

        switch ($method)
        {
            case Constants::WORKFLOW:
                return (new Workflow);
            case Constants::EMAIL:
                return (new Email);
        }
    }

    /**
     * Determines which escalation method to choose based on type and level
     * - Reads from config
     * @param string $type
     * @param int $level
     * @return mixed
     */
    private static function getEscalationMethodForTypeAndLevel(string $type, int $level)
    {
        return Constants::ESCALATION_CONFIG[$type][$level]['method'];
    }
}
