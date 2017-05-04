<?php

namespace RZP\Models\Plan\Subscription;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Plan;
use RZP\Models\Customer;
use RZP\Models\AddOn;
use RZP\Models\Schedule;
use RZP\Models\Schedule\Task;

class Creator extends Base\Core
{
    public function create(array $input, Plan\Entity $plan, Customer\Entity $customer)
    {
        $subscription = (new Entity)->build($input);

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
                // This should be called before filling end_at and total_count,
                // since they require the schedule to be created first.
                //
                $this->createScheduleAndTask($subscription, $plan);

                (new Core)->fillEndAtAndTotalCount($subscription, $plan);

                $this->repo->saveOrFail($subscription);

                //
                // This needs to be done after saving the subscription
                // because invoice/add_on is created and saved in the
                // following step, with the subscription_id.
                //

                $this->createAddOnsIfApplicable($subscription, $input);

                $this->createInvoiceIfApplicable($subscription);
            });

        return $subscription;
    }

    protected function createAddOnsIfApplicable(Entity $subscription, array $input)
    {
        if (empty($input[Entity::ADD_ONS]) === true)
        {
            return;
        }

        $addOnsInput = $input[Entity::ADD_ONS];

        $addOnCore = (new AddOn\Core);

        foreach ($addOnsInput as $addOnInput)
        {
            $addOnCore->create($addOnInput, $subscription);
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
        $addOns = $this->repo->add_on->getUnusedAddOnsForSubscription($subscription);

        if (($addOns->count() === 0) and
            ($subscription->getStartAt() !== null))
        {
            return;
        }

        (new Billing)->createInvoiceForSubscription($subscription, $addOns, true);
    }

    protected function createScheduleAndTask(Entity $subscription, Plan\Entity $plan)
    {
        $schedule = $this->createSchedule($subscription, $plan);

        $subscription->schedule()->associate($schedule);

        $this->createTask($subscription);
    }

    protected function createSchedule(Entity $subscription, Plan\Entity $plan)
    {
        $scheduleInput = [
            Schedule\Entity::NAME       => $plan->item->getName(),
            Schedule\Entity::INTERVAL   => $plan->getInterval(),
            Schedule\Entity::PERIOD     => $plan->getPeriod(),
        ];

        if ($subscription->getStartAt() !== null)
        {
            $scheduleInput[Schedule\Entity::ANCHOR] = $subscription->getAnchorForSchedule();
        }

        $schedule = (new Schedule\Core)->createSchedule($scheduleInput);

        return $schedule;
    }

    protected function createTask(Entity $subscription)
    {
        $schedule = $subscription->schedule;

        $taskInput = [
            Task\Entity::METHOD         => null,
            Task\Entity::TYPE           => Task\Type::SUBSCRIPTION,
            Task\Entity::SCHEDULE_ID    => $schedule->getId(),
            Task\Entity::NEXT_RUN_AT    => $subscription->getStartAt(),
        ];

        (new Task\Core)->createOrUpdate($subscription->merchant, $subscription, $taskInput);
    }
}

