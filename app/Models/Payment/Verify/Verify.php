<?php

namespace RZP\Models\Payment\Verify;

use App;
use Config;
use Carbon\Carbon;
use RZP\Exception;
use RedisDualWrite;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Diag\EventCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Models\Card\Network;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\Processor\Verify as VerifyTrait;

class Verify extends Base\Core
{
    use VerifyTrait;
    // ================== Configurations ==================

    /**
     * For all the payments which are in failed state,
     * verify for the payment will be run once for every boundary bucket.
     * For each bucket, we define the time boundary here.
     *
     * Initially, payment's default bucket value is null.
     * When the payment moves to failed state the bucket is set to 0.
     *
     * Then the failed filter verify cron will pick up
     * all failed payments older than 'self::FAILURE_MIN_TIME'.
     *
     * So, a failed payment with bucket 0 and older than two minutes gets picked
     * by the cron for verification. After verify, the bucket is set to 1.
     *
     * When the cron runs again, it will pick this payment if bucket is 1
     * and it is older than bucket 0's end time which currently is set to 15 mins.
     * We verify it and move it to bucket 2. And this cycle keeps repeating
     */
    protected static $failureStartBoundary = [
        0 => 720,       // 12 Minutes
        1 => 1800,      // 30 Minutes
        2 => 7200,      // 2 hours
        3 => 21600,     // 6 hours
        4 => 86400,     // 1 Day
        5 => 172800,    // 2 Day
        6 => 259200,    // 3 Day
        7 => 345600,    // 4 Day
    ];

    /***
     * For all the payments which are in failed state,
     * verify for the payment will run once and then will
     * take a wait for the time according to the bucket
     * specified below for next verify.
     *
     * Initially, verify_at for any payment is set as created_at + 120
     * Hence any payment in failed or created state is picked up after 2 min of
     * creation and then we keep on adding wait time to verify_at.
     *
     * Hence a failed payment can have bucket update as :
     * CURR :- adding same wait time as the current bucket to verify_at in
     *         case of verify needs to be called in same bucket again ex:-
     *         gateway return error code for retry again or verify is blocked
     *         and needs to be retried
     * NEXT :- adding wait time corresponding to next verify_bucket to verify_at
     *         when gateway return verify status as success so verify can be moved to
     *         next bucket
     * LAST :- We set verify_bucket >= 8 hence verify_at is set to null. This is used
     *         when we know for sure that payment doesn't need to be verified again.
     *         For example gateway returns payment status code as failed due to insufficient funds.
     */
    protected static $updateWaitBoundaries = [
        0 => 600,       // 10 Minutes
        1 => 600,       // 10 Minutes
        2 => 1080,      // 18 Minutes
        3 => 5400,      // 90 Minutes
        4 => 14400,     // 4 Hours
        5 => 64800,     // 18 Hours
        6 => 86400,     // 24 Hours
        7 => 86400,     // 24 Hours
        8 => 86400,     // 24 Hours
    ];

    /**
     * This is used for naming the redis lock key.
     * It's named as {payment_id}_verify.
     * We do not use the payment_id directly because
     * it's already being used in the core flows of refund and capture.
     */
    const KEY_SUFFIX = '_verify';

    /**
     * This is the minimum time for which the payment should be in
     * created state, before we run a "created" verify on it.
     */
    const CREATED_MIN_TIME = 120;  // 2 Minutes

    /**
     * This is the maximum time for which the payment should be in
     * created state, before we run a "created" verify on it.
     * After that created payments, follow boundary rule
     */
    const CREATED_MAX_TIME = 720;  // 12 Minutes

    /**
     * This is the minimum time for which the payment should be in
     * failed state, before we run verify on it.
     */
    const FAILURE_MIN_TIME = 120;  // 2 Minutes

    /**
     * This is the minimum time for which the payment verify status
     * should be in non-success state, before we run a "failed/error" verify on it.
     */
    const ERRORED_MIN_TIME = 0; // 0 Minute

    /**
     * This is the time for which payments will be locked via acquireMultiple
     * After this time, the keys will be released
     */
    const DEFAULT_LOCK_TIME = 900; // 15 Minutes

    /**
     * We need to block gateway from verify after certain error codes are returned,
     * The block will be lifted after duration mentioned here
     */
    const GATEWAY_BLOCK_TIME = 1800; // 30 minutes

    /**
     * Time interval after which timeout count will be reset
     */
    const GATEWAY_TIMEOUT_BUCKET_INTERVAL = 600; // 10 minutes

    /**
     * No of Timeout that should occur in GATEWAY_TIMEOUT_BUCKET_INTERVAL
     * for gateway to be blocked
     */
    const GATEWAY_TIMEOUT_THRESHOLD = 10;

    /**
     * No of Request Error that should occur in GATEWAY_TIMEOUT_BUCKET_INTERVAL
     * for gateway to be blocked
     */
    const GATEWAY_REQUEST_ERROR_THRESHOLD = 100;

    /**
     * Cache key prefix for storing gateway timeout values
     */
    const GATEWAY_TIMEOUT_CACHE_KEY_PREFIX = 'verify:verify_timeout_block';

    /**
     * Cache key prefix for storing gateway timeout values
     */
    const GATEWAY_REQUEST_ERROR_CACHE_KEY_PREFIX = 'verify:verify_request_error_block';

    /**
     * Cache key used to store gateway block info in hash map
     */
    const GATEWAY_BLOCK_CACHE_KEY = 'verify:gateway_block_cache';

    /**
     * Constant to signify that Verify Bucket should be updated with next boundary value
     * This should be used when we want to run verify on given payment in next run
     */
    const NEXT = 'next';

    /**
     * Constant to signify that Verify Bucket should be updated with last boundary value
     * This should be used when we want to disable verify for a given payment
     */
    const LAST = 'last';

    /**
     * Constant to signify that Verify Bucket should remain same
     * This should be used when we want to retry the payment in same bucket
     */
    const CURR = 'curr';

    /**
     * Minimum duration a payment should be old before it gets picked up
     * for verify for a particular payment verify filter.
     */
    const MINIMUM_TIME_MAP = [
        Filter::PAYMENTS_FAILED     => self::FAILURE_MIN_TIME,
        Filter::PAYMENTS_CREATED    => self::CREATED_MIN_TIME,
        Filter::VERIFY_FAILED       => self::ERRORED_MIN_TIME,
        Filter::VERIFY_ERROR        => self::ERRORED_MIN_TIME,
    ];

    /**
     * Maximum duration after which payments will be picked,
     * according to their verify_bucket,
     * Cuurently used for CREATED payments only
     */
    const MAXIMUM_TIME_MAP = [
        Filter::PAYMENTS_FAILED     => null,
        Filter::PAYMENTS_CREATED    => self::CREATED_MAX_TIME,
        Filter::VERIFY_FAILED       => null,
        Filter::VERIFY_ERROR        => null,
    ];
    /**
     * Max number of payments on which single instance of verify cron should operate
     */
    const ROWS_TO_FETCH = 100;

    /**
     * Threshold for which logs should be posted to slack
     */
    const LOGGING_THRESHOLD = 30;

    // ================== End Configurations ==================

    protected $core;
    protected $mutex;
    protected $redis;
    protected $slack;
    protected $route;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->redis = $this->app['redis']->connection('mutex_redis');

        $this->slack = $this->app['slack'];

