<?php

namespace RZP\Models\Plan\Subscription;

use App;
use Carbon\Carbon;

use RZP\Base;
use RZP\Exception;
use RZP\Exception\BadRequestValidationFailureException;

use RZP\Models\Invoice;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Plan\Cycle;

class Validator extends Base\Validator
{
    /**
     * Maximum number of addons that we allow
     * as part of the subscription creations
     */
    const MAX_ALLOWED_ADDONS = 5;

    const SECONDS_IN_ONE_YEAR = 31536000;

    /**
     * Used for getting the operation cancel to run the cancel rules.
     */
    const CANCEL = 'cancel';

    /**
     * Used for running the beforeCreate rules
     */
    const BEFORE_CREATE = 'before_create';

    protected static $createRules = [
        Entity::CUSTOMER_ID     => 'sometimes|string|size:19|public_id|nullable',
        Entity::PLAN_ID         => 'required|string|size:19|public_id',
        Entity::QUANTITY        => 'filled|integer|min:1|max:500',
        Entity::NOTES           => 'sometimes|notes',
        Entity::TOTAL_COUNT     => 'required_without:end_at|integer|min:1',
        Entity::START_AT        => 'sometimes|integer|custom|nullable',
        Entity::END_AT          => 'required_without:total_count|epoch',
        Entity::CUSTOMER_NOTIFY => 'sometimes|boolean',
        Entity::ADDONS          => 'sometimes|array|sequential_array|min:1|max:' . self::MAX_ALLOWED_ADDONS,
        Entity::ADDONS . '.*'   => 'sometimes|array|associative_array',
    ];

    protected static $beforeCreateRules = [
        Entity::CUSTOMER_ID     => 'sometimes|string|size:19|public_id|nullable',
        Entity::PLAN_ID         => 'required|string|size:19|public_id',
    ];

    protected static $cancelRules = [
        Entity::CANCEL_AT_CYCLE_END => 'filled|bool',
    ];

    protected static $createValidators = [
        Entity::TOTAL_COUNT,
        Entity::END_AT,
    ];

    protected static $manualTestChargeRules = [
        // Enforcing this because it makes things easier in constructRecurringPayload
        // of Subscription\Core. There, when deciding whether or not to send the
        // test_success flag, we can just use the fact that test_success is set in
        // the input. If we have a default value, it will need to be handled so as
        // to NOT send that default value in the usual charge cron payloads.
        'success' => 'required|boolean',
    ];

    public function validateEndAtAfterGenerating()
    {
        $subscription = $this->entity;

        $startAt = $subscription->getStartAt();
        $endAt = $subscription->getEndAt();

        $this->validateEndAtWithStartAt($startAt, $endAt);
    }

