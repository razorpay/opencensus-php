<?php

namespace RZP\Models\PartnerBankHealth;

use RZP\Models\Base;
use RZP\Base\BuilderEx;
use RZP\Models\Base\PublicCollection;
use RZP\Models\PartnerBankHealth\Entity;
use RZP\Constants\Entity as EntityConstants;

/**
 * Class Repository
 * This DB table is just a key-value store for partner bank health events
 * @package RZP\Models\PartnerBankHealth
 */
class Repository extends Base\Repository
{
    protected $entity = EntityConstants::PARTNER_BANK_HEALTH;

    public function getPartnerBankHealthStatusesFromEventType(string $key)
    {
        return $this->newQuery()
                    ->where(Entity::EVENT_TYPE, '=', $key)
                    ->first();
    }

    public function fetch(array $params, string $merchantId = null, string $connectionType = null): PublicCollection
    {
        $eventTypePattern = '';

        if (empty($params[Constants::SOURCE]) === false)
        {
            $eventTypePattern .= $params[Constants::SOURCE] . '.';
        }
        else
        {
            $eventTypePattern .= '%.';
        }

        unset($params[Constants::SOURCE]);

        if (empty($params[Constants::INTEGRATION_TYPE]) === false)
        {
            $eventTypePattern .= $params[Constants::INTEGRATION_TYPE] . '.';
        }
        else
        {
            $eventTypePattern .= '%.';
        }

        unset($params[Constants::INTEGRATION_TYPE]);

        if (empty($params[Constants::PAYOUT_MODE]) === false)
        {
            $eventTypePattern .= strtolower($params[Constants::PAYOUT_MODE]);
        }
        else
        {
            $eventTypePattern .= '%';
        }

        unset($params[Constants::PAYOUT_MODE]);

        if ($eventTypePattern !== '%.%.%')
        {
            $params[Constants::EVENT_TYPE_PATTERN] = $eventTypePattern;
        }

        return parent::fetch($params, $merchantId, $connectionType);
    }

    public function addQueryParamEventTypePattern(BuilderEx $query, $params)
    {
        $key = $params[Constants::EVENT_TYPE_PATTERN];

        if(strpos($key, '%') !== false)
        {
            $query->where(Entity::EVENT_TYPE, 'like', $key);
        }
        else
        {
            $query->where(Entity::EVENT_TYPE, '=', $key);
        }
    }
}
