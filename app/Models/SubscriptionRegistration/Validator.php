<?php

namespace RZP\Models\SubscriptionRegistration;

use App;

use RZP\Base;
use RZP\Constants;
use RZP\Models\Order;
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

    protected static $associateTokenRules = [
        Entity::TOKEN_ID => 'required|public_id',
    ];

    protected static $authenticateTokensRules = [
        Entity::IDS => 'required|array',
    ];

    protected static $publicIdRules = [
        Entity::ID => 'required|public_id',
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

    public function validateMethodWithOrder(array $input, Order\Entity $order)
    {
        if ((empty($input[Constants\Entity::SUBSCRIPTION_REGISTRATION][Entity::METHOD]) === false) and
            ($input[Constants\Entity::SUBSCRIPTION_REGISTRATION][Entity::METHOD] !== $order->getMethod()))
        {
            throw new BadRequestValidationFailureException(
                'order method doesn\'t match with token method',
                Entity::METHOD
            );
        }
    }

    public function validateTokenRegistrationToAssociate()
    {
        if ($this->entity->token !== null)
        {
            throw new BadRequestValidationFailureException(
                'token is already associated',
                Entity::TOKEN
            );
        }
    }

    public function validateTokenRegistrationToAuthenticate()
    {
        if ($this->entity->token === null)
        {
            throw new BadRequestValidationFailureException(
                'token must be associated first to authenticate',
                Entity::TOKEN
            );
        }

        if ($this->entity->getStatus() !== Status::CREATED)
        {
            throw new BadRequestValidationFailureException(
                'token can be authorized only in created state of token registration',
                Entity::TOKEN
            );
        }
    }
}
