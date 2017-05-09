<?php

namespace RZP\Models\Report;

use RZP\Base;
use RZP\Exception;
use RZP\Base\JitValidator;
use RZP\Constants\Entity as E;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::DAY             => 'sometimes|integer|between:1,31',
        Entity::MONTH           => 'required|integer|between:1,12',
        Entity::YEAR            => 'required|integer',
        Entity::TYPE            => 'required|string',
        Entity::START_TIME      => 'required|integer',
        Entity::END_TIME        => 'required|integer',
        Entity::GENERATED_BY    => 'required|string',
    ];

    protected static $reportQueueRules = [
        Entity::DAY             => 'sometimes|integer|between:1,31',
        Entity::MONTH           => 'required|integer|between:1,12',
        Entity::YEAR            => 'required|integer',
    ];

    // Entities for which report-generation is allowed
    protected $allowed = [
        E::ORDER,
        E::REFUND,
        E::PAYMENT,
        E::SETTLEMENT,
        E::TRANSACTION,
        E::MERCHANT,
        E::TRANSFER,
        E::REVERSAL,
    ];

    public function validateQueueInput(array $input)
    {
        (new JitValidator)->rules(self::$reportQueueRules)->input($input)->validate();
    }

    /**
     * Checks if the entity is allowed to be made a report of
     * Thows exception
     */
    public function validateAllowedEntity(string $entity)
    {
        if (in_array($entity, $this->allowed, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Cannot get report for the given entity');
        }

        if (($entity === E::MERCHANT) and
            ($this->entity->merchant->isMarketplace() === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Exporting this data is not allowed for the merchant');
        }
    }
}
