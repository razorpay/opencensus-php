<?php

namespace RZP\Models\SubscriptionRegistration;

use App;

use RZP\Base;
use RZP\Constants;
use RZP\Models\Order;
use RZP\Models\Invoice;
use RZP\Models\Payment;
use RZP\Models\PaperMandate;
use RZP\Models\Customer\Token;
use RZP\Constants\Entity as E;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    const EMANDATE_MAX_AMOUNT_LIMIT = 9999900;

    protected static $createRules = [
        Entity::EXPIRE_AT                       => 'sometimes|epoch',
        Entity::MAX_AMOUNT                      => 'sometimes|integer|nullable',
        Entity::FIRST_PAYMENT_AMOUNT            => 'sometimes|integer|nullable',
        Entity::AUTH_TYPE                       => 'sometimes|string|nullable|in:netbanking,aadhaar,debitcard,physical',
        Entity::METHOD                          => 'sometimes|string|nullable|in:emandate,card,nach',
        Entity::NOTES                           => 'sometimes|notes',
    ];

    protected static $createValidators = [
        Entity::AUTH_TYPE,
        Entity::MAX_AMOUNT,
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

    protected static $nachRegisterTestPaymentRules = [
        Entity::SUCCEED => 'sometimes|bool',
    ];

    protected static $paperMandateAuthenticateRules = [
        Entity::ORDER_ID                   => 'required_without:auth_link_id|public_id',
        Entity::AUTH_LINK_ID               => 'required_without:order_id|public_id',
        PaperMandate\Entity::FORM_UPLOADED => 'required|image|max:5120',
        'key_id'                           => 'sometimes|string',
        'x_entity_id'                      => 'sometimes|string',
    ];

    protected static $getUploadedPaperMandateFormRules = [
        Entity::ORDER_ID                   => 'public_id',
        Entity::AUTH_LINK_ID               => 'public_id',
        Entity::TOKEN_ID                   => 'public_id',
        'key_id'                           => 'sometimes|string',
    ];

    protected static $createSubscriptionRegistrationRules = [
        Entity::EXPIRE_AT                       => 'sometimes|epoch',
        Entity::MAX_AMOUNT                      => 'sometimes|integer|nullable',
        Entity::FIRST_PAYMENT_AMOUNT            => 'sometimes|integer|nullable',
        Entity::AUTH_TYPE                       => 'sometimes|string|nullable|in:netbanking,aadhaar,debitcard,physical',
        Entity::METHOD                          => 'sometimes|string|nullable|in:emandate,card,nach',
        Entity::NOTES                           => 'sometimes|notes',
        Entity::BANK_ACCOUNT                    => 'required_if:method,nach',
        Entity::NACH                            => 'sometimes_if:method,nach|custom',
    ];

    protected static $nachAuthTypeRules = [
        Entity::AUTH_TYPE => 'required|string|in:physical',
    ];

    protected static $emandateAuthTypeRules = [
        Entity::AUTH_TYPE => 'sometimes|string|nullable|in:netbanking,aadhaar,debitcard',
    ];

    protected static $nachArrayRules = [
        Entity::CREATE_FORM     => 'sometimes|bool',
        Entity::FORM_REFERENCE1 => 'sometimes|string',
        Entity::FORM_REFERENCE2 => 'sometimes|string',
    ];

    public function validateMaxAmount(array $input)
    {
        $maxAmount = $input[Entity::MAX_AMOUNT] ?? null;

        if ($maxAmount !== null)
        {
            $maxAmountLimit = self::EMANDATE_MAX_AMOUNT_LIMIT;

            $authType = $input[Entity::AUTH_TYPE] ?? null;

            if ($authType === Payment\AuthType::PHYSICAL)
            {
                $maxAmountLimit = PaperMandate\Validator::MAX_AMOUNT_LIMIT;
            }

            if ($maxAmount > $maxAmountLimit)
            {
                throw new BadRequestValidationFailureException(
                    'The amount may not be greater than ' . $maxAmountLimit . '.',
                    Entity::MAX_AMOUNT
                );
            }
        }
    }

    public function validateAuthType(array $input)
    {
        if (($input[Entity::METHOD] ?? null) === Method::NACH)
        {
            $this->validateInput(
                'nach_auth_type',
                [Entity::AUTH_TYPE => $input[Entity::AUTH_TYPE] ?? null]
            );
        }
        else
        {
            $this->validateInput(
                'emandate_auth_type',
                [Entity::AUTH_TYPE => $input[Entity::AUTH_TYPE] ?? null]
            );
        }
    }

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

    public function validateSubscriptionRegistrationForAuthentication(Entity $subscriptionRegistration)
    {
        if (($subscriptionRegistration->paperMandate === null) or
            ($subscriptionRegistration->paperMandate->getEntityName() !== E::PAPER_MANDATE))
        {
            throw new BadRequestValidationFailureException('token registration should be created for paper mandate');
        }

        $subscriptionRegistration->paperMandate->getValidator()->validateToAuthenticate();
    }



    public function validateInvoiceCreatedForTokenRegistration(Invoice\Entity $invoice)
    {
        if ($invoice->getEntityType() !== E::SUBSCRIPTION_REGISTRATION)
        {
            throw new BadRequestValidationFailureException(
                'invoice created should be for token registration'
            );
        }
    }

    public function validateOrderCreatedForTokenRegistration(Order\Entity $order)
    {
        if ($order->getMethod() !== Method::NACH)
        {
            throw new BadRequestValidationFailureException(
                'order created should be for token registration'
            );
        }
    }

    public function validatePaperMandateAuthenticateInput(array $input)
    {
        if ((empty($input[Entity::AUTH_LINK_ID]) === false) and
            (empty($input[Entity::ORDER_ID]) === false))
        {
            throw new BadRequestValidationFailureException(
              'both order id and auth link id is not required'
            );
        }

        $this->validateInput('paper_mandate_authenticate', $input);
    }

    public function validateGetUploadedPaperMandateForm(array $input)
    {
        $idCount = 0;
        $idCount = empty($input[Entity::AUTH_LINK_ID]) ? $idCount : $idCount + 1;
        $idCount = empty($input[Entity::ORDER_ID]) ? $idCount : $idCount + 1;
        $idCount = empty($input[Entity::TOKEN_ID]) ? $idCount : $idCount + 1;

        if ($idCount !== 1)
        {
            throw new BadRequestValidationFailureException(
                'any one of order or auth link or token id is required'
            );
        }

        $this->validateInput('get_uploaded_paper_mandate_form', $input);
    }

    public function validatePaymentCreation()
    {
        if ($this->entity->getMethod() !== Method::NACH)
        {
            return;
        }

        $paperMandate = $this->entity->paperMandate;

        if ($paperMandate === null)
        {
            throw new \LogicException(
                'paper mandate can\'t be null for token registration',
                null,
                [
                    'tokenRegistration' => $this->entity->toArray(),
                ]);
        }

        $paperMandate->getValidator()->validatePaymentCreation();
    }

    public function validateFirstPaymentAmount(array $input)
    {
        if (empty($input[Entity::FIRST_PAYMENT_AMOUNT]) === true)
        {
            return;
        }

        if ($input[Entity::METHOD] === Method::NACH)
        {
            $maxAmount = $input[Entity::MAX_AMOUNT] ?? PaperMandate\Entity::DEFAULT_AMOUNT;

            if ($input[Entity::FIRST_PAYMENT_AMOUNT] > $maxAmount)
            {
                throw new BadRequestValidationFailureException(
                    'first payment amount cannot be greater than maximum amount'
                );
            }
        }

    }

    public function validateTokenToRetry(Token\Entity $token)
    {
        if ($token->getRecurringStatus() !== Token\RecurringStatus::REJECTED)
        {
            throw new BadRequestValidationFailureException(
                'token can\'t be retried if it is not rejected'
            );
        }

        if ($token->getMethod() !== Payment\Method::NACH)
        {
            throw new BadRequestValidationFailureException(
                'only nach method token can be retried'
            );
        }

        if (count($token->nachPayments()->get()) !== 1)
        {
            throw new BadRequestValidationFailureException(
                'token can be retried only if exactly one payment created for it'
            );
        }
    }

    public function validateNach($attribute, $value)
    {
        $this->validateInput('nach_array', $value);
    }

    public function validateNachRegisterTestPaymentAuthorizeOrFail()
    {
        if ($this->getMode() !== Constants\Mode::TEST)
        {
            throw new BadRequestValidationFailureException(
                'this is only allowed for test payments'
            );
        }

        if ($this->entity->getMethod() !== Method::NACH)
        {
            throw new BadRequestValidationFailureException(
                'this is only allowed for nach method'
            );
        }

        if ($this->entity->token === null)
        {
            throw new BadRequestValidationFailureException(
                'payment is not created yet'
            );
        }

        if ($this->entity->token->getRecurringStatus() !== Token\RecurringStatus::INITIATED)
        {
            throw new BadRequestValidationFailureException(
                'payment is already processed, can\'t perform this now'
            );
        }
    }
}