        $this->route = $this->app['api.route']->getCurrentRouteName();
    }

    /**
     * Verify Payments Based on filter and Bucket filter, if provided
     * For detailed Documentation refer to
     * https://docs.google.com/document/d/128BT3KYBRloYR85zaZODB5htUmG8JrGKKP6eGAGgW68
     *
     * @param  string $filter
     * @param  array  $bucketFilter
     * @return array aggregated result of verify results
     *               Sample Result
     *              [
     *                  'filter'            => <filter>,
     *                  'verified'          => <count>,
     *                  'authorized/failed' => <count>,
     *                  'timed out'         => <count>,
     *                  'error'             => <count>,
     *                  'authorizedTime'    => <time>,
     *                  'totalTime'         => <time>
     *              ]
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     */
    public function verifyPaymentsWithFilter(string $filter, array $bucketFilter, string $gateway = null)
    {
        // TODO :- Remove all the time boundary based checks from the query once we move to verify_at based query
        $verifyFetchStartTime = time();

        $timeBoundary = $this->getStartAndEndTimeForVerify($filter);

        $paymentStatus = $this->getPaymentStatusForFilter($filter);

        $verifyStatus = $this->getVerifyStatusForFilter($filter);

        $boundary = $this->getBoundaryForVerify($filter, $bucketFilter, $timeBoundary);

        $disabledGateways = $this->getBlockedGateways();

        if (($gateway !== null) and
            (in_array($gateway, $disabledGateways, true) === true))
        {
            return ['message' => 'no payment to verify'];
        }

        //
        // We Fetch Twice the number of required payments,
        // and filtering extra payments in later stage
        //
        $paymentsCollectionWithCount = $this->repo->payment->getPaymentsToVerify(
                                                                $timeBoundary,
                                                                $boundary,
                                                                $verifyStatus,
                                                                $paymentStatus,
                                                                self::ROWS_TO_FETCH * 2,
                                                                $gateway,
                                                                $disabledGateways);

        $payments = $paymentsCollectionWithCount['payments'];

        $verifiableCount = $paymentsCollectionWithCount['verifiable_count'];

        $verifyFetchEndTime = time();

        $verifyFetchTime = $verifyFetchEndTime - $verifyFetchStartTime;

        return $this->verifyMultiplePayments($payments, $filter, $bucketFilter, $verifiableCount, $verifyFetchTime);
    }

    public function verifyAllPayments($timestamps, $gateway, $count, $bucket, $filterPaymentPushedToKafka = true)
    {
        $verifyFetchStartTime = Carbon::now()->getTimestamp();

        $disabledGateways = $this->getBlockedGateways();

        $payments = $this->repo->payment->getPaymentsToVerifyByGatewayAndTime($timestamps, $gateway, $count,
                            $disabledGateways, $bucket, [Payment\Status::FAILED, Payment\Status::CREATED], $filterPaymentPushedToKafka);

        $verifyFetchEndTime = Carbon::now()->getTimestamp();

        $verifyFetchTime = $verifyFetchEndTime - $verifyFetchStartTime;

        list($summary, $resultSet) = $this->verifyFilteredPayments($payments);

        $summary['start_time'] = $verifyFetchStartTime;
        $summary['end_time'] = $verifyFetchEndTime;
        $summary['fetch_time'] = $verifyFetchTime;

        $summary = array_merge($summary, $resultSet);

        $this->trace->info(TraceCode::VERIFY_PROCESSED_SUMMARY, $summary);

        return $summary;
    }

    public function fetchFailedAndCreatedPayments($timestamps, $gateway, $count, $bucket, $useSlave, $disabledGateways, $filterPaymentPushedToKafka)
    {
        if ($useSlave === true)
        {
            $payments = $this->repo->useSlave( function() use ($timestamps, $gateway, $count, $bucket, $disabledGateways, $filterPaymentPushedToKafka)
            {
                return $this->repo->payment->getPaymentsToVerifyByGatewayAndTime($timestamps, $gateway, $count,
                                            $disabledGateways, $bucket, [Payment\Status::FAILED, Payment\Status::CREATED], $filterPaymentPushedToKafka);
            });
        }
        else
        {
            $payments = $this->repo->payment->getPaymentsToVerifyByGatewayAndTime($timestamps, $gateway, $count,
                                            $disabledGateways, $bucket, [Payment\Status::FAILED, Payment\Status::CREATED], $filterPaymentPushedToKafka);
        }

        return $payments;
    }

    public function verifyAllPaymentsNewRoute($timestamps, $gateway, $count, $bucket, $useSlave, $filterPaymentPushedToKafka)
    {
        $verifyFetchStartTime = Carbon::now()->getTimestamp();

        $disabledGateways = $this->getBlockedGateways();

        $payments = $this->fetchFailedAndCreatedPayments($timestamps, $gateway, $count, $bucket, $useSlave, $disabledGateways, $filterPaymentPushedToKafka);

        $payments = $this->filterPaymentsWithFinalErrorCode($payments);

        $verifyFetchEndTime = Carbon::now()->getTimestamp();

        $verifyFetchTime = $verifyFetchEndTime - $verifyFetchStartTime;

        list($summary, $resultSet) = $this->verifyFilteredPaymentsNewRoute($payments);

        $summary['start_time'] = $verifyFetchStartTime;
        $summary['end_time'] = $verifyFetchEndTime;
        $summary['fetch_time'] = $verifyFetchTime;

        $summary = array_merge($summary, $resultSet);

        $this->trace->info(TraceCode::VERIFY_PROCESSED_SUMMARY, $summary);

        return $summary;
    }

    public function verifyCapturedPayments($timestamps, $gateway, $count, $bucket, $filterPaymentPushedToKafka)
    {
        $verifyFetchStartTime = Carbon::now()->getTimestamp();

        $disabledGateways = $this->getBlockedGateways();

        $payments = $this->repo->payment->getPaymentsToVerifyByGatewayAndTime($timestamps, $gateway, $count,
            $disabledGateways, $bucket,  [Payment\Status::CAPTURED], $filterPaymentPushedToKafka);

        $verifyFetchEndTime = Carbon::now()->getTimestamp();

        $verifyFetchTime = $verifyFetchEndTime - $verifyFetchStartTime;

        list($summary, $resultSet) = $this->doVerifyCapturedPayments($payments);

        $summary['start_time'] = $verifyFetchStartTime;
        $summary['end_time'] = $verifyFetchEndTime;
        $summary['fetch_time'] = $verifyFetchTime;

        $summary = array_merge($summary, $resultSet);

        $this->trace->info(TraceCode::CAPTURED_VERIFY_PROCESSED_SUMMARY, $summary);

        return $summary;
    }

    protected function filterPaymentsWithFinalErrorCode(Base\PublicCollection $payments) : Base\PublicCollection
    {
        $this->trace->info(TraceCode::PAYMENT_VERIFY_FILTER,
            [
                'payments' => $payments->getIds(),
            ]);

        $pays = $payments->filter(function (Payment\Entity $payment) {

            if ($payment->getStatus() === Payment\Status::CAPTURED)
            {
                return true;
            }

            $isFinalErrorCode = $this->isFinalErrorCode($payment);

            return !$isFinalErrorCode;
        });

        $this->trace->info(TraceCode::PAYMENT_VERIFY_FILTER,
            [
                'after_filtering_payments' => $pays->getIds(),
            ]);

        return new Base\PublicCollection($pays);
    }

    protected function verifyFilteredPayments($payments)
    {
        $resultSet = [
            Result::AUTHORIZED    => 0,
            Result::SUCCESS       => 0,
            Result::TIMEOUT       => 0,
            Result::ERROR         => 0,
            Result::UNKNOWN       => 0,
            Result::REQUEST_ERROR => 0,
        ];

        $notApplicable = $locked = 0;

        $totalAuthTimeDiff = $avgAuthTime = 0;

        $verifyStart = Carbon::now(Timezone::IST)->timestamp;

        foreach ($payments as $payment)
        {
            $gateway = $payment->getGateway();

            if ($this->isGatewayBlocked($gateway) === true)
            {
                $notApplicable++;

                continue;
            }

            $lock = $this->lockPaymentForVerify($payment);

            if ($lock === false)
            {
                $locked++;

                continue;
            }

            $this->repo->reload($payment);

            // We ignore all the payments which has been authorized
            // but not captured
            if (($payment->hasBeenAuthorized() === true) and
                ($payment->hasBeenCaptured() === false))
            {
                $payment->setNonVerifiable();

                $this->repo->saveOrFail($payment);

                $notApplicable++;

                $this->releasePaymentAfterVerify($payment);

                continue;
            }

            // There could be case where payment is already verified by some other thread,
            // hence check the verify_at after reload
            if ($verifyStart < $payment->getVerifyAt())
            {
                $notApplicable++;

                $this->releasePaymentAfterVerify($payment);

                continue;
            }

            $verifyResult = null;

            //Payment has already been capture, will be verified by VerifyCapturePayments cron.
            if($payment->hasBeenCaptured() === true)
            {
                $notApplicable++;

                $this->releasePaymentAfterVerify($payment);

                continue;
            }

            $filter = ($payment->isCreated() === true) ? Filter::PAYMENTS_CREATED : Filter::PAYMENTS_FAILED;

            $verifyResult = $this->verifyPayment($payment, $filter);

            if ($verifyResult !== null)
            {
                $resultSet[$verifyResult] += 1;

                if ($verifyResult === Result::AUTHORIZED)
                {
                    $totalAuthTimeDiff += (time() - $payment->getCreatedAt());

                    $avgAuthTime = $totalAuthTimeDiff/$resultSet[Result::AUTHORIZED];
                }
            }
            else
            {
                $notApplicable++;
            }

            $this->releasePaymentAfterVerify($payment);
        }

        $verifyEnd = time();

        $totalVerifyTime = $verifyEnd - $verifyStart;

        $summary = [
            'total_time'     => $totalVerifyTime,
            'authorize_time' => $avgAuthTime,
            'not_applicable' => $notApplicable,
            'locked_count'   => $locked,
        ];

        return [$summary, $resultSet];
    }

    protected function verifyFilteredPaymentsNewRoute(Base\PublicCollection $payments)
    {
        $totalPaymentsCount = $payments->count();

        $resultSet = [
            Result::AUTHORIZED    => 0,
            Result::SUCCESS       => 0,
            Result::TIMEOUT       => 0,
            Result::ERROR         => 0,
            Result::UNKNOWN       => 0,
            Result::REQUEST_ERROR => 0,
        ];

        $notApplicable = $locked = 0;

        $totalAuthTimeDiff = $avgAuthTime = 0;

        $verifyStart = Carbon::now(Timezone::IST)->timestamp;

        $attemptedPayments = 0;

        foreach ($payments as $payment)
        {
            $gateway = $payment->getGateway();

            if ($this->isGatewayBlocked($gateway) === true)
            {
                $this->trace->info(TraceCode::PAYMENT_VERIFICATION_GATEWAY_BLOCKED, [
                    'payment_id' => $payment->getId(),
                    'gateway'    => $payment->getGateway(),
                ]);

                $customProperties = [
                    'is_pushed_to_kafka'  => $payment->getIsPushedToKafka(),
                    'gateway'             => $payment->getGateway(),
                ];

                $this->app['diag']->trackVerifyPaymentEvent(EventCode::PAYMENT_VERIFICATION_PAYMENT_BLOCKED, $payment, null, $customProperties);

                $notApplicable++;

                continue;
            }

            if((in_array($payment->getGateway(),Payment\Gateway::$fileBasedEMandateDebitGateways)=== true) and
                ($payment->getRecurringType() === Payment\RecurringType::AUTO))
            {
                $this->trace->info(
                    TraceCode::PAYMENT_VERIFY_STOPPED_FOR_FILE_BASED_DEBITS,
                    [
                        'payment_id' => $payment->getId(),
                    ]);

                $notApplicable++;

                continue;
            }

            $lock = $this->lockPaymentForVerify($payment);

            if ($lock === false)
            {
                $this->trace->info(TraceCode::PAYMENT_VERIFICATION_PAYMENT_LOCKED, [
                    'payment_id' => $payment->getId(),
                    'gateway'    => $payment->getGateway(),
                ]);

                $locked++;

                continue;
            }

            $this->repo->reload($payment);

            // We ignore all the payments which has been authorized
            // but not captured
            if (($payment->hasBeenAuthorized() === true) and
                ($payment->hasBeenCaptured() === false))
            {
                $this->trace->info(TraceCode::PAYMENT_VERIFICATION_PAYMENT_AUTHORIZED, [
                    'payment_id' => $payment->getId(),
                    'gateway'    => $payment->getGateway(),
                    'authorized' => $payment->hasBeenAuthorized(),
                    'captured'   => $payment->hasBeenCaptured(),
                ]);

                $payment->setNonVerifiable();

                $this->repo->saveOrFail($payment);

                $notApplicable++;

                $this->releasePaymentAfterVerify($payment);

                continue;
            }

            // There could be case where payment is already verified by some other thread,
            // hence check the verify_at after reload
            if ($verifyStart < $payment->getVerifyAt())
            {
                $this->trace->info(TraceCode::PAYMENT_VERIFICATION_VERIFY_AT_GREATER_THAN_VERIFY_START, [
                    'payment_id'    => $payment->getId(),
                    'gateway'       => $payment->getGateway(),
                    'verify_start'  => $verifyStart,
                    'verify_at'     => $payment->getVerifyAt(),
                ]);

                $notApplicable++;

                $this->releasePaymentAfterVerify($payment);

                continue;
            }

            //Payment has already been capture, will be verified by VerifyCapturePayments cron.
            if($payment->hasBeenCaptured() === true)
            {
                $this->trace->info(TraceCode::PAYMENT_VERIFICATION_PAYMENT_CAPTURED, [
                    'payment_id'    => $payment->getId(),
                    'gateway'       => $payment->getGateway(),
                    'captured'      => $payment->hasBeenCaptured(),
                ]);

                $notApplicable++;

                $this->releasePaymentAfterVerify($payment);

                continue;
            }

            $verifyResult = null;

            // For verification of created and failed payments
            $filter = ($payment->isCreated() === true) ? Filter::PAYMENTS_CREATED : Filter::PAYMENTS_FAILED;

            $verifyResult = $this->verifyPaymentNewRoute($payment, $filter);

            if ($verifyResult !== null)
            {
                $resultSet[$verifyResult] += 1;

                if ($verifyResult === Result::AUTHORIZED)
                {
                    $totalAuthTimeDiff += (time() - $payment->getCreatedAt());

                    $avgAuthTime = $totalAuthTimeDiff/$resultSet[Result::AUTHORIZED];
                }
            }
            else
            {
                $notApplicable++;
            }

            $attemptedPayments += 1;

            $this->releasePaymentAfterVerify($payment);
        }

        $verifyEnd = time();

        $totalVerifyTime = $verifyEnd - $verifyStart;

        $shouldBeAttempted = $totalPaymentsCount - $notApplicable;

        $resultSet['attempted_payments'] = $attemptedPayments;
        $resultSet['total_payments'] = $totalPaymentsCount;
        $resultSet['must_attempt'] = $shouldBeAttempted;

        $summary = [
            'total_payments' => $totalPaymentsCount,
            'total_time'     => $totalVerifyTime,
            'authorize_time' => $avgAuthTime,
            'not_applicable' => $notApplicable,
            'locked_count'   => $locked,
            'attempted_payments' => $attemptedPayments,
            'must_attempt' => $shouldBeAttempted,
        ];

        return [$summary, $resultSet];
    }

    protected function doVerifyCapturedPayments(Base\PublicCollection $payments)
    {
        $totalPaymentsCount = $payments->count();

        $resultSet = [
            Result::AUTHORIZED    => 0,
            Result::SUCCESS       => 0,
            Result::TIMEOUT       => 0,
            Result::ERROR         => 0,
            Result::UNKNOWN       => 0,
            Result::REQUEST_ERROR => 0,
        ];

        $notApplicable = $locked = 0;

        $verifyStart = Carbon::now(Timezone::IST)->timestamp;

        $attemptedPayments = 0;

        foreach ($payments as $payment)
        {
            $gateway = $payment->getGateway();

            if($this->isGatewayBlocked($gateway) === true)
            {
                $notApplicable++;

                continue;
            }

            $lock = $this->lockPaymentForVerify($payment);

            if($lock === false)
            {
                $locked++;

                continue;
            }

            $this->repo->reload($payment);

            // We ignore all the payments which has been authorized
            // but not captured
            if (($payment->hasBeenAuthorized() === true) and
                ($payment->hasBeenCaptured() === false))
            {
                $payment->setNonVerifiable();

                $this->repo->saveOrFail($payment);

                $notApplicable++;

                $this->releasePaymentAfterVerify($payment);

                continue;
            }

            // There could be case where payment is already verified by some other thread,
            // hence check the verify_at after reload
            if ($verifyStart < $payment->getVerifyAt())
            {
                $notApplicable++;

                $this->releasePaymentAfterVerify($payment);

                continue;
            }

            $verifyResult = null;

            // For verification of captured payment
            if($payment->hasBeenCaptured() === true)
            {
                if($this->isPaymentNotApplicableForCaptureVerify($payment) === true)
                {
                    $payment->setNonVerifiable();

                    $this->repo->saveOrFail($payment);

                    $notApplicable++;

                    $this->releasePaymentAfterVerify($payment);

                    continue;
                }

                $filter = Filter::PAYMENTS_CAPTURED;

                $verifyResult = (new CaptureVerify())->verifyPayment($payment, $filter);

                // On hold is removed once transaction is verified successfully
                if($verifyResult === Result::SUCCESS)
                {
                    $payment->setNonVerifiable();

                    $this->repo->saveOrFail($payment);
                }

                $resultSet[$verifyResult] += 1;

                $attemptedPayments += 1;
            }
        }

        $verifyEnd = time();

        $totalVerifyTime = $verifyEnd - $verifyStart;

        $shouldBeAttempted = $totalPaymentsCount - $notApplicable;

        $resultSet['attempted_payments'] = $attemptedPayments;
        $resultSet['total_payments'] = $totalPaymentsCount;
        $resultSet['must_attempt'] = $shouldBeAttempted;

        $summary = [
            'total_payments' => $totalPaymentsCount,
            'total_time'     => $totalVerifyTime,
            'not_applicable' => $notApplicable,
            'locked_count'   => $locked,
            'attempted_payments' => $attemptedPayments,
            'must_attempt' => $shouldBeAttempted,
        ];

        return [$summary, $resultSet];
    }

    public function verifyPaymentsWithIds(array $paymentIds)
    {
        $verifyFetchStartTime = time();

        $disabledGateways = $this->getBlockedGateways();

        // //
        // // We Fetch Twice the number of required payments,
        // // and filtering extra payments in later stage
        // //
        $paymentsCollectionWithCount = $this->repo->payment->getPaymentsToVerifyByIds(
                                                                $paymentIds,
                                                                self::ROWS_TO_FETCH * 2,
                                                                $disabledGateways);

        $payments = $paymentsCollectionWithCount['payments'];

        $verifiableCount = $paymentsCollectionWithCount['verifiable_count'];

        $verifyFetchEndTime = time();

        $verifyFetchTime = $verifyFetchEndTime - $verifyFetchStartTime;

        $summary = $this->verifyMultiplePayments($payments, '', [], $verifiableCount, $verifyFetchTime);

        $apiPaymentIds = $payments->pluck(Payment\Entity::ID)->toArray();

        $possibleRearchPaymentIds = array_diff($paymentIds,$apiPaymentIds);

        return $this->verifyRearchPaymentsInBulk($possibleRearchPaymentIds,$summary);
    }

    protected function verifyRearchPaymentsInBulk(array $paymentIds, $summary)
    {
        foreach ($paymentIds as $paymentId)
        {
            try
            {
                $response = $this->app['pg_router']->paymentVerify($paymentId, false);

                if (isset($response) === false)
                {
                    $summary[Result::ERROR] = $summary[Result::ERROR] + 1;
                }
                elseif (isset($response["id"]) === false)
                {
                    $summary[Result::ERROR] = $summary[Result::ERROR] + 1;
                }
                elseif (isset($response["id"]) === true)
                {
                    $summary[Result::SUCCESS] = $summary[Result::SUCCESS] + 1;

                    if ($response["status"] === Payment\Status::AUTHORIZED)
                    {
                        $summary[Result::AUTHORIZED] = $summary[Result::AUTHORIZED] + 1;
                    }
                }
            }
            catch (\Exception $ex)
            {
                $summary[Result::ERROR] = $summary[Result::ERROR] + 1;

                $this->trace->info(
                    TraceCode::PG_ROUTER_REQUEST_FAILURE,
                    [
                        'payment_id'        => $paymentId,
                        'action'            => "bulk_verify",
                    ]);
            }
        }

        return $summary;
    }

    public function verifyPaymentsWithIdsNewRoute(array $paymentIds)
    {
        $verifyFetchStartTime = time();

        $disabledGateways = $this->getBlockedGateways();

        // //
        // // We Fetch Twice the number of required payments,
        // // and filtering extra payments in later stage
        // //
        $paymentsCollectionWithCount = $this->repo->payment->getPaymentsToVerifyByIds(
            $paymentIds,
            self::ROWS_TO_FETCH * 2,
            $disabledGateways);

        $payments = $paymentsCollectionWithCount['payments'];

        $verifiableCount = $paymentsCollectionWithCount['verifiable_count'];

        $verifyFetchEndTime = time();

        $verifyFetchTime = $verifyFetchEndTime - $verifyFetchStartTime;

        return $this->verifyMultiplePaymentsNewRoute($payments, '', [], $verifiableCount, $verifyFetchTime);
    }

    /**
     * Get the boundaries for which paymnets dhould be fetched for running verify
     * @param string $filter
     *
     * @return array
     */
    protected function getStartAndEndTimeForVerify(string $filter)
    {
        Filter::isValidFilter($filter);

        $minimumTime = self::MINIMUM_TIME_MAP[$filter];

        $maximumTime = self::MAXIMUM_TIME_MAP[$filter];

        return [
            'min' => $minimumTime,
            'max' => $maximumTime
        ];
    }

    /**
     * Get the boundary using filter and time boundary
     * @param string $filter
     * @param array  $bucketFilter
     * @param array  $timeBoundary
     *
     * @return array boundary array for verify
     */
    protected function getBoundaryForVerify(string $filter, array $bucketFilter = [], array $timeBoundary = [])
    {
        $boundary = [];

        if ($filter !== Filter::VERIFY_ERROR)
        {
            // Return the proper boundary array
            $boundary = self::$failureStartBoundary;

            // Value being set here signifies end of boundary.
            // index is incremented while doing query
            // As we want to get Payments which have passed that boundary,
            // and should be verified.
            $boundary[-1] = $timeBoundary['min'];

            // If bucket filter is passed, get rid of other bucket values
            if (empty($bucketFilter) === false)
            {
                $newBoundary = [];

                foreach ($bucketFilter as $bucket)
                {
                    $bucketEndTime = $boundary[$bucket - 1];

                    $newBoundary = [$bucket - 1 => $bucketEndTime];
                }

                $boundary = $newBoundary;
            }
        }

        return $boundary;
    }

    /**
     * @param Base\PublicCollection $payments
     * @param string                $filter
     * @param array                 $bucketFilter
     * @param integer               $verifiableCount
     * @param integer               $verifyFetchTime
     * @return array with aggregated results
     */
    protected function verifyMultiplePayments(
        Base\PublicCollection $payments,
        string $filter,
        array $bucketFilter,
        int $verifiableCount,
        int $verifyFetchTime,
        string $gateway = null)
    {
        $resultSet = [
            Result::AUTHORIZED    => 0,
            Result::SUCCESS       => 0,
            Result::TIMEOUT       => 0,
            Result::ERROR         => 0,
            Result::UNKNOWN       => 0,
            Result::REQUEST_ERROR => 0,
        ];

        $notApplicable = 0;

        $lockedPayments = $this->lockPaymentsForVerify($payments, $filter);

        $totalAuthTimeDiff = 0;

        $verifyStart = time();

        foreach ($lockedPayments as $payment)
        {
            if ($this->isGatewayBlocked($payment->getGateway()) === true)
            {
                $notApplicable++;

                $this->releasePaymentAfterVerify($payment);

                continue;
            }

            $verifyResult = $this->verifyPayment($payment, $filter);

            if ($verifyResult === Result::AUTHORIZED)
            {
                $totalAuthTimeDiff += (time() - $payment->getCreatedAt());
            }

            if ($verifyResult !== null)
            {
                $resultSet[$verifyResult] += 1;
            }
            else
            {
                $notApplicable++;
            }

            $this->releasePaymentAfterVerify($payment);
        }

        $verifyEnd = time();

        $times = [
            'start'             => $verifyStart,
            'end'               => $verifyEnd,
            'authorize_time'    => $totalAuthTimeDiff,
            'fetch_time'        => $verifyFetchTime
        ];

        $summary = $this->processResult($resultSet, $times, $filter, $bucketFilter, $verifiableCount);

        $this->addDataToVerifySummary($summary, $lockedPayments, $notApplicable);

        $this->trace->gauge(Metric::PAYMENTS_VERIFIABLE_COUNT, $verifiableCount, ['gateway' => $gateway]);

        $this->trace->info(TraceCode::VERIFY_PROCESSED_SUMMARY, $summary);

        return $summary;
    }

    /**
     * @param Base\PublicCollection $payments
     * @param string                $filter
     * @param array                 $bucketFilter
     * @param integer               $verifiableCount
     * @param integer               $verifyFetchTime
     * @return array with aggregated results
     */
    protected function verifyMultiplePaymentsNewRoute(
        Base\PublicCollection $payments,
        string $filter,
        array $bucketFilter,
        int $verifiableCount,
        int $verifyFetchTime,
        string $gateway = null)
    {
        $resultSet = [
            Result::AUTHORIZED    => 0,
            Result::SUCCESS       => 0,
            Result::TIMEOUT       => 0,
            Result::ERROR         => 0,
            Result::UNKNOWN       => 0,
            Result::REQUEST_ERROR => 0,
        ];

        $notApplicable = 0;

        $lockedPayments = $this->lockPaymentsForVerify($payments, $filter);

        $totalAuthTimeDiff = 0;

        $verifyStart = time();

        foreach ($lockedPayments as $payment)
        {
            if ($this->isGatewayBlocked($payment->getGateway()) === true)
            {
                $notApplicable++;

                $this->releasePaymentAfterVerify($payment);

                continue;
            }

            $verifyResult = $this->verifyPaymentNewRoute($payment, $filter);

            if ($verifyResult === Result::AUTHORIZED)
            {
                $totalAuthTimeDiff += (time() - $payment->getCreatedAt());
            }

            if ($verifyResult !== null)
            {
                $resultSet[$verifyResult] += 1;
            }
            else
            {
                $notApplicable++;
            }

            $this->releasePaymentAfterVerify($payment);
        }

        $verifyEnd = time();

        $times = [
            'start'             => $verifyStart,
            'end'               => $verifyEnd,
            'authorize_time'    => $totalAuthTimeDiff,
            'fetch_time'        => $verifyFetchTime
        ];

        $summary = $this->processResult($resultSet, $times, $filter, $bucketFilter, $verifiableCount);

        $this->addDataToVerifySummary($summary, $lockedPayments, $notApplicable);

        $this->trace->gauge(Metric::PAYMENTS_VERIFIABLE_COUNT, $verifiableCount, ['gateway' => $gateway]);

        $this->trace->info(TraceCode::VERIFY_PROCESSED_SUMMARY, $summary);

        return $summary;
    }


    protected function addDataToVerifySummary(array & $summary, Base\PublicCollection $payments, int $notApplicable)
    {
        if ($notApplicable !== 0)
        {
            $summary['not_applicable'] = $notApplicable;
        }

        $summary['verified_payments'] = $payments->count();
    }

    /** Lock All Payments
     *
     * @param Base\PublicCollection $payments
     * @param string                $filter
     *
     * @return Base\PublicCollection with keys locked and not_locked,
     *         having payments which are locked and not_locked respectively
     */
    protected function lockPaymentsForVerify(Base\PublicCollection $payments, string $filter)
    {
        $paymentIds = $payments->pluck(Payment\Entity::ID);

        // Get payment ids to lock
        $lockedPaymentIds = $this->mutex->acquireMultiple(
            $paymentIds, self::DEFAULT_LOCK_TIME, self::KEY_SUFFIX);

        $this->trace->info(
            TraceCode::VERIFY_LOCKED_PAYMENTS,
            [
                'payment_ids_locked'     => $lockedPaymentIds['locked'],
                'payment_ids_not_locked' => $lockedPaymentIds['unlocked'],
                'filter'                 => $filter,
            ]);

        // Lock all payments by payment ids
        $lockedPayments = $payments->whereIn(Payment\Entity::ID, $lockedPaymentIds['locked']);

        // If more payments are locked,
        // release lock on extra payments
        if ($lockedPayments->count() > self::ROWS_TO_FETCH)
        {
            $chunkedLockedPayments = $lockedPayments->chunk(self::ROWS_TO_FETCH);

            $this->mutex->releaseMultiple($chunkedLockedPayments[1], self::KEY_SUFFIX);

            $lockedPayments = $chunkedLockedPayments[0];
        }

        // Return final locked payments
        return $lockedPayments;
    }

    protected function lockPaymentForVerify(Payment\Entity $payment)
    {
        $resourceWithSuffix = $payment->getId() . self::KEY_SUFFIX;

        $isLockAcquired = $this->mutex->acquire($resourceWithSuffix, self::DEFAULT_LOCK_TIME);

        return $isLockAcquired;
    }

    protected function releasePaymentAfterVerify(Payment\Entity $payment)
    {
        $this->mutex->release($payment->getId() . self::KEY_SUFFIX);
    }

    /** Process the result for displaying in slack and returning to caller
     *
     * @param array $result             Raw result array
     * @param array $times              Array containing time metrics
     * @param string $filter            Filter used to fetch payments
     * @param array  $bucketFilter      Bucket filter used to fetch payments
     * @param integer $verifiableCount  Max payments waiting to be verified
     * @return array with processed result
     */
    protected function processResult(array $result, array $times, string $filter, array $bucketFilter, int $verifiableCount)
    {
        $avgTimeDiff = 0;

        $totalVerifyTime = $times['end'] - $times['start'];

        if ($result[Result::AUTHORIZED] !== 0)
        {
            $avgTimeDiff = ($times['authorize_time'] / $result[Result::AUTHORIZED]);
        }

        $processedResults = [
            'filter'           => $filter,
            'bucket_filter'    => $bucketFilter,
            'verifiable_count' => $verifiableCount,
            'authorize_time'   => $avgTimeDiff,
            'total_time'       => $totalVerifyTime . ' secs',
            'fetch_time'       => $times['fetch_time']
        ];

        return array_merge($processedResults, $result);
    }

    public function verifyPayment(Payment\Entity $payment, string $filter = null, array $gatewayData = null)
    {
        $result = Result::SUCCESS;

        $merchant = $payment->merchant;

        //
        // Exception is thrown when the there's a mismatch
        // between payment status and status returned by gateway.
        // Most cases, this would mean that the payment is in failed
        // state and gateway returned back status authorized.
        //
        try
        {
            $this->processor($merchant)->verify($payment, $gatewayData);

            $this->updateVerifyBucket($payment, $filter, self::NEXT);
        }
        catch (Exception\PaymentVerificationException $e)
        {
            $action = $e->getAction();

            $result = Result::ERROR;

            $this->trace->info(
                TraceCode::VERIFY_ACTION,
                [
                    'payment_id' => $payment->getId(),
                    'action' => $action,
                    'verify_route' => 'verify/all',
                ]
            );

            switch ($action)
            {
                case Action::BLOCK:
                    $this->blockGatewayForVerify($payment);
                    $this->updateVerifyBucket($payment, $filter, self::CURR);

                    break;

                case Action::RETRY:
                    $this->updateVerifyBucket($payment, $filter, self::CURR);

                    break;

                case Action::FINISH:
                    $result = Result::UNKNOWN;

                    $this->updateVerifyBucket($payment, $filter, self::LAST);

                    break;

                default:
                    $this->updateVerifyBucket($payment, $filter, self::NEXT);

                    try
                    {
                        $result = $this->authorizePayment($merchant, $payment, $e);
                    }
                    catch (\Throwable $e)
                    {
                        $this->trace->traceException(
                            $e,
                            Trace::ERROR,
                            TraceCode::GATEWAY_VERIFY_ERROR,
                            [
                                'payment_id' => $payment->getId()
                            ]);

                        $result = Result::ERROR;
                    }

                    break;
            }
        }
        catch (Exception\GatewayRequestException $e)
        {
            if ($e instanceof Exception\GatewayTimeoutException)
            {
                $result = Result::TIMEOUT;

                $timeoutThreshold = $this->getTimeoutThresholdForBlock($payment);

                $this->checkForPreviousRequestErrorAndBlockGatewayIfApplicable($payment,
                    $timeoutThreshold,
                    self::GATEWAY_TIMEOUT_CACHE_KEY_PREFIX);

                $this->trace->traceException($e,
                                    Trace::WARNING,
                                    TraceCode::GATEWAY_REQUEST_TIMEOUT,
                                    [
                                       'payment_id' => $payment->getId(),
                                        'gateway'    => $payment->getGateway()
                                    ]);
            }
            else
            {
                $result = Result::REQUEST_ERROR;

                $this->checkForPreviousRequestErrorAndBlockGatewayIfApplicable($payment,
                    self::GATEWAY_REQUEST_ERROR_THRESHOLD,
                    self::GATEWAY_REQUEST_ERROR_CACHE_KEY_PREFIX);

                $this->trace->traceException($e,
                                    Trace::WARNING,
                                    TraceCode::GATEWAY_REQUEST_ERROR,
                                    [
                                       'payment_id' => $payment->getId(),
                                       'gateway'    => $payment->getGateway()
                                    ]);
            }

            $this->updateVerifyBucket($payment, $filter, self::NEXT);
        }
        catch (\Throwable $e)
        {
            $this->updateVerifyBucket($payment, $filter, self::NEXT);

            // @note: If payment verification fails due to any reason
            // other than expected ones, we should log it as an error
            // exception.
            $extraData = ['payment_id' => $payment->getId()];

            $this->trace->traceException($e, null, null, $extraData);

            // Just continue
            $result = Result::ERROR;
        }

        return $result;
    }

    public function verifyPaymentNewRoute(Payment\Entity $payment, string $filter = null, array $gatewayData = null)
    {
        $result = Result::SUCCESS;

        $merchant = $payment->merchant;

        $payment->setIsGooglePayMethodChangeApplicable();

        // Change method from unselected to UPI before verify call
        $payment->updateGooglePayPaymentMethodIfApplicable(Payment\Method::UPI);

        //
        // Exception is thrown when the there's a mismatch
        // between payment status and status returned by gateway.
        // Most cases, this would mean that the payment is in failed
        // state and gateway returned back status authorized.
        //
        try
        {
            $this->processor($merchant)->verifyNewRoute($payment, 'verify/new_cron', $gatewayData);

            $this->updateVerifyBucket($payment, $filter, self::NEXT);

            // Change method to unselected, as payment is not authorized
            $payment->updateGooglePayPaymentMethodIfApplicable(Payment\Method::UNSELECTED);
        }
        catch (Exception\PaymentVerificationException $e)
        {
            $result = $this->handlePaymentVerificationException($payment, $filter, $e);
        }
        catch (Exception\GatewayRequestException $e)
        {
            $result = $this->handleGatewayRequestException($payment, $filter, $e);
        }
        catch (\Throwable $e)
        {
            // Change method to unselected, as payment is not authorized
            $payment->updateGooglePayPaymentMethodIfApplicable(Payment\Method::UNSELECTED);

            $this->updateVerifyBucket($payment, $filter, self::NEXT);

            // @note: If payment verification fails due to any reason
            // other than expected ones, we should log it as an error
            // exception.
            $extraData = ['payment_id' => $payment->getId()];

            $this->trace->traceException($e, null, null, $extraData);

            // Just continue
            $result = Result::ERROR;
        }

        return $result;
    }

    protected function handlePaymentVerificationException(Payment\Entity $payment, string $filter,
                                                          Exception\PaymentVerificationException $e)
    {
        // Change method back to unselected, for the cases where payment is not getting authorized
        $payment->updateGooglePayPaymentMethodIfApplicable(Payment\Method::UNSELECTED);

        $merchant = $payment->merchant;

        $action = $e->getAction();

        $result = Result::ERROR;

        $this->trace->info(
            TraceCode::VERIFY_ACTION,
            [
                'payment_id' => $payment->getId(),
                'action' => $action,
                'verify_route' => 'verify/new_route',
            ]
        );

        switch ($action)
        {
            case Action::BLOCK:
                $this->blockGatewayForVerify($payment);
                $this->updateVerifyBucket($payment, $filter, self::CURR);

                break;

            case Action::RETRY:
                $this->updateVerifyBucket($payment, $filter, self::CURR);

                break;

            case Action::FINISH:
                $result = Result::UNKNOWN;

                $this->updateVerifyBucket($payment, $filter, self::LAST);

                break;

            default:
                $this->updateVerifyBucket($payment, $filter, self::NEXT);

                // Change method from unselected to UPI before verify call
                $payment->updateGooglePayPaymentMethodIfApplicable(Payment\Method::UPI);

                try
                {
                    $result = $this->authorizePayment($merchant, $payment, $e);
                }
                catch (\Throwable $e)
                {
                    // Change method to unselected, as payment is not authorized
                    $payment->updateGooglePayPaymentMethodIfApplicable(Payment\Method::UNSELECTED);

                    $this->trace->traceException(
                        $e,
                        Trace::ERROR,
                        TraceCode::GATEWAY_VERIFY_ERROR,
                        [
                            'payment_id' => $payment->getId()
                        ]);

                    $result = Result::ERROR;
                }

                break;
        }

        return $result;
    }

    protected function handleGatewayRequestException(Payment\Entity $payment, string $filter,
                                                     \Exception $e)
    {
        // Change method to unselected, as payment is not authorized
        $payment->updateGooglePayPaymentMethodIfApplicable(Payment\Method::UNSELECTED);

        $result = Result::REQUEST_ERROR;

        if ($e instanceof Exception\GatewayTimeoutException)
        {
            $result = Result::TIMEOUT;

            $timeoutThreshold = $this->getTimeoutThresholdForBlock($payment);

            $this->checkForPreviousRequestErrorAndBlockGatewayIfApplicable($payment,
                $timeoutThreshold,
                self::GATEWAY_TIMEOUT_CACHE_KEY_PREFIX);

            $this->trace->traceException($e,
                Trace::WARNING,
                TraceCode::GATEWAY_REQUEST_TIMEOUT,
                [
                    'payment_id' => $payment->getId(),
                    'gateway'    => $payment->getGateway()
                ]);
        }
        else
        {
            $result = Result::REQUEST_ERROR;

            $this->checkForPreviousRequestErrorAndBlockGatewayIfApplicable($payment,
                self::GATEWAY_REQUEST_ERROR_THRESHOLD,
                self::GATEWAY_REQUEST_ERROR_CACHE_KEY_PREFIX);

            $this->trace->traceException($e,
                Trace::WARNING,
                TraceCode::GATEWAY_REQUEST_ERROR,
                [
                    'payment_id' => $payment->getId(),
                    'gateway'    => $payment->getGateway()
                ]);
        }

        $this->updateVerifyBucket($payment, $filter, self::NEXT);

        return $result;
    }

    protected function getTimeoutThresholdForBlock(Payment\Entity $payment)
    {
        $threshold = self::GATEWAY_TIMEOUT_THRESHOLD;

        // We are doing this at a gateway level, as we dont want to increase the threshold for all upi payments.
        // That might put unnecessary load on cron when gateway is down. If some other gateway has timeout issues
        // then we can add that gateway in the list.
        if (isset(Payment\Gateway::$verifyBlockThresholdGateways[$payment->getGateway()]) === true)
        {
            $threshold = Payment\Gateway::$verifyBlockThresholdGateways[$payment->getGateway()];
        }

        return $threshold;
    }

    protected function getBucketIntervalForBlock(Payment\Entity $payment)
    {
        $bucketInterval = self::GATEWAY_TIMEOUT_BUCKET_INTERVAL;

        // We are doing this at a gateway level, as we dont want to change the bucket time for all upi payments.
        if (isset(Payment\Gateway::$verifyBlockBucketIntervalGateways[$payment->getGateway()]) === true)
        {
            $bucketInterval = Payment\Gateway::$verifyBlockBucketIntervalGateways[$payment->getGateway()];
        }

        return $bucketInterval;
    }

    protected function getTimeForBlock(Payment\Entity $payment)
    {
        $gatewayBlockTime = self::GATEWAY_BLOCK_TIME;

        // We are doing this at a gateway level, as we dont want to increase the threshold for all upi payments.
        // That might put unnecessary load on cron when gateway is down. If some other gateway has timeout issues
        // then we can add that gateway in the list.
        if (isset(Payment\Gateway::$verifyBlockTimeGateways[$payment->getGateway()]) === true)
        {
            $gatewayBlockTime = Payment\Gateway::$verifyBlockTimeGateways[$payment->getGateway()];
        }

        return $gatewayBlockTime;
    }

    protected function checkForPreviousRequestErrorAndBlockGatewayIfApplicable(Payment\Entity $payment, int $threshold, string $key)
    {
        $currentTimestamp = Carbon::now()->getTimestamp();

        $timeoutBucketInterval = $this->getBucketIntervalForBlock($payment);

        $currentTimestampBucket = (int)($currentTimestamp / $timeoutBucketInterval);

        $gateway = $payment->getGateway();

        $key .= '_' . $gateway . '_' . $currentTimestampBucket;

        $requestErrorPaymentsCount = (int) $this->redis->incr($key);

        $this->redis->expire($key, $timeoutBucketInterval);

        if ($requestErrorPaymentsCount >= $threshold)
        {
            $this->blockGatewayForVerify($payment);
        }
    }

    protected function blockGatewayForVerify(Payment\Entity $payment)
    {
        $expireTime = Carbon::now()->getTimestamp() + $this->getTimeForBlock($payment);

        $this->trace->info(
            TraceCode::VERIFY_GATEWAY_BLOCK,
            [
                'gateway'               => $payment->getGateway(),
                'expire_time'           => $expireTime,
                'timeout_threshold'     => $this->getTimeoutThresholdForBlock($payment),
                'bucket_interval'       => $this->getBucketIntervalForBlock($payment),
                'block_time'            => $this->getTimeForBlock($payment),
            ]);

        $this->redis->hSet(
            self::GATEWAY_BLOCK_CACHE_KEY,
            $payment->getGateway(),
            $expireTime
        );
    }

    protected function getBlockedGateways()
    {
        $verifyDisabledGateways = Payment\Gateway::$verifyDisabled;

        $blockedGateways = [];

        $allBlockedGateways = $this->redis->hGetAll(self::GATEWAY_BLOCK_CACHE_KEY);

        foreach ($allBlockedGateways as $blockedGateway => $expiryTime)
        {
            if ($expiryTime >= Carbon::now()->getTimestamp())
            {
                $blockedGateways[] = $blockedGateway;
            }
            else
            {
                $this->redis->hDel(self::GATEWAY_BLOCK_CACHE_KEY, $blockedGateway);
            }
        }

        return array_merge($verifyDisabledGateways, $blockedGateways);
    }

    protected function isGatewayBlocked(string $gateway)
    {
        $blockedGateways = $this->getBlockedGateways();

        if (in_array($gateway, $blockedGateways, true) === true)
        {
            return true;
        }

        return false;
    }

    protected function authorizePayment(
        Merchant\Entity $merchant,
        Payment\Entity $payment,
        Exception\PaymentVerificationException $e)
    {
        $verify = $e->getVerifyObject();

        if (($verify->apiSuccess === true) and ($verify->gatewaySuccess === false))
        {
//            $ex = new Exception\LogicException(
//                "Should not have reached here. apiSuccess cannot be true when gatewaySuccess is false.",
//                null,
//                $verify->getDataToTrace());

            $ex = new Exception\RuntimeException(
                'apiSuccess cannot be true when gatewaySuccess is false, transaction might be possible fraud',
                [
                    'payment_id' => $payment->getId(),
                    'gateway'    => $payment->getGateway(),
                ]);

            $extraProperties = [
                'is_pushed_to_kafka'  => $payment->getIsPushedToKafka(),
                'api_success'  => $verify->apiSuccess,
                'gateway_success' => $verify->gatewaySuccess,
                'amount_mismatch' => $verify->amountMismatch
            ];

            $this->app['diag']->trackVerifyPaymentEvent(EventCode::PAYMENT_VERIFICATION_STATUS_MISMATCH_POSSIBLE_FRAUD, $payment, null, $extraProperties);

            $this->trace->warning(
                TraceCode::PAYMENT_VERIFY_POSSIBLE_FRAUD,
                $this->getAuthExceptionTraceBody($payment, $ex)
            );

            throw $ex;
        }
        else
        {
            // PaymentVerificationException was only been thrown when there was a status mismatch
            // Thus till now there were only two possibilities, one where apiSuccess=true and
            // gatewaySuccess=false is already handled and will result in LogicException.
            // Second case where apiSuccess=false and gatewaySuccess=true will require payment to be authorized.
            // Now with new changes, some gateways will throw PaymentVerificationException when
            // apiSuccess=false, gatewaySuccess=true and amountMismatch=true.
            // Thus, we need to check for amountMismatch before authorizing payment.
            if (($verify->amountMismatch === true) and
                (($this->shouldAuthorizeOnAmountMismatch($payment, $e) === false)))
            {
                // If amountMismatch is not allowed, we can throw the generic runtime exception
                throw new Exception\RuntimeException(
                    'Payment amount verification failed.',
                    [
                        'payment_id' => $payment->getId(),
                        'gateway'    => $payment->getGateway(),
                    ]);
            }

            try
            {
                $processor = $this->processor($merchant);

                $resource = $processor->getCallbackMutexResource($payment);

                $this->mutex->acquireAndRelease($resource, function() use ($processor, $payment)
                {
                    // Adding the order id mutex for solving multiple captured payment on same order
                    // If payment has order id then resource will contain order id else payment id
                    $orderMutex = $processor->getCallbackOrderMutexResource($payment);

                    $this->mutex->acquireAndRelease($orderMutex,
                        function() use ($processor, $payment)
                        {
                            // Attempt to authorize payments whose verification failed
                            $processor->authorizeFailedPayment($payment);
                        },
                        60,
                        ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
                        20, 1000, 2000
                    );
                },
                    60,
                    ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
                    20,
                    1000,
                    2000);
            }
            catch (Exception\BadRequestValidationFailureException $ex)
            {
                $this->trace->warning(
                    TraceCode::PAYMENT_VERIFY_ALREADY_AUTHORIZED,
                    $this->getAuthExceptionTraceBody($payment, $ex)
                );

                return null;
            }
            catch (Exception\GatewayErrorException $ex)
            {
                $this->trace->warning(
                    TraceCode::GATEWAY_VERIFY_ERROR,
                    $this->getAuthExceptionTraceBody($payment, $ex)
                );

                return null;
            }
        }

        // Now Just continue
        return Result::AUTHORIZED;
    }

    protected function updateVerifyBucket(
        Payment\Entity $payment,
        string $filter = null,
        string $param = self::NEXT)
    {
        if ($this->isBucketUpdateApplicable() === true)
        {
            $nextVerifyBucket = $this->getPaymentVerifyBucket($payment, $filter, $param);

            $verifyAt = $this->getPaymentVerifyAt($payment, $nextVerifyBucket);

            $payment->setVerifyAt($verifyAt);

            $payment->setVerifyBucket($nextVerifyBucket);

            $this->repo->saveOrFail($payment);
        }
    }

    protected function isBucketUpdateApplicable()
    {
        $cron = $this->app['basicauth']->isCron();

        //
        // If filter is null, then verify is initiated manually, not via cron
        //
        // We need to check for route, as we dont want any other cron
        // running verify to update this maybe recon in future
        //
        // Don't update VERIFY_BUCKET, in that case
        //
        if (($cron === true) and
            (in_array($this->route, ['payment_verify_multiple', 'payment_verify_all', 'payment_new_verify_all'], true) === true))
        {
            return true;
        }

        return false;
    }

    protected function getAuthExceptionTraceBody(
        Payment\Entity $payment,
        Exception\BaseException $ex)
    {
        return [
            'payment_id'    => $payment->getId(),
            'status'        => $payment->getStatus(),
            'verify_bucket' => $payment->getVerifyBucket(),
            'error_message' => $ex->getMessage(),
        ];
    }

    protected function getPaymentStatusForFilter(string $filter)
    {
        $paymentStatus = null;

        switch ($filter)
        {
            case Filter::PAYMENTS_FAILED:
                $paymentStatus = Payment\Status::FAILED;
                break;

            case Filter::PAYMENTS_CREATED:
                $paymentStatus = Payment\Status::CREATED;
                break;
        }

        return $paymentStatus;
    }

    protected function getVerifyStatusForFilter(string $filter)
    {
        $verifyStatus = null;

        switch ($filter)
        {
            case Filter::VERIFY_FAILED:
                $verifyStatus = Status::FAILED;
                break;

            case Filter::VERIFY_ERROR:
                $verifyStatus = Status::ERROR;
                break;

        }

        return $verifyStatus;
    }

    /**
     * Gets the verify bucket in which the current
     * diff (current_time - payment_created_at) falls in.
     * For example: If greater than 15 minutes, the verify_bucket
     * will be 1. If greater than 1 hour, the verify_bucket will be 2.
     *
     * @param $diff
     * @param $boundaries
     * @return int
     */
    protected function getCurrentVerifyBucket($diff, array $boundaries)
    {
        $currentVerifyBucket = $verifyBucket = 0;

        foreach ($boundaries as $boundary)
        {
            $verifyBucket++;

            if ($diff >= $boundary)
            {
                $currentVerifyBucket = $verifyBucket;
            }
            else
            {
                break;
            }
        }

        return $currentVerifyBucket;
    }

    protected function getPaymentVerifyBucket(
        Payment\Entity $payment,
        string $filter,
        string $param = self::NEXT)
    {
        // TODO : Migrate to new commented getPaymentVerifyBucket Function once we migrate fetch query to verify_at
        // For Payment having verified as error,
        // verify bucket should be 0
        if ($filter === Filter::VERIFY_ERROR)
        {
            return 0;
        }

        $diff = Carbon::now()->getTimestamp() - $payment->getCreatedAt();

        if ($filter === Filter::PAYMENTS_CAPTURED)
        {
            $diff = Carbon::now()->getTimestamp() - $payment->getCapturedAt();
        }

        // Payments which are less than X minutes old should always be picked by cron
        // Payments older than X minutes should follow the bucket logic
        if (($filter === Filter::PAYMENTS_CREATED) and ($diff < self::CREATED_MAX_TIME))
        {
            return 0;
        }

        // Get Verify Boundary to update Verify Bucket
        $boundaries = $this->getBoundaryInSeconds($filter);

        switch ($param)
        {
            case self::NEXT:
                $currentVerifyBucket = $this->getCurrentVerifyBucket(
                                               $diff,
                                               $boundaries);
                break;

            case self::LAST:
                $currentVerifyBucket = count($boundaries);
                break;

            case self::CURR:
                $currentVerifyBucket = $payment->getVerifyBucket() - 1;
                break;

            default:
                throw new Exception\LogicException(
                    'Invalid param for setting verify bucket');
        }

        $nextVerifyBucket = $currentVerifyBucket + 1;

        return $nextVerifyBucket;
    }

    protected function getPaymentVerifyAt(Payment\Entity $payment, int $nextVerifyBucket)
    {
        $verifyAt = null;

        if ($nextVerifyBucket > count(self::$failureStartBoundary))
        {
            $verifyAt = null;
        }
        else if ($nextVerifyBucket === $payment->getVerifyBucket())
        {
            // if verify bucket is not changing, verify after few mins.
            $verifyAt = time() + 600;
        }
        else
        {
            $verifyAt = time() + self::$updateWaitBoundaries[$nextVerifyBucket];
        }

        return $verifyAt;
    }

