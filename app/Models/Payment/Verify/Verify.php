<?php

namespace RZP\Models\Payment\Verify;

use App;
use Config;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Base;
use RZP\Exception;
use RZP\Trace\TraceCode;

class Verify extends Base\Core
{
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
        0 => 900,       // 15 Minutes
        1 => 1800,      // 30 Minutes
        2 => 3600,      // 60 Minutes
        3 => 21600,     // 6 hours
        4 => 86400,     // 1 Day
        5 => 172800,    // 2 Day
        6 => 259200,    // 3 Day
        7 => 345600,    // 4 Day
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
     * Cache key prefix for storing gateway timeout values
     */
    const GATEWAY_TIMEOUT_CACHE_KEY_PREFIX = 'verify_timeout_block';

    /**
     * Cache key used to store gateway block info in hash map
     */
    const GATEWAY_BLOCK_CACHE_KEY = 'gateway_block_cache';

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
    protected $slackChannel;
    protected $route;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->redis = $this->app['redis'];

        $this->slack = $this->app['slack'];

        $this->route = $this->app['api.route']->getCurrentRouteName();

        $this->slackChannel = Config::get('slack.channels.tech_logs_verify');
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
    public function verifyPaymentsWithFilter(string $filter, array $bucketFilter = [])
    {
        $verifyFetchStartTime = time();

        $timeBoundary = $this->getStartAndEndTimeForVerify($filter);

        $paymentStatus = $this->getPaymentStatusForFilter($filter);

        $verifyStatus = $this->getVerifyStatusForFilter($filter);

        $boundary = $this->getBoundaryForVerify($filter, $bucketFilter, $timeBoundary);

        $disabledGateways = $this->getBlockedGateways();

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
                                                                $disabledGateways);

        $payments = $paymentsCollectionWithCount['payments'];

        $verifiableCount = $paymentsCollectionWithCount['verifiable_count'];

        $verifyFetchEndTime = time();

        $verifyFetchTime = $verifyFetchEndTime - $verifyFetchStartTime;

        return $this->verifyMultiplePayments($payments, $filter, $bucketFilter, $verifiableCount, $verifyFetchTime);
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
        int $verifyFetchTime)
    {
        $resultSet = [
            Result::AUTHORIZED    => 0,
            Result::SUCCESS       => 0,
            Result::TIMEOUT       => 0,
            Result::ERROR         => 0,
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

        $this->trace->info(
            TraceCode::VERIFY_PROCESSED_SUMMARY,
            $summary
        );

        $this->notifyInSlack($resultSet, $summary);

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

    /** Notify Processed Data in slack
     *
     * @param array $resultSet        raw result array
     * @param array $summary processed result array
     * @return void
     */
    protected function notifyInSlack(array $resultSet, array $summary)
    {
        $total = array_sum($resultSet);

        if (($total !== 0) and
            (($resultSet[Result::SUCCESS] > self::LOGGING_THRESHOLD) or
             ($total !== $resultSet[Result::SUCCESS])))
        {
            // Drop all false values (NULL, 0, "", [])
            $slackArray = array_filter($summary);

            $message = 'Payment verify result';

            $this->slack->queue(
                $message,
                $slackArray,
                [
                    'channel' => $this->slackChannel
                ]
            );
        }
    }

    public function verifyPayment(Payment\Entity $payment, string $filter = null)
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
            $this->processor($merchant)->verify($payment);

            $this->updateVerifyBucket($payment, $filter, self::NEXT);
        }
        catch (Exception\PaymentVerificationException $e)
        {
            $action = $e->getAction();

            $result = Result::ERROR;

            switch ($action)
            {
                case Action::BLOCK:
                    $this->blockGatewayForVerify($payment->getGateway());

                    break;

                case Action::RETRY:

                    break;

                case Action::FINISH:
                    $this->updateVerifyBucket($payment, $filter, self::LAST);

                    break;

                default:
                    $this->updateVerifyBucket($payment, $filter, self::NEXT);

                    $result = $this->authorizePayment($merchant, $payment, $e);

                    break;
            }
        }
        catch (Exception\GatewayTimeoutException $e)
        {
            $this->checkForPreviousTimeoutAndBlockGatewayIfApplicable($payment);

            $this->updateVerifyBucket($payment, $filter, self::NEXT);

            $this->trace->info(
                TraceCode::GATEWAY_REQUEST_TIMEOUT,
                ['payment_id' => $payment->getId()]);

            // Just continue
            $result = Result::TIMEOUT;
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

    protected function checkForPreviousTimeoutAndBlockGatewayIfApplicable(Payment\Entity $payment)
    {
        $currentTimestamp = Carbon::now()->getTimestamp();

        $currentTimestampBucket = (int)($currentTimestamp / self::GATEWAY_TIMEOUT_BUCKET_INTERVAL);

        $gateway = $payment->getGateway();

        $key = self::GATEWAY_TIMEOUT_CACHE_KEY_PREFIX;

        $key .= '_' . $gateway . '_' . $currentTimestampBucket;

        $timedOutPaymentsCount = (int) $this->redis->incr($key);

        if ($timedOutPaymentsCount >= self::GATEWAY_TIMEOUT_THRESHOLD)
        {
            $this->blockGatewayForVerify($gateway);
        }
    }

    protected function blockGatewayForVerify(string $gateway)
    {
        $this->trace->info(
            TraceCode::VERIFY_GATEWAY_BLOCK,
            ['gateway' => $gateway]
        );
        $this->redis->hSet(
            self::GATEWAY_BLOCK_CACHE_KEY,
            $gateway,
            Carbon::now()->getTimestamp() + self::GATEWAY_BLOCK_TIME
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
            throw new Exception\LogicException(
                "Should not have reached here. apiSuccess cannot be true when gatewaySuccess is false.",
                null,
                $verify->getDataToTrace());
        }
        else
        {
            try
            {
                // Attempt to authorize payments whose verification failed
                $this->processor($merchant)->authorizeFailedPayment($payment);
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
            ($this->route === 'payment_verify_multiple'))
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
        // For Payment having verified as error,
        // verify bucket should be 0
        if ($filter === Filter::VERIFY_ERROR)
        {
            return 0;
        }

        $diff = Carbon::now()->getTimestamp() - $payment->getCreatedAt();

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

            default:
                throw new Exception\LogicException(
                    'Invalid param for setting verify bucket');
        }

        $nextVerifyBucket = $currentVerifyBucket + 1;

        return $nextVerifyBucket;
    }

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
}
