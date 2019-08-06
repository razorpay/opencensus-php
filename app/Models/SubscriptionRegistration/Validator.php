<?php

namespace RZP\Models\SubscriptionRegistration;

use App;

use RZP\Base;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::EXPIRE_AT                       => 'sometimes|epoch',
        Entity::MAX_AMOUNT                      => 'sometimes|integer|nullable',
        Entity::FIRST_PAYMENT_AMOUNT            => 'sometimes|integer|nullable',
        Entity::AUTH_TYPE                       => 'sometimes|string|nullable|in:netbanking,aadhaar',
        Entity::METHOD                          => 'sometimes|string|nullable|in:emandate,card',
        Entity::NOTES                           => 'sometimes|notes',
    ];

    protected static $autochargeRules = [
        'count'         => 'sometimes|integer',
        'merchant_ids'  => 'sometimes|string'
    ];

    public function validateMethodAndFirstPaymentAmount(array $input)
    {
        if (array_key_exists(Entity::FIRST_PAYMENT_AMOUNT, $input))
        {
            if (array_key_exists(Entity::METHOD, $input))
            {
                $firstPaymentAmount = $input[Entity::FIRST_PAYMENT_AMOUNT];

                $method = $input[Entity::METHOD];

                if ($method === 'card')
                {
                    if ($firstPaymentAmount > 0)
                    {
                        throw new BadRequestValidationFailureException('token.first_payment_amount should be “null” for method = “card”');
                    }
                }
            }
        }
    }
}
