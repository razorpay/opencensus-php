<?php

namespace RZP\Models\SubscriptionRegistration;

use App;

use RZP\Base;
use RZP\Constants;
use RZP\Models\Order;
use RZP\Models\Feature;
use RZP\Models\Invoice;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\PaperMandate;
use RZP\Models\Customer\Token;
use RZP\Constants\Entity as E;
use RZP\Error\PublicErrorDescription;
use Carbon\Carbon;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\UpiMandate\Entity as UPI_MANDATE;
use RZP\Models\Customer\Entity as CustomerEntity;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::EXPIRE_AT                       => 'sometimes|epoch',
        Entity::CURRENCY                        => 'sometimes|string|size:3',
        Entity::MAX_AMOUNT                      => 'sometimes|integer|nullable',
        Entity::FIRST_PAYMENT_AMOUNT            => 'sometimes|integer|nullable',
        Entity::AUTH_TYPE                       => 'sometimes|string|nullable|in:netbanking,aadhaar,debitcard,physical,migrated',
        Entity::METHOD                          => 'sometimes|string|nullable|in:emandate,card,nach,upi',
        Entity::NOTES                           => 'sometimes|notes',
        Entity::FREQUENCY                       => 'required_if:method,upi|in:weekly,monthly,yearly,as_presented,quarterly',
        Entity::RECURRING_TYPE                  => 'sometimes|string|in:before,on,after',
        Entity::RECURRING_VALUE                 => 'sometimes|integer|nullable',
    ];

    protected static $createValidators = [
        Entity::AUTH_TYPE,
        Entity::FIRST_PAYMENT_AMOUNT,
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
        Entity::ORDER_ID                             => 'required_without:auth_link_id|public_id',
        Entity::AUTH_LINK_ID                         => 'required_without:order_id|public_id',
        PaperMandate\Entity::FORM_UPLOADED           => 'required_without:paper_mandate_upload_id|mimes:jpg,jpeg,png,gif,bmp,svg|max:5120',
        PaperMandate\Entity::PAPER_MANDATE_UPLOAD_ID => 'sometimes|public_id',
        'key_id'                                     => 'sometimes|string',
        'x_entity_id'                                => 'sometimes|string',
    ];

    protected static $getUploadedPaperMandateFormRules = [
        Entity::ORDER_ID                   => 'public_id',
        Entity::AUTH_LINK_ID               => 'public_id',
        Entity::TOKEN_ID                   => 'public_id',
        'key_id'                           => 'sometimes|string',
    ];

    protected static $createSubscriptionRegistrationRules = [
        Entity::EXPIRE_AT                       => 'sometimes|epoch',
        Entity::CURRENCY                        => 'sometimes|string|size:3',
        Entity::MAX_AMOUNT                      => 'sometimes|integer|nullable',
        Entity::FIRST_PAYMENT_AMOUNT            => 'sometimes|integer|nullable',
        Entity::AUTH_TYPE                       => 'sometimes|string|nullable|in:netbanking,aadhaar,debitcard,physical,migrated',
        Entity::METHOD                          => 'sometimes|string|nullable|in:emandate,card,nach,upi',
        Entity::NOTES                           => 'sometimes|notes',
        Entity::BANK_ACCOUNT                    => 'required_if:method,nach',
        Entity::NACH                            => 'sometimes_if:method,nach|custom',
        Entity::FREQUENCY                       => 'required_if:method,upi|in:weekly,monthly,yearly,as_presented,quarterly',
        Entity::RECURRING_TYPE                  => 'sometimes|string|in:before,on,after',
        Entity::RECURRING_VALUE                 => 'sometimes|integer|nullable',
    ];

    protected static $nachAuthTypeRules = [
        Entity::AUTH_TYPE => 'required|string|in:physical,migrated',
    ];

    protected static $emandateAuthTypeRules = [
        Entity::AUTH_TYPE => 'sometimes|string|nullable|in:netbanking,aadhaar,debitcard,migrated',
    ];

    protected static $nachArrayRules = [
        Entity::CREATE_FORM     => 'sometimes|bool',
        Entity::FORM_REFERENCE1 => 'sometimes|string',
        Entity::FORM_REFERENCE2 => 'sometimes|string',
    ];

    protected static $minAmountCheckRules = [
        Entity::FIRST_PAYMENT_AMOUNT => 'required|integer|min_amount'
    ];

    protected static $listTokensRules = [
        Base\Fetch::COUNT         => 'sometimes|integer|min:1|max:100',
        Base\Fetch::SKIP          => 'sometimes|integer|min:0',
        Entity::PAYMENT_ID        => 'sometimes|public_id',
        Entity::CUSTOMER_CONTACT  => 'sometimes|contact_syntax',
        Entity::CUSTOMER_EMAIL    => 'sometimes|email',
        Entity::RECURRING_STATUS  => 'sometimes|recurring_status',
    ];

    public function validateMaxAmount(array $input, $countryCode = 'IN')
    {
        $maxAmount = $input[Entity::MAX_AMOUNT] ?? null;

        if ($maxAmount !== null)
        {
            $maxAmountLimit = Token\Entity::EMANDATE_MAX_AMOUNT_LIMIT;

            $authType = $input[Entity::AUTH_TYPE] ?? null;
            $method = $input[Entity::METHOD] ?? null;

            if ($authType === Payment\AuthType::PHYSICAL ||
                    ($method === Payment\Method::NACH && $authType === Payment\AuthType::MIGRATED ) )
            {
                $maxAmountLimit = PaperMandate\Validator::MAX_AMOUNT_LIMIT;
            }
            elseif (($authType === Payment\AuthType::AADHAAR) or
                ($authType === Payment\AuthType::AADHAAR_FP))
            {
                $maxAmountLimit = Token\Entity::AADHAAR_EMANDATE_MAX_AMOUNT_LIMIT;
            }
            elseif ($method === Payment\Method::CARD or $method === null)
            {
                $maxAmountLimit = Token\Entity::RECURRING_MAX_AMOUNT[$countryCode][Payment\Method::CARD];
            }

            if ($maxAmount > $maxAmountLimit)
            {

                $this->getTrace()->count(
                    Metric::SUBSCRIPTION_REGISTRATION_VALIDATION_FAILED,
                    [
                        'message' => 'The max amount may not be greater than ' . $maxAmountLimit . '.',
                        'route' => App::getFacadeRoot()['api.route']->getCurrentRouteName(),
                    ]
                );

                throw new BadRequestValidationFailureException(
                    'The max amount may not be greater than ' . $maxAmountLimit . '.',
                    Entity::MAX_AMOUNT
                );
            }

            if ($maxAmount <= Token\Entity::LEAST_MAX_AMOUNT_LIMIT)
            {

                $this->getTrace()->count(
                    Metric::SUBSCRIPTION_REGISTRATION_VALIDATION_FAILED,
                    [
                        'message' => 'The max amount should be greater than zero.',
                        'route' => App::getFacadeRoot()['api.route']->getCurrentRouteName(),
                    ]
                );

                throw new BadRequestValidationFailureException(
                    'The max amount should be greater than zero.',
                    Entity::MAX_AMOUNT
                );
            }
        }
    }

    public function validateFrequencyAndMaxAmountCardRecurring(array $input)
    {
        $freqArray = array(ENTITY::WEEKLY, ENTITY::MONTHLY, ENTITY::YEARLY, ENTITY::AS_PRESENTED);

        $frequency = $input[Entity::FREQUENCY] ?? null;
        $maxAmount = $input[Entity::MAX_AMOUNT] ?? null;

        if($frequency === null){
            throw new BadRequestValidationFailureException(
                'frequency cannot be empty.',
                Entity::FREQUENCY
            );
        } elseif (!in_array($frequency, $freqArray)) {
            throw new BadRequestValidationFailureException(
                'The selected frequency is invalid',
                Entity::FREQUENCY
            );
        }

        if($maxAmount === null){
            throw new BadRequestValidationFailureException(
                'max amount cannot be empty.',
                Entity::MAX_AMOUNT
            );
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
                        $this->getTrace()->count(
                            Metric::SUBSCRIPTION_REGISTRATION_VALIDATION_FAILED,
                            [
                                'message' => 'token.first_payment_amount should be “null” for method = “card”',
                                'route' => App::getFacadeRoot()['api.route']->getCurrentRouteName(),
                            ]
                        );

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

            $this->getTrace()->count(
                Metric::SUBSCRIPTION_REGISTRATION_VALIDATION_FAILED,
                [
                    'message' => 'order method doesn\'t match with token method',
                    'route' => App::getFacadeRoot()['api.route']->getCurrentRouteName(),
                ]
            );

            throw new BadRequestValidationFailureException(
                'order method doesn\'t match with token method',
                Entity::METHOD
            );
        }
    }

    public function validateCustomerDetailsForAuthLink(array $input, Merchant\Entity $merchant = null)
    {
        if ($merchant !== null and
            $merchant->isFeatureEnabled(Feature\Constants::CAW_IGNORE_CUSTOMER_CHECK) === true)
        {
            // This is for backward compatibility
            return;
        }

        if (empty($input[CustomerEntity::CONTACT]) === true)
        {

            $this->getTrace()->count(
                Metric::SUBSCRIPTION_REGISTRATION_VALIDATION_FAILED,
                [
                    'message' => PublicErrorDescription::BAD_REQUEST_AUTH_LINK_CONTACT_EMPTY,
                    'route' => App::getFacadeRoot()['api.route']->getCurrentRouteName(),
                ]
            );

            throw new BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_AUTH_LINK_CONTACT_EMPTY,
                CustomerEntity::CONTACT
            );
        }

        if (empty($input[CustomerEntity::EMAIL]) === true and
            $merchant->isFeatureEnabled(Feature\Constants::EMAIL_OPTIONAL) === false)
        {

            $this->getTrace()->count(
                Metric::SUBSCRIPTION_REGISTRATION_VALIDATION_FAILED,
                [
                    'message' => PublicErrorDescription::BAD_REQUEST_AUTH_LINK_EMAIL_EMPTY,
                    'route' => App::getFacadeRoot()['api.route']->getCurrentRouteName(),
                ]
            );

            throw new BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_AUTH_LINK_EMAIL_EMPTY,
                CustomerEntity::EMAIL
            );
        }
    }

    public function validateTokenRegistrationToAssociate()
    {
        if ($this->entity->token !== null)
        {

            $this->getTrace()->count(
                Metric::SUBSCRIPTION_REGISTRATION_VALIDATION_FAILED,
                [
                    'message' => 'token is already associated',
                    'route' => App::getFacadeRoot()['api.route']->getCurrentRouteName(),
                ]
            );

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

            $this->getTrace()->count(
                Metric::SUBSCRIPTION_REGISTRATION_VALIDATION_FAILED,
                [
                    'message' => 'token must be associated first to authenticate',
                    'route' => App::getFacadeRoot()['api.route']->getCurrentRouteName(),
                ]
            );

            throw new BadRequestValidationFailureException(
                'token must be associated first to authenticate',
                Entity::TOKEN
            );
        }

        if ($this->entity->getStatus() !== Status::CREATED)
        {

            $this->getTrace()->count(
                Metric::SUBSCRIPTION_REGISTRATION_VALIDATION_FAILED,
                [
                    'message' => 'token can be authorized only in created state of token registration',
                    'route' => App::getFacadeRoot()['api.route']->getCurrentRouteName(),
                ]
            );

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

            $this->getTrace()->count(
                Metric::SUBSCRIPTION_REGISTRATION_VALIDATION_FAILED,
                [
                    'message' => 'token registration should be created for paper mandate',
                    'route' => App::getFacadeRoot()['api.route']->getCurrentRouteName(),
                ]
            );

            throw new BadRequestValidationFailureException('token registration should be created for paper mandate');
        }

        $subscriptionRegistration->paperMandate->getValidator()->validateToAuthenticate();
    }

    public function validateInvoiceCreatedForTokenRegistration(Invoice\Entity $invoice)
    {
        if ($invoice->getEntityType() !== E::SUBSCRIPTION_REGISTRATION)
        {

            $this->getTrace()->count(
                Metric::SUBSCRIPTION_REGISTRATION_VALIDATION_FAILED,
                [
                    'message' => 'invoice created should be for token registration',
                    'route' => App::getFacadeRoot()['api.route']->getCurrentRouteName(),
                ]
            );

            throw new BadRequestValidationFailureException(
                'invoice created should be for token registration'
            );
        }
    }

    public function validateOrderCreatedForTokenRegistration(Order\Entity $order)
    {
        if ($order->getMethod() !== Method::NACH)
        {

            $this->getTrace()->count(
                Metric::SUBSCRIPTION_REGISTRATION_VALIDATION_FAILED,
                [
                    'message' => 'order created should be for token registration',
                    'route' => App::getFacadeRoot()['api.route']->getCurrentRouteName(),
                ]
            );

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

            $this->getTrace()->count(
                Metric::SUBSCRIPTION_REGISTRATION_VALIDATION_FAILED,
                [
                    'message' => 'both order id and auth link id is not required',
                    'route' => App::getFacadeRoot()['api.route']->getCurrentRouteName(),
                ]
            );

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

            $this->getTrace()->count(
                Metric::SUBSCRIPTION_REGISTRATION_VALIDATION_FAILED,
                [
                    'message' => 'any one of order or auth link or token id is required',
                    'route' => App::getFacadeRoot()['api.route']->getCurrentRouteName(),
                ]
            );

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

            $this->getTrace()->count(
                Metric::SUBSCRIPTION_REGISTRATION_VALIDATION_FAILED,
                [
                    'message' => 'paper mandate can\'t be null for token registration',
                    'route' => App::getFacadeRoot()['api.route']->getCurrentRouteName(),
                ]
            );

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

        $method = $input[Entity::METHOD] ?? null;

        if ($method === Method::UPI)
        {

            $this->getTrace()->count(
                Metric::SUBSCRIPTION_REGISTRATION_VALIDATION_FAILED,
                [
                    'message' => 'first payment amount not allowed',
                    'route' => App::getFacadeRoot()['api.route']->getCurrentRouteName(),
                ]
            );

            throw new BadRequestValidationFailureException(
                'first payment amount not allowed'
            );
        }

        $maxAmount = empty($input[Entity::MAX_AMOUNT]) ?
            Entity::getDefaultMaxAmountForMethod($method) : $input[Entity::MAX_AMOUNT];

        $firstPaymentAmount = empty($input[Entity::FIRST_PAYMENT_AMOUNT]) ?
            0 : $input[Entity::FIRST_PAYMENT_AMOUNT];

        if ($firstPaymentAmount > $maxAmount)
        {

            $this->getTrace()->count(
                Metric::SUBSCRIPTION_REGISTRATION_VALIDATION_FAILED,
                [
                    'message' => 'first payment amount cannot be greater than maximum amount',
                    'route' => App::getFacadeRoot()['api.route']->getCurrentRouteName(),
                ]
            );

            throw new BadRequestValidationFailureException(
                'first payment amount cannot be greater than maximum amount'
            );
        }

        if ($firstPaymentAmount != 0)
        {
            $inputAmount = [
                Entity::FIRST_PAYMENT_AMOUNT => $firstPaymentAmount,
            ];

            $this->validateInputValues('min_amount_check', $inputAmount);
        }
    }

    public function validateTokenToRetry(Token\Entity $token)
    {
        $app = App::getFacadeRoot();

        $this->repo = $app['repo'];

        if ($token->getRecurringStatus() !== Token\RecurringStatus::REJECTED)
        {

            $this->getTrace()->count(
                Metric::SUBSCRIPTION_REGISTRATION_VALIDATION_FAILED,
                [
                    'message' => 'token can\'t be retried if it is not rejected',
                    'route' => App::getFacadeRoot()['api.route']->getCurrentRouteName(),
                ]
            );

            throw new BadRequestValidationFailureException(
                'token can\'t be retried if it is not rejected'
            );
        }

        if ($token->getMethod() !== Payment\Method::NACH)
        {

            $this->getTrace()->count(
                Metric::SUBSCRIPTION_REGISTRATION_VALIDATION_FAILED,
                [
                    'message' => 'only nach method token can be retried',
                    'route' => App::getFacadeRoot()['api.route']->getCurrentRouteName(),
                ]
            );

            throw new BadRequestValidationFailureException(
                'only nach method token can be retried'
            );
        }

        $tokenId = $token->getId();

        if (count($this->repo->payment->getPaymentCountByToken($tokenId)->get()) !== 1)
        {

            $this->getTrace()->count(
                Metric::SUBSCRIPTION_REGISTRATION_VALIDATION_FAILED,
                [
                    'message' => 'token can be retried only if exactly one payment created for it',
                    'route' => App::getFacadeRoot()['api.route']->getCurrentRouteName(),
                ]
            );

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

            $this->getTrace()->count(
                Metric::SUBSCRIPTION_REGISTRATION_VALIDATION_FAILED,
                [
                    'message' => 'this is only allowed for test payments',
                    'route' => App::getFacadeRoot()['api.route']->getCurrentRouteName(),
                ]
            );

            throw new BadRequestValidationFailureException(
                'this is only allowed for test payments'
            );
        }

        if ($this->entity->getMethod() !== Method::NACH)
        {

            $this->getTrace()->count(
                Metric::SUBSCRIPTION_REGISTRATION_VALIDATION_FAILED,
                [
                    'message' => 'this is only allowed for nach method',
                    'route' => App::getFacadeRoot()['api.route']->getCurrentRouteName(),
                ]
            );

            throw new BadRequestValidationFailureException(
                'this is only allowed for nach method'
            );
        }

        if ($this->entity->token === null)
        {

            $this->getTrace()->count(
                Metric::SUBSCRIPTION_REGISTRATION_VALIDATION_FAILED,
                [
                    'message' => 'payment is not created yet',
                    'route' => App::getFacadeRoot()['api.route']->getCurrentRouteName(),
                ]
            );

            throw new BadRequestValidationFailureException(
                'payment is not created yet'
            );
        }

        if ($this->entity->token->getRecurringStatus() !== Token\RecurringStatus::INITIATED)
        {

            $this->getTrace()->count(
                Metric::SUBSCRIPTION_REGISTRATION_VALIDATION_FAILED,
                [
                    'message' => 'payment is already processed, can\'t perform this now',
                    'route' => App::getFacadeRoot()['api.route']->getCurrentRouteName(),
                ]
            );

            throw new BadRequestValidationFailureException(
                'payment is already processed, can\'t perform this now'
            );
        }
    }

    public function validateBankAccountBeforeCreation(string $method, array $bankInput, Merchant\Entity $merchant): void
    {
        if (isset($bankInput[Token\Entity::ACCOUNT_TYPE]) === false)
        {
            return;
        }

        if (in_array($bankInput[Token\Entity::ACCOUNT_TYPE],
                PaperMandate\Constants::NACH_EXTRA_BANK_ACCOUNT_TYPES, true) === true)
        {
            if ($method !== Method::NACH)
            {

                $this->getTrace()->count(
                    Metric::SUBSCRIPTION_REGISTRATION_VALIDATION_FAILED,
                    [
                        'message' => 'The selected account type is invalid.',
                        'route' => App::getFacadeRoot()['api.route']->getCurrentRouteName(),
                    ]
                );

                throw new BadRequestValidationFailureException(
                    'The selected account type is invalid.');
            }
        }
    }

    public function validateTokenExpiryDate(array $input)
    {
        if (empty($input[Entity::EXPIRE_AT]) === false) {
            if (Carbon::now()->timestamp >= $input[Entity::EXPIRE_AT])
            {
                throw new BadRequestValidationFailureException(
                    'expire_at cannot be less than current time'
                );
            }
        }
    }
}