    public function validateStartAtForAuthTransaction()
    {
        $subscription = $this->entity;

        $startAt = $subscription->getStartAt();

        if ($startAt === null)
        {
            return;
        }

        $currentTime = Carbon::now()->getTimestamp();

        if ($startAt < $currentTime)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUBSCRIPTION_CURRENT_TIME_PAST_START_TIME,
                null,
                [
                    'start_at'          => $startAt,
                    'current_time'      => $currentTime,
                    'subscription_id'   => $subscription->getId(),
                ]);
        }
    }

    public function validateSubscriptionCancellable()
    {
        $subscription = $this->entity;

        $currentStatus = $subscription->getStatus();

        if ($subscription->isTerminalStatus() === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Subscription is not cancellable in ' . $currentStatus . ' status.',
                'status',
                [
                    'subscription_id' => $subscription->getId(),
                    'status'          => $currentStatus,
                ]);
        }
    }

    public function validateSubscriptionChargeable(Invoice\Entity $invoice, bool $manual)
    {
        $subscription = $this->entity;
        $valid = true;

        //
        // This will be empty only when valid is true,
        // in which case, we don't care about it's value.
        //
        $traceCode = '';

        if (($subscription->isTerminalStatus() === true) and ($manual === false))
        {
            $traceCode = TraceCode::SUBSCRIPTION_NOT_IN_CHARGEABLE_STATE;

            $valid = false;
        }
        //
        // This happens when two crons picked up the same invoice
        // and queued the charge on them.
        // If one of the queue picks it up first, it would have marked the
        // invoice as paid and now this queue gets executed.
        //
        else if ($invoice->isPaid() === true)
        {
            $traceCode = TraceCode::SUBSCRIPTION_INVOICE_ALREADY_PAID;

            $valid = false;
        }
        //
        // When a different cron picked up the invoice for a charge
        // and got queued, the status could have gone into
        // halted. If this happened, we should not attempt
        // to charge the subscription now.
        //
        else if (($invoice->getSubscriptionStatus() === Invoice\Status::HALTED) and
                 ($manual === false))
        {
            $traceCode = TraceCode::SUBSCRIPTION_INVOICE_HALTED;

            $valid = false;
        }

        return [$valid, $traceCode];
    }

    public function validateTestSubscriptionChargeable()
    {
        $subscription = $this->entity;

        if (($subscription->hasEnded() === true) or
            ($subscription->isManualTestChargeableStatus() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUBSCRIPTION_NOT_TEST_CHARGEABLE,
                'status',
                [
                    'subscription_id'       => $subscription->getId(),
                    'subscription_status'   => $subscription->getStatus(),
                ]);
        }
    }

    protected function validateEndAtWithStartAt(int $startAt, int $endAt)
    {
        if ($endAt < $startAt)
        {
            throw new Exception\BadRequestValidationFailureException(
                'end_at cannot be lesser than start_at.',
                null,
                [
                    'start_at'  => $startAt,
                    'end_at'    => $endAt,
                ]);
        }

        $maxSecondsFromStartAt = $startAt + (Entity::MAX_YEARS_ALLOWED_FOR_SUBSCRIPTION * self::SECONDS_IN_ONE_YEAR);

        if ($endAt > $maxSecondsFromStartAt)
        {
            throw new Exception\BadRequestValidationFailureException(
                'end_at should be within ' . Entity::MAX_YEARS_ALLOWED_FOR_SUBSCRIPTION . ' year/s of start_at.',
                null,
                [
                    'start_at'  => $startAt,
                    'end_at'    => $endAt,
                    'max_years' => Entity::MAX_YEARS_ALLOWED_FOR_SUBSCRIPTION,
                    'seconds'   => $maxSecondsFromStartAt,
                ]);
        }
    }

    protected function validateEndAt($input)
    {
        //
        // This is possible when total_count is sent in the input.
        //
        if (empty($input[Entity::END_AT]) === true)
        {
            return;
        }

        if (empty($input[Entity::TOTAL_COUNT]) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_END_AT_AND_TOTAL_COUNT_SENT,
                null,
                [
                    'end_at'        => $input[Entity::END_AT],
                    'total_count'   => $input[Entity::TOTAL_COUNT],
                ]);
        }

        $startAt = $input[Entity::START_AT];
        $endAt = $input[Entity::END_AT];

        $this->validateEndAtWithStartAt($startAt, $endAt);
    }

    protected function validateStartAt($attribute, $value)
    {
        $currentTime = Carbon::now()->getTimestamp();

        if ($value < $currentTime)
        {
            throw new Exception\BadRequestValidationFailureException(
                'start_at cannot be lesser than the current time.',
                null,
                [
                    'start_at'      => $value,
                    'current_time'  => $currentTime,
                ]);
        }

        $maxSecondsAllowedForSubscription = Entity::MAX_YEARS_ALLOWED_FOR_SUBSCRIPTION * self::SECONDS_IN_ONE_YEAR;

        $maxSecondsFromCurrentTime = $currentTime + $maxSecondsAllowedForSubscription;

        if ($value > $maxSecondsFromCurrentTime)
        {
            throw new Exception\BadRequestValidationFailureException(
                'start_at must be less than ' . Entity::MAX_YEARS_ALLOWED_FOR_SUBSCRIPTION . ' year/s from now.',
                null,
                [
                    'start_at'  => $value,
                    'max_year'  => Entity::MAX_YEARS_ALLOWED_FOR_SUBSCRIPTION,
                    'seconds'   => $maxSecondsFromCurrentTime,
                ]);
        }
    }

    protected function validateTotalCount($input)
    {
        //
        // This is possible when end_at is sent in the input.
        //
        if (empty($input[Entity::TOTAL_COUNT]) === true)
        {
            return;
        }

        if (empty($input[Entity::END_AT]) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_END_AT_AND_TOTAL_COUNT_SENT,
                null,
                [
                    'end_at'        => $input[Entity::END_AT],
                    'total_count'   => $input[Entity::TOTAL_COUNT],
                ]);
        }

        $totalCount = $input[Entity::TOTAL_COUNT];

        $subscription = $this->entity;
        $plan = $subscription->plan;

        $period = $plan->getPeriod();
        $interval = $plan->getInterval();

        $maxAllowedTotalCount = Cycle::getMaxAllowedTotalCount($plan);

        if ($totalCount > $maxAllowedTotalCount)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Exceeds the maximum total_count (' . $maxAllowedTotalCount . ') allowed for the given period and interval',
                null,
                [
                    'period'        => $period,
                    'interval'      => $interval,
                    'max_allowed'   => $maxAllowedTotalCount,
                    'input'         => $input,
                ]);
        }
    }

    public function validateSubscriptionViewable()
    {
        $subscription   = $this->entity;

        if ($subscription->isGlobal() === false)
        {
            $message = 'Hosted page is not available. Please contact the merchant for further details.';
            throw new BadRequestValidationFailureException($message);
        }
    }
}
