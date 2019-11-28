<?php

namespace RZP\Models\SubscriptionRegistration;

use Queue;
use RZP\Constants;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Invoice;
use RZP\Models\Customer\Token;
use RZP\Jobs\TokenRegistrationAutoCharge;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    public function listTokens(array $input): array
    {
        $result = $this->repo->subscription_registration->fetchRecurringTokensByMerchant(
            $this->merchant,
            $input);

        return $result->toArrayPublic();
    }

    public function listAuthLinks(array $input): array
    {
        $invoices = $this->repo->invoice->fetchForEntityType(
            $input,
            $this->merchant->getId(),
            Constants\Entity::SUBSCRIPTION_REGISTRATION
        );

        return $invoices->toArrayPublic();
    }

    public function createAuthLink(array $input): array
    {
        $invoice = $this->core->createAuthLink($input, $this->merchant);

        return $invoice->toArrayPublic();
    }

    public function fetchAuthLink(string $id, array $input): array
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
            $id,
            $this->merchant,
            null,
            null,
            $input,
            Constants\Entity::SUBSCRIPTION_REGISTRATION
        );

        return (new ViewDataSerializer($invoice))->serializeForApi();
    }

    public function fetchAuthLinkInternal(string $id, array $input): array
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
            $id,
            $this->merchant,
            null,
            null,
            $input,
            Constants\Entity::SUBSCRIPTION_REGISTRATION
        );

        $data = (new ViewDataSerializer($invoice))->serializeForApiInternal();

        return $data;
    }

    public function fetchToken(String $id, array $input): array
    {
        $token = $this->repo->token->findByPublicIdAndMerchant($id, $this->merchant, $input);

        return (new Token\ViewDataSerializer($token))->serializeForSubscriptionRegistration();
    }

    public function deleteToken(String $id): array
    {
        return $this->core->deleteToken($id, $this->merchant);
    }

    public function chargeToken(String $id, array $input): array
    {
        return $this->core->chargeToken($id, $input, $this->merchant);
    }

    public function processAutoCharges(array $input)
    {
        $validator = new Validator();

        $validator->validateInput('autocharge', $input);

        $count = $input['count'] ?? 100;

        $merchantIds = $input['merchant_ids'] ?? [];

        $tokenRegistrationsToCharge = $this->repo->subscription_registration->getTokenRegistrationsForFirstCharge($merchantIds, $count);

        $tokenRegistrationsPicked = [];

        foreach ($tokenRegistrationsToCharge as $tokr)
        {
            try
            {
                $autoChargeJob = new TokenRegistrationAutoCharge($this->mode, $tokr);

                Queue::push($autoChargeJob);

                $this->trace->info(TraceCode::TOKEN_REGISTRATION_AUTO_CHARGE_JOB_INITIATED,
                    [
                       'id'   => $tokr->getId(),
                       'mode' => $this->mode
                    ]);
                array_push($tokenRegistrationsPicked, $tokr->getId());
            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::TOKEN_REGISTRATION_AUTO_CHARGE_QUEUE_FAILED,
                    [
                        'token.registration_id'  => $tokr->getId(),
                        'mode'                   => $this->mode
                    ]
                );
            }
        }

        return $tokenRegistrationsPicked;
    }

    public function associateToken(string $id, array $input)
    {
        $tokenRegistration = $this->repo->subscription_registration->findByPublicId($id);

        $validator = $tokenRegistration->getValidator();

        $validator->validateInput('associate_token', $input);

        $validator->validateTokenRegistrationToAssociate();

        $token = $this->repo->token->findByPublicId($input[Entity::TOKEN_ID]);

        $this->core->associateToken($tokenRegistration, $token);

        return $tokenRegistration->toArrayAdmin();
    }

    public function authenticateTokens(array $input)
    {
        $validator = new Validator;

        $validator->validateInput('authenticate_tokens', $input);

        $subscriptionRegistrations = [];

        $failed = [];

        foreach ($input[Entity::IDS] as $id)
        {
            try
            {
                $validator->validateInput('public_id', ['id' => $id]);

                $subscriptionRegistration = $this->authenticateToken($id);

                array_push($subscriptionRegistrations, $subscriptionRegistration[Entity::ID]);
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e);

                array_push($failed, $id);
            }
        }

        return [
            'failed'    => $failed,
            'succeeded' => $subscriptionRegistrations,
        ];
    }

    public function paperMandateAuthenticate(array $input): array
    {
        $validator = new Validator;

        $validator->validatePaperMandateAuthenticateInput($input);

        $subscriptionRegistration = null;

        if (empty($input[Entity::ORDER_ID]) === false)
        {
            $subscriptionRegistration = $this->getSubscriptionRegistrationForOrder($input[Entity::ORDER_ID]);
        }
        else if (empty($input[Entity::AUTH_LINK_ID]) === false)
        {
            $subscriptionRegistration = $this->getSubscriptionRegistrationForInvoice($input[Entity::AUTH_LINK_ID]);
        }
        else
        {
            throw new Exception\LogicException(
                'should not have reached here'
            );
        }

        $validator->validateSubscriptionRegistrationForAuthentication($subscriptionRegistration);

        return $this->core->paperMandateAuthenticate($subscriptionRegistration, $input);
    }

    public function paperMandateAuthenticateProxy(array $input): array
    {
        $data = $this->paperMandateAuthenticate($input);

        if ($data[SubscriptionRegistrationConstants::SUCCESS] === true)
        {
            $data[SubscriptionRegistrationConstants::PAYMENT_RESPONSE] = $this->createPaymentForPaperMandate($input);
        }

        return $data;
    }

    protected function createPaymentForPaperMandate(array $input)
    {
        $paymentInput = [];

        $orderId = null;

        if (empty($input[Entity::ORDER_ID]) === false)
        {
            $orderId = $input[Entity::ORDER_ID];
        }
        else
        {
            $invoiceId = $input[Entity::AUTH_LINK_ID];

            $invoice = $this->repo->invoice->findByPublicIdAndMerchant($invoiceId, $this->merchant);

            $orderId = Order\Entity::getSignedId($invoice->getOrderId());
        }

        $subscriptionRegistration = $this->getSubscriptionRegistrationForOrder($orderId);

        $paperMandate = $subscriptionRegistration->paperMandate;

        $customer = $paperMandate->customer;

        $order = $this->repo->order->findByPublicIdAndMerchant($orderId, $this->merchant);

        $paymentInput[Payment\Entity::AMOUNT]      = $order->getAmount();

        $paymentInput[Payment\Entity::CURRENCY]    = $order->getCurrency();

        $paymentInput[Payment\Entity::METHOD]      = $order->getMethod();

        $paymentInput[Payment\Entity::RECURRING]   = true;

        $paymentInput[Payment\Entity::ORDER_ID]    = $orderId;

        $paymentInput[Payment\Entity::CUSTOMER_ID] = $customer->getPublicId();

        $paymentInput[Payment\Entity::CONTACT]     = $customer->getContact();

        $paymentInput[Payment\Entity::EMAIL]       = $customer->getEmail();

        $paymentService = new Payment\Service();

        return $paymentService->process($paymentInput);
    }

    public function paperMandateValidate(array $input): array
    {
        $validator = new Validator;

        $validator->validatePaperMandateAuthenticateInput($input);

        $subscriptionRegistration = null;

        if (empty($input[Entity::ORDER_ID]) === false)
        {
            $subscriptionRegistration = $this->getSubscriptionRegistrationForOrder($input[Entity::ORDER_ID]);
        }
        else if (empty($input[Entity::AUTH_LINK_ID]) === false)
        {
            $subscriptionRegistration = $this->getSubscriptionRegistrationForInvoice($input[Entity::AUTH_LINK_ID]);
        }
        else
        {
            throw new Exception\LogicException(
                'should not have reached here'
            );
        }

        $validator->validateSubscriptionRegistrationForAuthentication($subscriptionRegistration);

        return $this->core->paperMandateValidate($subscriptionRegistration, $input);
    }

    public function getUploadedPaperMandateForm(array $input)
    {
        (new Validator)->validateGetUploadedPaperMandateForm($input);

        if (empty($input[Entity::ORDER_ID]) === false)
        {
            $subscriptionRegistration = $this->getSubscriptionRegistrationForOrder($input[Entity::ORDER_ID]);
        }
        else if (empty($input[Entity::AUTH_LINK_ID]) === false)
        {
            $subscriptionRegistration = $this->getSubscriptionRegistrationForInvoice($input[Entity::AUTH_LINK_ID]);
        }
        else
        {
            $subscriptionRegistration = $this->getSubscriptionRegistrationForToken($input[Entity::TOKEN_ID]);
        }

        $paperMandate = $subscriptionRegistration->paperMandate;

        return [
            SubscriptionRegistrationConstants::URL => $paperMandate->getUploadedFormUrl()
        ];
    }

    public function retryPaperMandateToken($tokenId)
    {
        $token = $this->repo->token->findByPublicIdAndMerchant($tokenId, $this->merchant);

        $subscriptionRegistration = $this->getSubscriptionRegistrationForToken($tokenId);

        $subscriptionRegistration->getValidator()->validateTokenToRetry($token);

        $payments = $token->nachPayments;

        if (count($payments) !== 1)
        {
            throw new Exception\LogicException(
                'exactly one payment should have been created for the given token',
                null,
                [
                    'token_id'       => $token->getId(),
                    'payments_count' => count($payments),
                ]
            );
        }

        $payment = $payments->get(0);

        $order = $payment->order;

        if ($order === null)
        {
            throw new Exception\LogicException(
                'order can\'t be null for nach method for payment',
                null,
                [
                    'token_id'       => $token->getId(),
                    'payment_id'     => $payment->getId(),
                ]
            );
        }

        return $this->createPaymentForPaperMandate([Entity::ORDER_ID => $order->getPublicId()]);
    }

    public function nachRegisterTestPaymentAuthorizeOrFail(string $id, array $input)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchant($id, $this->merchant);

        $subscriptionRegistration = $invoice->entity;

        if (($subscriptionRegistration === null) or
            ($invoice->getEntityType() !== Constants\Entity::SUBSCRIPTION_REGISTRATION))
        {
            throw new Exception\BadRequestValidationFailureException(
                'id provided does not exist'
            );
        }

        $subscriptionRegistration->getValidator()->validateNachRegisterTestPaymentAuthorizeOrFail();

        (new Validator)->validateInput('nach_register_test_payment', $input);

        return $this->core->nachRegisterTestPaymentAuthorizeOrFail($subscriptionRegistration, $input);
    }

    protected function getSubscriptionRegistrationForToken(string $tokenId)
    {
        $tokenId = Token\Entity::stripDefaultSign($tokenId);

        $subscriptionRegistration = $this->repo
                                         ->subscription_registration
                                         ->findByTokenIdAndMerchant($tokenId, $this->merchant->getId());

        if ($subscriptionRegistration === null)
        {
            throw new Exception\BadRequestValidationFailureException("The id provided does not exist");
        }

        return $subscriptionRegistration;
    }

    protected function getSubscriptionRegistrationForOrder(string $orderId)
    {
        $order = $this->repo->order->findByPublicIdAndMerchant($orderId, $this->merchant);

        (new Validator)->validateOrderCreatedForTokenRegistration($order);

        $invoice = $order->invoice;

        return $this->getSubscriptionRegistrationForInvoice($invoice->getPublicId());
    }

    protected function getSubscriptionRegistrationForInvoice(string $invoiceId)
    {
        $invoice = $this->repo
                        ->invoice
                        ->findByPublicIdAndMerchant($invoiceId, $this->merchant);

        (new Validator)->validateInvoiceCreatedForTokenRegistration($invoice);

        $subscriptionRegistration = $invoice->tokenRegistration;

        return $subscriptionRegistration;
    }

    protected function authenticateToken(string $id)
    {
        $tokenRegistration = $this->repo->subscription_registration->findByPublicId($id);

        $tokenRegistration->getValidator()->validateTokenRegistrationToAuthenticate();

        $token = $tokenRegistration->token;

        $this->core->authenticate($tokenRegistration, $token);

        return $tokenRegistration->toArrayAdmin();
    }

    public function sendNotification(string $id, string $medium): array
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
            $id,
            $this->merchant,
            null,
            null,
            [],
            Constants\Entity::SUBSCRIPTION_REGISTRATION
        );

        $invoice->setRelation('entity', $invoice->entity);

        $order = $invoice->order;

        $order->getValidator()->validateOrderNotPaid();

        $data = (new Invoice\Core())->sendNotification($invoice, $medium);

        return $data;
    }

    public function cancelAuthLink(string $id)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
            $id,
            $this->merchant,
            null,
            null,
            [],
            Constants\Entity::SUBSCRIPTION_REGISTRATION
        );

        $order = $invoice->order;

        $order->getValidator()->validateOrderNotPaid();

        $invoice = (new Invoice\Core())->cancelInvoice($invoice);

        return $invoice->toArrayPublic();
    }
}
