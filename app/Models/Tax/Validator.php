<?php

namespace RZP\Models\Tax;

use RZP\Base;
use RZP\Exception\LogicException;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME      => 'required|string|max:512',
        Entity::RATE_TYPE => 'sometimes|string|in:percentage,flat',
        Entity::RATE      => 'required|integer|min:0',
    ];

    protected static $editRules  = [
        Entity::NAME      => 'sometimes|string|max:512',
        Entity::RATE_TYPE => 'sometimes|string|in:percentage,flat',
        Entity::RATE      => 'sometimes|integer|min:0',
    ];

    protected static $createValidators = [
        Entity::RATE,
    ];

    protected static $editValidators = [
        Entity::RATE,
    ];

    /**
     * Validates rate wrt to rate_type.
     *
     * @param array $input
     *
     * @return
     *
     * @throws LogicException
     * @throws BadRequestValidationFailureException
     */
    public function validateRate(array $input)
    {
        // $rateType and $rate we get from either input or the entity itself
        // to ensure that validation happened with combinations(create, update
        // with rate,rate_type or both etc.) of use cases.

        $rateType = $input[Entity::RATE_TYPE] ?? $this->entity->getRateType();

        $rate = $input[Entity::RATE] ?? $this->entity->getRate();

        if ($rateType === RateType::PERCENTAGE)
        {
            $this->validatePercentageRate($rate);
        }

        // Other rate type: flat has no validations as of now. It can be any
        // integer value.
    }

    // Private methods

    private function validatePercentageRate(int $rate)
    {
        if (($rate < 0) or ($rate > 1000000))
        {
            $message = 'rate should be between 0 to 1000000 if rate_type is percentage';

            throw new BadRequestValidationFailureException($message, Entity::RATE);
        }
    }
}
