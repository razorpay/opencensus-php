<?php

namespace RZP\Models\Merchant\WebhookV2;

use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;

/**
 * Since webhookV2 is a proxy layer this file contains
 * only those validations which stork cannot do
 */
class Validator extends \RZP\Base\Validator
{
    // keys peresent in the payload
    const SUBSCRIPTIONS = 'subscriptions';
    const EVENT_META    = 'eventmeta';
    const EVENT_NAME    = 'name';

    /**
     * This method validates the stork input. Includes validations
     * which stork cannot contain.
     * @param array           $input    stork input
     * @param Merchant\Entity $merchant the merchant entity
     *
     * @throws Exception\BadRequestException
     */
    public function validateStorkWebhookInput(array $input, Merchant\Entity $merchant)
    {
        if (isset($input[self::SUBSCRIPTIONS]) === true)
        {
            $this->validateStorkSubscriptions($input[self::SUBSCRIPTIONS], $merchant);
        }
    }

    //validates subscription payload for stork input
    protected function validateStorkSubscriptions(array $subscriptions, Merchant\Entity $merchant)
    {
        $eventNames = array_values(
            array_filter(
                array_map(
                    function ($subscription)
                    {
                        return $subscription[self::EVENT_META][self::EVENT_NAME] ?? null;
                    },
                    $subscriptions
                )
            )
        );

        $events = [];

        foreach ($eventNames as $name)
        {
            $events[$name] = '1';
        }

        // Additionally, validates that events sent in request are allowed
        // feature, product origin wise.
        $filteredEvents = Merchant\Webhook\Event::filterForPublicApi($merchant, $events);

        $extraEvents = array_diff(array_keys($events), array_keys($filteredEvents));

        if (count($extraEvents) > 0)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid event name/names: ' . implode(', ', $extraEvents));
        }
    }

    /**
     * @param Merchant\Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validatePartnerWithWebhooksAccess(Merchant\Entity $merchant)
    {
        //
        // the `$nonPartnerOAuth` condition is for B/C, should be removed after
        // all oauth merchants are moved to partner type pure_platform.
        //
        $nonPartnerOAuth = (($merchant->isPartner() === false) and ($merchant->isTagAdded('Oauth') === true));

        if (($merchant->isPartnerWithWebhooksAccess() === false) and ($nonPartnerOAuth === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
                Merchant\Entity::PARTNER_TYPE,
                [
                    Merchant\Entity::ID           => $merchant->getId(),
                    Merchant\Entity::PARTNER_TYPE => $merchant->getPartnerType(),
                ]);
        }
    }
}
