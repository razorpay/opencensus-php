<?php

namespace RZP\Models\Plan\Subscription;

use RZP\Exception\BadRequestException;
use RZP\Exception\LogicException;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Plan;
use RZP\Models\Customer;
use RZP\Models\Customer\Token;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function create(array $input, Plan\Entity $plan, Token\Entity $token)
    {
        $subscription = (new Entity)->build($input);

        $this->associateEntitiesToSubscription($subscription, $plan, $token);

        $this->repo->saveOrFail($subscription);

        return $subscription;
    }

    public function charge(Entity $subscription)
    {
        $this->mutex->acquireAndRelease(
            $subscription->getId(),
            function() use($subscription) 
            {
                $recurringPayload = $this->constructRecurringPayload($subscription);

                $queuePayload = [
                    'recurring_payload' => $recurringPayload,
                    'subscription_id'   => $subscription->getId(),
                    // This would almost always be rzp_{mode},since it will be
                    // run via cron. We actually need the mode here. But basicauth
                    // functions mostly work on the key. Hence, sending the key
                    // across rather than the mode.
                    'key_id'            => $this->app['basicauth']->getPublicKey(),
                ];

                // If the status is in created state, this means that the token has not
                // been associated with it yet. An authorized payment for this subscription
                // has not been done.
                if ($subscription->getStatus() === Status::CREATED)
                {
                    throw new LogicException(
                        'Should not have reached here. The subscription is not ' .
                        'chargeable because it is still in created state.',
                        null,
                        [
                            'subscription_id'   => $subscription->getId(),
                            'status'            => $subscription->getStatus(),
                        ]);
                }

                $this->app['queue']->push(Charge::class . '@fireCharge', $queuePayload);
            }
        );
    }

    public function retry(Entity $subscription)
    {
        $this->charge($subscription);
    }

    /**
     * Subscription need not be updated if it's in created or processed state.
     * That flow would be taken care by the normal subscription capture flow.
     *
     * Only if it's in on_hold state with capture_failure as error, we need to
     * explicitly update the subscription. This is because, this capture would
     * have been an explicit call and not via normal subscription flow.
     *
     * @param Entity $subscription
     * @param Payment\Entity $capturedPayment
     * @return bool
     */
    public function shouldUpdateSubscriptionOnCapture(Entity $subscription, Payment\Entity $capturedPayment)
    {
        $status = $subscription->getStatus();
        $errorStatus = $subscription->getErrorStatus();

        if ($status === Status::ON_HOLD)
        {
            if ($errorStatus === Status::CAPTURE_FAILURE)
            {
                return true;
            }
            else
            {
                $this->trace->error(
                    TraceCode::SUBSCRIPTION_STATE_UNEXPECTED,
                    [
                        'payment_id'        => $capturedPayment->getId(),
                        'subscription_id'   => $subscription->getId(),
                        'status'            => $subscription->getStatus(),
                        'error_status'      => $subscription->getErrorStatus(),
                    ]);

                return false;
            }
        }

        return false;
    }

    protected function constructRecurringPayload(Entity $subscription)
    {
        $subscriptionAmount = $subscription->getChargeableAmount();
        $customer = $subscription->customer;
        $tokenId = $subscription->token->getPublicId();

        $recurringPayload = [
            Payment\Entity::AMOUNT      => $subscriptionAmount,
            Payment\Entity::CURRENCY    => Payment\Entity::DEFAULT_CURRENCY,
            Payment\Entity::RECURRING   => '1',
            Payment\Entity::TOKEN       => $tokenId,
            Payment\Entity::CUSTOMER_ID => $customer->getPublicId(),
            Payment\Entity::EMAIL       => $customer->getEmail(),
            Payment\Entity::CONTACT     => $customer->getContact(),
            Payment\Entity::DESCRIPTION => 'Recurring Payment via Subscription'
        ];

        return $recurringPayload;
    }

    protected function associateEntitiesToSubscription(Entity $subscription, Plan\Entity $plan, Token\Entity $token)
    {
        $customer = $token->customer;
        $merchant = $customer->merchant;

        $subscription->merchant()->associate($merchant);
        $subscription->plan()->associate($plan);
        $subscription->customer()->associate($customer);
        $subscription->token()->associate($token);
    }
}