//    protected function getPaymentVerifyBucket(
//        Payment\Entity $payment,
//        string $filter,
//        string $param = self::NEXT)
//    {
//        // For Payment having verified as error,
//        // verify bucket should be 0
//        if ($filter === Filter::VERIFY_ERROR)
//        {
//            return 0;
//        }
//
//        $diff = Carbon::now()->getTimestamp() - $payment->getCreatedAt();
//        // Payments which are less than X minutes old should always be picked by cron
//        // Payments older than X minutes should follow the bucket logic
//        if (($filter === Filter::PAYMENTS_CREATED) and ($diff < self::CREATED_MAX_TIME))
//        {
//            return 0;
//        }
//
//
//        switch ($param)
//        {
//            case self::NEXT:
//                $nextVerifyBucket = $payment->getVerifyBucket() + 1;
//                break;
//
//            case self::LAST:
//                $nextVerifyBucket = count(self::$updateWaitBoundaries);
//                break;
//
//            case self::CURR:
//                $nextVerifyBucket = $payment->getVerifyBucket();
//                break;
//
//            default:
//                throw new Exception\LogicException(
//                    'Invalid param for setting verify bucket');
//        }
//
//        return $nextVerifyBucket;
//    }

    /**
     * @param string $filter filter for which boundary has to be returned
     * @return array  Array containg boundary with expiry time
     * @throws Exception\LogicException
     */
    public function getBoundaryInSeconds(string $filter)
    {
        switch ($filter)
        {
            case Filter::VERIFY_FAILED:
            case Filter::PAYMENTS_FAILED:
            case Filter::PAYMENTS_CREATED:
            case Filter::PAYMENTS_CAPTURED:
                $boundaries = self::$failureStartBoundary;
                break;

            default:
                throw new Exception\LogicException(
                    'Unknown filter provided.', null, ['filter' => $filter]);
        }

        return $boundaries;
    }

    protected function processor($merchant = null)
    {
        return new Payment\Processor\Processor($merchant);
    }

    protected function isPaymentNotApplicableForCaptureVerify($payment)
    {
        $gateway = $payment->getGateway();

        $isNotApplicable = ((Payment\Gateway::isCaptureVerifyEnabledGateway($gateway) === false) or
                            ((Payment\Gateway::isCaptureVerifyQREnabledGateways($gateway)=== false) and
                            ($payment->isBharatQr() === true)) or
                            ($payment->isUpiTransfer() === true));

        if ($isNotApplicable === true)
        {
            return $isNotApplicable;
        }

        // If payment is already reconciled, we don't want to verify it
        if ($payment->isReconciled() === true)
        {
            return true;
        }

        // For Hitachi (Rupay Network), verification call works only for 2 days(172800 sec) after authorization
        if ($gateway === Payment\Gateway::HITACHI)
        {
            if (($payment->hasCard() === true) and ($payment->card->getNetwork() === Network::getFullName(Network::RUPAY)))
            {
                if (($payment->hasBeenAuthorized() === true) and (Carbon::now()->getTimestamp() - $payment->getAuthorizeTimestamp() > 172800))
                {
                    return true;
                }
            }
        }

        return false;
    }

    protected function shouldAuthorizeOnAmountMismatch(
        Payment\Entity $payment,
        Exception\PaymentVerificationException $e): bool
    {
        $verify = $e->getVerifyObject();

        // If gateway has not explicitly set the currency and amountAuthorized,
        // we will not authorize the payment
        if ((is_string($verify->currency) or is_integer($verify->amountAuthorized)) === false)
        {
            return false;
        }

        return $payment->shouldAllowGatewayAmountMismatch($verify->currency, $verify->amountAuthorized);
    }

    public function isFinalErrorCode($payment)
    {
        $method = $payment->getMethod();
        $internal_error_code = $payment->getInternalErrorCode()??'';

        $isOptimizerPayment = false;

        $terminalTypeArray = array();

        if ($payment->hasTerminal() === true)
        {
            $terminalTypeArray = $payment->terminal->getType();

            if (($terminalTypeArray !== null) && (in_array('optimizer', $terminalTypeArray) === true))
            {
                $isOptimizerPayment = true;
            }
        }

        $isFinalErrorCode = $this->isFinal($method, $internal_error_code, $isOptimizerPayment);

        if ($isFinalErrorCode === true)
        {
            $payment->setNonVerifiable();

            $this->trace->info(TraceCode::PAYMENT_VERIFY_FILTER,
                [
                    'payment_id' => $payment->getId(),
                    'internal_error_code' => $internal_error_code,
                    'isFinal' => $isFinalErrorCode,
                ]);

            $this->repo->saveOrFail($payment);

            $customProperties = [
                'is_pushed_to_kafka'  => $payment->getIsPushedToKafka(),
            ];

            $this->app['diag']->trackVerifyPaymentEvent(EventCode::PAYMENT_VERIFICATION_FILTERED_FINAL_FAILURE, $payment, null, $customProperties);
        }

        if ($isOptimizerPayment === true)
        {
            $this->trace->info(TraceCode::PAYMENT_VERIFY_OPTIMIZER_CHECK,
                [
                    'payment_id' => $payment->getId(),
                    'error_code_non_verifiable' => $isFinalErrorCode,
                    'terminal_id' => $payment->terminal->getId(),
                    'terminal_type_array' => $terminalTypeArray,
                ]);
        }

        return $isFinalErrorCode;
    }
}
