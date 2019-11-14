<?php

namespace RZP\Models\SubscriptionRegistration;

use Queue;
use RZP\Constants;
use RZP\Exception;
use RZP\Jobs\Job;
use RZP\Models\Base;
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

        $data = $this->core->sendNotification($invoice, $medium);

        return $data;
    }
}
