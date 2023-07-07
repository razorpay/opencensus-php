<?php


namespace RZP\Models\MerchantRiskAlert;

use App;
use RZP\Models\Workflow\Action;
use RZP\Base\Validator as BaseValidator;
use RZP\Constants\Entity as EntityConstants;
use RZP\Exception\BadRequestValidationFailureException;


class Validator extends BaseValidator
{

    protected $app;

    protected static array $needsClarificationRequestRules = [
        "clarification_type"     => 'required|string',
        "clarification_sub_type" => 'required|string',
        "email_body"            => 'required|string'
    ];

    public function __construct($entity = null)
    {
        parent::__construct($entity);

        $this->app = App::getFacadeRoot();
    }

    public function validateTriggerNeedsClarificationRequest($action): void
    {
        if ($action->getAttribute(Action\Entity::ENTITY_NAME) !== EntityConstants::MERCHANT_DETAIL)
        {
            $message = 'Cannot RAS send needs clarification email for ' .
                $action->getEntityName() . ' entity';

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
