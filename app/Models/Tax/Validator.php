<?php

namespace RZP\Models\Tax;

use RZP\Base;
use RZP\Exception\LogicException;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME      => 'required|string|max:512',
        Entity::RATE_TYPE => 'required|string|in:percentage,flat',
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
        $tax = $this->entity;

        // $rateType and $rate we get from either input or the entity itself
        // to ensure that validation happened with combinations(create, update
        // with rate,rate_type or both etc.) of use cases.

        $rateType = $input[Entity::RATE_TYPE] ?? $tax->getRateType();

        $rate = $input[Entity::RATE] ?? $tax->getRate();

        switch ($rateType)
        {
            case RateType::PERCENTAGE:

                $this->validatePercentageRate($rate);

                break;

            case RateType::FLAT:

                $this->validateFlatRate($rate);

                break;

            default:

                throw new LogicException('rate_type is invalid');
        }
    }

    // Private methods

    private function validatePercentageRate(int $rate)
    {
        if (($rate < 0) or ($rate > 10000))
        {
            $message = 'rate should be between 0 to 10000 if rate_type is percentage';

            throw new BadRequestValidationFailureException($message);
        }
    }

    private function validateFlatRate(int $rate)
    {
        // TODO:
        // - Limits?
    }
}
