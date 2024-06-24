<?php

namespace RZP\Models\Base;

use Carbon\Carbon;
use InvalidArgumentException;

use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Entity as ConstantEntity;
use RZP\Models\Currency;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Trace\TraceCode;

class Utility
{
    /**
     * Formats allowed for human readable date time values in files
     * @var array
     */
    public static $allowedDateFormats = [
        'd-m-Y H:i:s',
        'd-m-Y H:i',
        'd-m-Y',
        'd-m-y',
        'd/m/Y H:i:s',
        'd/m/Y H:i',
        'd/m/Y',
        'd/m/y',
    ];

    public static function isUpdatedAndroidSdk($input)
    {
        if ((isset($input['_'])) and
            (isset($input['_']['platform'])) and
            ($input['_']['platform'] === 'android') and
            (isset($input['_']['library'])) and
            ($input['_']['library'] === 'checkoutjs') and
            (isset($input['_']['version'])) and
            (version_compare($input['_']['version'], '1.0.0') >= 0))
        {
            return true;
        }

        return false;
    }

    /**
     * Parse a value as epoch or fails
     * @param  int|string|null $value
     * @return int|null
     * @throws BadRequestValidationFailureException
     */
    public static function parseAsEpoch($value)
    {
        if (empty($value) === true)
        {
            return $value;
        }

        if (is_numeric($value) === true)
        {
            return (int) $value;
        }

        return self::createCarbonFromAllowedFormats($value)->getTimestamp();
    }

    /**
     * Creates Carbon instance for given value against allowed formats
     * @param  string $value
     * @return Carbon
     * @throws BadRequestValidationFailureException
     */
    public static function createCarbonFromAllowedFormats($value): Carbon
    {
        foreach (self::$allowedDateFormats as $allowedDateFormat)
        {
            try
            {
                return Carbon::createFromFormat($allowedDateFormat, $value, Timezone::IST);
            }
            // Above operation either returns valid Carbon instance else throws InvalidArgumentException (i.e. failed)
            catch (InvalidArgumentException $e)
            {
            }
        }

        throw new BadRequestValidationFailureException(
            "Date/time value is not in correct format: {$value}",
            null,
            compact('value'));
    }

    public static function getTimestampFormatted($epoch, $format)
    {
        return date($format, $epoch);
    }

    public static function getTimestampFormattedByTimeZone($epoch, $format, $timeZone)
    {
        $timeStamp = Carbon::createFromTimestamp($epoch, $timeZone);

        return  $timeStamp->format($format);
    }


    public static function getAmountComponents($amount, $currency)
    {
        $currencySymbol = Currency\Currency::SYMBOL[$currency] ?: 'INR';

        $denominationFactor = Currency\Currency::DENOMINATION_FACTOR[$currency] ?: 100;

        $superUnitInAmount = money_format_IN((integer)($amount / $denominationFactor));

        $subUnitInAmount = str_pad($amount % $denominationFactor, 2, 0, STR_PAD_LEFT);

        return [$currencySymbol, $superUnitInAmount, $subUnitInAmount];
    }

    public static function getCombinations($arrays)
    {
        $result = array(array());

        foreach ($arrays as $property => $property_values)
        {
            $tmp = array();

            foreach ($result as $result_item)
            {
                foreach ($property_values as $property_value)
                {
                    $tmp[] = array_merge($result_item, array($property => $property_value));
                }
            }

            $result = $tmp;
        }
        return $result;
    }

    // note: this is a temporary fix to solve the following issue: https://razorpay.slack.com/archives/C027FDDSZ0F/p1716188016901139
    // context: only for the race condition pattern we are observing all 3 columns: activated_at, activated, live to get unset.
    // hence to handle the race condition, we will override this behaviour behind an experiment after log verification.
    // during regular deactivate: only activated, live are unset.
    public function overrideDeactivateIfApplicable($entity)
    {
        try {
            if ($entity->getEntityName() !== ConstantEntity::MERCHANT) {
                return;
            }

            $dirtyChanges = $entity->getDirty();

            $originalData = $entity->getOriginal();

            if ((count($dirtyChanges) > 0)
                and array_key_exists(MerchantEntity::ACTIVATED_AT, $dirtyChanges) === true
                and array_key_exists(MerchantEntity::ACTIVATED_AT, $originalData) === true
                and array_key_exists(MerchantEntity::ACTIVATED, $dirtyChanges) === true
                and array_key_exists(MerchantEntity::ACTIVATED, $originalData) === true
                and array_key_exists(MerchantEntity::LIVE, $dirtyChanges) === true
                and array_key_exists(MerchantEntity::LIVE, $originalData) === true
                and $originalData[MerchantEntity::ACTIVATED_AT] !== null
                and $dirtyChanges[MerchantEntity::ACTIVATED_AT] === null
                and $originalData[MerchantEntity::ACTIVATED] === true
                and $dirtyChanges[MerchantEntity::ACTIVATED] === 0
                and $originalData[MerchantEntity::LIVE] === true
                and $dirtyChanges[MerchantEntity::LIVE] === 0
            ) {
                app('trace')->info(TraceCode::ACTIVATION_FIELDS_UNSET, [
                    'dirty_changes' => $dirtyChanges,
                    'merchant_id' => $entity->getId(),
                    'original_data' => $originalData,
                ]);

                $this->getDebugBackTrace();

                if ((new Merchant\Core)->isSplitzExperimentEnable(
                        [
                            'id' => app('request')->getTaskId(),
                            'experiment_id' => app('config')->get('app.override_deactivate_experiment_id'),
                        ],
                        'enable'
                    ) === false) {
                    return;
                }

                $entity->setAttribute(MerchantEntity::ACTIVATED_AT, $originalData[MerchantEntity::ACTIVATED_AT]);
                $entity->setAttribute(MerchantEntity::ACTIVATED, $originalData[MerchantEntity::ACTIVATED]);
                $entity->setAttribute(MerchantEntity::LIVE, $originalData[MerchantEntity::LIVE]);
            }
        }
        catch (\Throwable $exception)
        {
            app('trace')->traceException($exception, Trace::ERROR, TraceCode::ACTIVATION_FIELDS_UNSET_ERROR);
        }
    }

    public function getDebugBackTrace(): void
    {
        try {
            $route = $this->getRouteOrJobName();

            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

            $traceInfo = [];

            foreach ($backtrace as $trace) {
                if (isset($trace['file']) && !str_contains($trace['file'], 'vendor/')) {
                    $traceInfo[] = [
                        'line' => $trace['line'],
                        'function' => $trace['function'] ?? 'N/A',
                        'class' => $trace['class'] ?? 'N/A',
                    ];
                }
            }

            app('trace')->info(TraceCode::ACTIVATION_FIELDS_UNSET_DEBUG, [
                "trace" => $traceInfo,
                "route" => $route
            ]);
        } catch (\Exception $e) {
            app('trace')->traceException($e,
                null,
                TraceCode::ACS_ROUTE_QUERY_LOGS_EXCEPTION
            );
        }
    }

    public function getRouteOrJobName()
    {
        try {
            $runningInQueue = app()->runningInQueue();
            if ($runningInQueue === true) {
                $flow = app('worker.ctx')->getJobName();
            } else {
                $flow = app('request.ctx')->getRoute();
            }

            if ($flow === null or $flow === "") {
                return "none";
            }

            return $flow;

        } catch (\Exception $e) {
            $this->trace->traceException($e, Trace::WARNING, TraceCode::ASV_ROLLBACK_GET_ROUTE_OR_WORKER_NAME_EXCEPTION);
            return "none";
        }
    }
}
