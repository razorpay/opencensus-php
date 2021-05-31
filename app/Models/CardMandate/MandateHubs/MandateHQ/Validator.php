<?php

namespace RZP\Models\CardMandate\MandateHubs\MandateHQ;

use RZP\Base;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $processCallBackRules = [
        Constants::WEBHOOK_ENTITY     => 'required|string|in:event',
        Constants::WEBHOOK_EVENT      => 'required|string|custom',
        Constants::WEBHOOK_CONTAINS   => 'required|array|custom',
        Constants::WEBHOOK_PAYLOAD    => 'required|associative_array',
        Constants::WEBHOOK_CREATED_AT => 'required|epoch',
    ];

    protected static $processCallBackValidators = [
        Constants::WEBHOOK_PAYLOAD,
    ];

    protected static $webhookPayloadRules = [
        Constants::WEBHOOK_ENTITY_MANDATE      => 'sometimes|associative_array',
        Constants::WEBHOOK_ENTITY_NOTIFICATION => 'sometimes|associative_array',
    ];

    public function validateEvent($attribute, $value)
    {
        if (in_array($value, Constants::$WebhookEvents) === false)
        {
            throw new BadRequestValidationFailureException(
                $value . ' is not a valid webhook event'
            );
        }
    }

    public function validateContains($attribute, $value)
    {
        foreach ($value as $entity) {
            if (in_array($entity, Constants::$WebhookEntities) === false)
            {
                throw new BadRequestValidationFailureException(
                    $entity . ' is not a valid entity'
                );
            }
        }
    }

    public function validatePayload($input)
    {
        $contains = $input[Constants::WEBHOOK_CONTAINS];
        $payload = $input[Constants::WEBHOOK_PAYLOAD];

        foreach ($contains as $entity)
        {
            if (isset($payload[$entity]) === false)
            {
                throw new BadRequestValidationFailureException(
                    $entity . ' is not present in the payload'
                );
            }
        }

        $this->validateInput('webhook_payload', $payload);
    }
}
