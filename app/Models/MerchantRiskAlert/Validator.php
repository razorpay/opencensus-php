<?php


namespace RZP\Models\MerchantRiskAlert;

use App;
use RZP\Models\Workflow\Action;
use RZP\Base\Validator as BaseValidator;
use RZP\Constants\Entity as EntityConstants;
use RZP\Exception\BadRequestValidationFailureException;


class Validator extends BaseValidator
{

    const TEAM_NAME_ERROR_MESSAGE = "Team name not correct.";

    protected $app;

    protected static array $needsClarificationRequestRules = [
        "email_body"            => 'required|string',
        "team_name"             => 'required|string|custom'
    ];

    public function __construct($entity = null)
    {
        parent::__construct($entity);

        $this->app = App::getFacadeRoot();
    }

    protected function validateTeamName(string $attribute, string $value)
    {
        if($value != Constants::TRANSACTION_MONITORING_TEAM_NAME && $value != Constants::MERCHANT_RISK_PG_TEAM_NAME)
        {
            throw new BadRequestValidationFailureException(self::TEAM_NAME_ERROR_MESSAGE);
        }
    }
    public function validateTriggerNeedsClarificationRequest($action): void
    {
        if ($action->getAttribute(Action\Entity::ENTITY_NAME) !== EntityConstants::MERCHANT_DETAIL)
        {
            $message = 'Cannot send needs clarification email for entity:' .
                $action->getEntityName() ;

            throw new BadRequestValidationFailureException($message);
        }

        $this->validateNeedsClarificationRequestNotAlreadyTriggered($action);
    }

    protected function validateNeedsClarificationRequestNotAlreadyTriggered($action)
    {
        $cacheKey = (new Service)->getCacheKeyForNeedsClarificationRequest($action);

        if ($this->app['cache']->get($cacheKey) === null)
        {
            return;
        }

        $message = 'RAS Needs clarification already sent for this workflow. Cannot be triggered again';

        throw new BadRequestValidationFailureException($message);

    }
}
