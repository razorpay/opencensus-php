<?php

namespace RZP\Models\Plan\Subscription;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Plan;
use RZP\Models\Customer;
use RZP\Models\Plan\Subscription\Addon;
use RZP\Models\Schedule;
use RZP\Models\Schedule\Task;

class Creator extends Base\Core
{
    /**
     * @param array                 $input
     * @param Plan\Entity           $plan
     * @param Customer\Entity|null  $customer This is not type hinted because customer can be null
     *                                        also, in case the merchant wants to follow global
     *                                        customer flow.
     *
     * @return Entity
     * @throws \Exception
     */
    public function create(array $input, Plan\Entity $plan, Customer\Entity $customer = null): Entity
    {
        $subscription = (new Entity);

        //
        // Transaction on live and test is required because
        // schedule is created in both live and test.
        //
        $this->repo->transactionOnLiveAndTest(
            function() use ($subscription, $plan, $customer, $input)
            {
                // This is being done for the `schedule` and `task` associations.
                $subscription->generateId();

                //
                // This should be called before creating task since it requires
                // merchant to associated with the subscription first.
                //
                $subscription->associateEntities($plan, $customer);

                //
                // This needs to be done after associating the entities
                // since create validations need to access the
                // corresponding plan's attributes.
                //
                $subscription->build($input);

                //
                // This should be after build because any of these
                // keys have defaults present in the entity class
                // then build will override any previously set values
                //
                if ($customer !== null)
                {
                    $subscription->setGlobalCustomer(false);

                    $subscription->setCustomerEmail($customer->getEmail());
                }

                //
                // This should be called before filling end_at and total_count,
                // since they require the schedule to be created first.
                //
                $this->createScheduleAndTask($subscription, $plan);

                $this->fillEndAtAndTotalCount($subscription, $plan);

                $this->repo->saveOrFail($subscription);

                //
                // This needs to be done after saving the subscription
                // because invoice/addon is created and saved in the
                // following step, with the subscription_id.
                //

                $this->createAddonsIfApplicable($subscription, $input);

                $this->createInvoiceIfApplicable($subscription);
            });

        return $subscription;
    }

    public function fillEndAtAndTotalCount(Entity $subscription, Plan\Entity $plan)
    {
        $startAt = $subscription->getStartAt();

        //
        // We get the start_at at the time of first charge.
        // We fill end_at and total_count at that time.
        //
        if ($startAt === null)
        {
            return;
        }

        if ($subscription->getTotalCount() === null)
        {
            $this->calculateAndSetTotalCount($subscription);
        }
        else if ($subscription->getEndAt() === null)
        {
            $this->calculateAndSetEndAt($subscription);

            $subscription->getValidator()->validateEndAtAfterGenerating();
        }
        else
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_END_AT_AND_TOTAL_COUNT_SENT,
                null,
                [
                    'subscription_id'   => $subscription->getId(),
                    'plan_id'           => $plan->getId(),
                    'total_count'       => $subscription->getTotalCount(),
                    'end_at'            => $subscription->getEndAt(),
                ]);
        }
    }

    protected function createAddonsIfApplicable(Entity $subscription, array $input)
    {
        if (empty($input[Entity::ADDONS]) === true)
        {
            return;
        }

        $addonsInput = $input[Entity::ADDONS];

        $addonCore = (new Addon\Core);

        foreach ($addonsInput as $addonInput)
        {
            $addonCore->create($addonInput, $subscription);
        }
    }

    /**
     * We create an invoice only if the auth transaction includes the
     * first charge also. This invoice will be used when the payment
     * for the auth txn (first charge) is made.
     *
     * If the auth txn also includes the upfront_amount, the invoice
     * will be made for plan_amount + upfront_amount.
     *
     * @param Entity $subscription
     */
    protected function createInvoiceIfApplicable(Entity $subscription)
    {
        $addons = $this->repo->addon->getUnusedAddonsForSubscription($subscription);

        if (($addons->count() === 0) and
            ($subscription->getStartAt() !== null))
        {
            return;
        }

        (new Biller)->createInvoiceForSubscription($subscription, $addons, true);
    }

    protected function createScheduleAndTask(Entity $subscription, Plan\Entity $plan)
    {
        $schedule = $this->createSchedule($subscription, $plan);

        $subscription->schedule()->associate($schedule);

        $this->createTask($subscription);
    }

    protected function createSchedule(Entity $subscription, Plan\Entity $plan): Schedule\Entity
    {
        $scheduleInput = [
            // Harman (Chief Master Namer) says this is how we should name it.
            Schedule\Entity::NAME       => $plan->getInterval() . '/' . $plan->getPeriod(),
            Schedule\Entity::INTERVAL   => $plan->getInterval(),
            Schedule\Entity::PERIOD     => $plan->getPeriod(),
            Schedule\Entity::TYPE       => Schedule\Type::SUBSCRIPTION
        ];

        if ($subscription->getStartAt() !== null)
        {
            $scheduleInput[Schedule\Entity::ANCHOR] = $subscription->getAnchorForSchedule();
            $scheduleInput[Schedule\Entity::NAME] .= '/' . $scheduleInput[Schedule\Entity::ANCHOR];
        }

        //
        // Currently, a new schedule is created for every subscription
        // even if a schedule already exists with the same interval,
        // period, anchor, delay, etc.
        //
        $schedule = (new Schedule\Core)->createSchedule($scheduleInput);

        return $schedule;
    }

    protected function createTask(Entity $subscription): Task\Entity
    {
        $schedule = $subscription->schedule;

        $taskInput = [
            Task\Entity::METHOD         => null,
            Task\Entity::TYPE           => Task\Type::SUBSCRIPTION,
            Task\Entity::SCHEDULE_ID    => $schedule->getId(),
            Task\Entity::NEXT_RUN_AT    => $subscription->getStartAt(),
        ];

        $task = (new Task\Core)->createOrUpdate($subscription->merchant, $subscription, $taskInput);

        return $task;
    }

    protected function calculateAndSetEndAt(Entity $subscription)
    {
        $endAt = Plan\Cycle::getEndTimeForGivenTotalCount($subscription);

        $subscription->setEndAt($endAt);
    }

    protected function calculateAndSetTotalCount(Entity $subscription)
    {
        $totalCount = Plan\Cycle::getTotalCountForGivenInterval($subscription);

        $subscription->setTotalCount($totalCount);
    }
}
