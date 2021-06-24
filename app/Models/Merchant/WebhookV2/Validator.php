<?php

namespace RZP\Models\Merchant\WebhookV2;

use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Base\JitValidator;
use RZP\Models\Merchant\Entity;
use RZP\Models\Merchant\AccessMap;
use RZP\Models\Merchant\Constants;
use RZP\Models\Merchant\MerchantApplications;
use RZP\Models\Merchant\Account\Entity as AccountEntity;

use Razorpay\OAuth\Application\Repository as OauthAppRepository;
use Razorpay\OAuth\Exception\DBQueryException as OauthDBQueryException;

/**
 * Since webhookV2 is a proxy layer this file contains
 * only those validations which stork cannot do
 */
class Validator extends \RZP\Base\Validator
{
    const ID             = 'id';
    const URL            = 'url';
    const WEBHOOK        = 'webhook';
    const OWNER_ID       = 'owner_id';
    const OWNER_TYPE     = 'owner_type';
    const ALERT_EMAIL    = 'alert_email';
    const APPLICATION_ID = 'application_id';

    // keys peresent in the payload
    const SUBSCRIPTIONS = 'subscriptions';
    const EVENT_META    = 'eventmeta';
    const EVENT_NAME    = 'name';

    const EMAIL_TYPE = 'type';
    // deactivate is a type of email which can be sent.
    const EMAIL_DEACTIVATE = 'deactivate';
    // valid email types which can be sent.
    const VALID_EMAIL_TYPES = [self::EMAIL_DEACTIVATE];

    const MAX_ALLOWED_WEBHOOK_EVENTS_PER_CSV_FILE = 1000;

    // validation rules for the webhook array which needs to be passed to send a webhook disable email.
    protected static $deactivateEmailRules = [
        self::ID          => 'required|string|size:14',
        self::URL         => 'required|string|url|max:255|min:3',
        self::OWNER_ID    => 'required|string|size:14',
        self::OWNER_TYPE  => 'required|string|in:merchant',
        self::ALERT_EMAIL => 'sometimes|email',
    ];

    protected static $processWebhookEventsFromCsvRules = [
        Constant::FILE => 'required|file|max:2048|mime_types:text/csv,text/plain|mimes:csv,txt',
    ];

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

    /**
     * @param Merchant\Entity $merchant
     * @param string          $applicationId
     */
    public function validatePartnerMerchantHasApplicationAccess(Merchant\Entity $merchant, string $applicationId)
    {
        try
        {
            (new OauthAppRepository)->findActiveApplicationByIdAndMerchantId($applicationId, $merchant->getId());
        }
        catch (OauthDBQueryException $e)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
                self::APPLICATION_ID,
                [
                    Merchant\Entity::ID  => $merchant->getId(),
                    self::APPLICATION_ID => $applicationId,
                ]);
        }
    }

    /**
     * @param string $emailType type of email to be sent
     * @param array  $input     to build the email
     *
     * @throws Exception\BadRequestException
     */
    public function validateSendEmailInput(string $emailType, array $input)
    {
        if (in_array(strtolower($emailType), self::VALID_EMAIL_TYPES, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException('email type is not mentioned or is incorrect');
        }

        $fn = 'validate' . studly_case($emailType) . 'EmailData';
        $this->$fn($input);
    }

    /**
     * @param array $data
     *
     * @throws Exception\BadRequestException
     */
    protected function validateDeactivateEmailData(array $data)
    {
        if (isset($data[self::WEBHOOK]) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid payload. webhook not present');
        }

        (new JitValidator)->setStrictFalse()->rules(self::$deactivateEmailRules)->input($data[self::WEBHOOK])->validate();
    }

    public function validateProcessWebhookEventsFromCsvInput(array $input)
    {
        (new JitValidator)->rules(self::$processWebhookEventsFromCsvRules)->input($input)->validate();

        // File size is relatively small so not worrying about reading twice.
        // Also subtracts by 1 assuming first line is header.
        $numRows = count(file($input[Constant::FILE])) - 1;
        if ($numRows > self::MAX_ALLOWED_WEBHOOK_EVENTS_PER_CSV_FILE)
        {
            throw new Exception\BadRequestValidationFailureException(
                "Number of webhook events in file exceed max allowed limit: {$numRows}"
            );
        }
    }

    /**
     * This method validates the onboarding webhook action by partner for sub-merchant
     *
     * @param string $accountId
     * @param Entity $partner
     *
     * @throws Exception\BadRequestException
     */
    public function validateOnboardingWkAction(string &$accountId, Merchant\Entity $partner)
    {
        $accountId = AccountEntity::verifyIdAndStripSign($accountId);

        $subMerchant = (new Merchant\Repository())->findOrFailPublic($accountId);

        $this->validatePartnerSubMerchantMapping($partner, $subMerchant);
    }

    /**
     * This method validates if webhook events are present in input and if input is empty
     *
     * @param array|null $input
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateOnboardingWkInput(array $input)
    {
        if (empty($input))
        {
            throw new Exception\BadRequestValidationFailureException(
                "input cannot be empty"
            );
        }
        else if (((isset($input[Service::EVENTS]) === false) || sizeof($input[Service::EVENTS]) === 0))
        {
            throw new Exception\BadRequestValidationFailureException(
                "no webhook events provided in the input"
            );
        }
    }

    /**
     * This method checks if there exists a mapping between the partner and sub-merchant
     *
     * @param Entity $partner
     * @param Entity $subMerchant
     *
     * @throws Exception\BadRequestException
     */
    public function validatePartnerSubMerchantMapping(Merchant\Entity $partner, Merchant\Entity $subMerchant)
    {
        $appType = (new MerchantApplications\Core())->getDefaultAppTypeForPartner($partner);

        $isMapped = (new AccessMap\Core())->isMerchantMappedToPartnerWithAppType($partner, $subMerchant, $appType);

        if ($isMapped === false)
        {
             throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PARTNER_MERCHANT_MAPPING_NOT_FOUND,
                [
                    Entity::PARTNER_ID  => $partner->getId(),
                    Entity::MERCHANT_ID => $subMerchant->getId(),
                    Constants::APP_TYPE => $appType,
                ]
            );
        }
    }
}
