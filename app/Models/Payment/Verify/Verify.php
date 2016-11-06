<?php

namespace RZP\Models\Payment\Verify;

use App;
use Config;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;
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
        0 => 900,           // 15 Minutes
        1 => 3600,          // 60 Minutes
        2 => 86400,         // 1 Day
        3 => 172800,        // 2 Day
        4 => 259200,        // 3 Day
        5 => 345600,        // 4 Day
        6 => 432000,        // 5 Day
        7 => 518400,        // 6 Day
        8 => 604800,        // 7 Day
        // TODO: Decide on the boundaries.
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
     * Minimum duration a payment should be old before it gets picked up
     * for verify for a particular payment verify filter.
     */
    const MINIMUM_TIME_MAP = [
        Filter::PAYMENTS_FAILED     => self::FAILURE_MIN_TIME,
        Filter::PAYMENTS_CREATED    => self::CREATED_MIN_TIME,
        Filter::VERIFY_FAILED       => self::ERRORED_MIN_TIME,
        Filter::VERIFY_ERROR        => self::ERRORED_MIN_TIME,
    ];

    // ================== End Configurations ==================

    protected $core;
    protected $mutex;
    protected $slack;
    protected $route;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->slack = $this->app['slack'];

        $this->route = $this->app['api.route']->getCurrentRouteName();
    }

    /**
     * Verify Payments Based on filter
     * For detailed Documentation refer to
     * https://docs.google.com/document/d/128BT3KYBRloYR85zaZODB5htUmG8JrGKKP6eGAGgW68
     *
     * @param  string $filter
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
    public function verifyPaymentsWithFilter($filter, $bucket = null)
    {
        Filter::isValidFilter($filter);

        $paymentStatus = $this->getPaymentStatusForFilter($filter);
        $verifyStatus = $this->getVerifyStatusForFilter($filter);

        $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

        $minimumTime = self::MINIMUM_TIME_MAP[$filter];

        $boundary = [];

        if (($filter === Filter::PAYMENTS_FAILED) or
            ($filter === Filter::VERIFY_FAILED))
        {
            // Return the proper boundary array
            $boundary = self::$failureStartBoundary;

            // Value being set here signifies end of boundary.
            // index is incremented while doing query
            // As we want to get Payments which have passed that boundary,
            // and should be verified.
            $boundary[-1] = $minimumTime;

            // If bucket filter is passed, get rid of other bucket values
            if ($bucket !== null)
            {
                $bucketEndTime = $boundary[$bucket - 1];

                $boundary = [$bucket - 1 => $bucketEndTime];
            }
        }

        $paymentsCollectionWithCount = $this->repo->payment->getPaymentsToVerify(
            $minimumTime, $boundary, $verifyStatus, $paymentStatus);

        $payments = $paymentsCollectionWithCount['payments'];

        $verifiableCount = $paymentsCollectionWithCount['verifiable_count'];

        return $this->verifyMultiplePayments($payments, $filter, $verifiableCount);
    }

    /**
     * @param Base\PublicCollection $payments
     * @param string                $filter
     * @param integer               $count
     * @return array with aggregated results
     */
    protected function verifyMultiplePayments(Base\PublicCollection $payments, $filter, $verifiableCount)
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
                $notApplicable += 1;
            }

            $this->releasePaymentAfterVerify($payment);
        }

        $verifyEnd = time();

        $times = [
            'start'             => $verifyStart,
            'end'               => $verifyEnd,
            'authorize_time'    => $totalAuthTimeDiff
        ];

        $summary = $this->processResult($resultSet, $times, $filter, $verifiableCount);

        $this->addDataToVerifySummary($summary, $lockedPayments, $notApplicable);

        $this->trace->info(
            TraceCode::VERIFY_PROCESSED_SUMMARY,
            $summary
        );

        $this->notifyInSlack($resultSet, $summary);

        return $summary;
    }

    protected function addDataToVerifySummary(array & $summary, $payments, $notApplicable)
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
     * @return array with keys locked and not_locked,
     *         having payments which are locked and not_locked respectively
     */
    protected function lockPaymentsForVerify(Base\PublicCollection $payments, $filter)
    {
        $paymentIds = $payments->pluck(Payment\Entity::ID);

        $lockedPaymentIds = $this->mutex->acquireMultiple($paymentIds, self::DEFAULT_LOCK_TIME, self::KEY_SUFFIX);

        $this->trace->info(
            TraceCode::VERIFY_LOCKED_PAYMENTS,
            [
                'payment_ids_locked'     => $lockedPaymentIds['locked'],
                'payment_ids_not_locked' => $lockedPaymentIds['unlocked'],
                'filter'                 => $filter,
            ]);

        $lockedPayments = $payments->whereIn(Payment\Entity::ID, $lockedPaymentIds['locked']);

        return $lockedPayments;
    }

    protected function releasePaymentAfterVerify(Payment\Entity $payment)
    {
        $this->mutex->release($payment->getId() . self::KEY_SUFFIX);
    }

    /** Process the result for displaying in slack and returning to caller
     *
     * @param array $result raw result array
     * @param $times
     * @param string $filter filter used to fetch payments
     * @return array with processed result
     */
    protected function processResult(array $result, $times, $filter, $verifiableCount)
    {
        $avgTimeDiff = 0;

        $totalVerifyTime = $times['end'] - $times['start'];

        if ($result[Result::AUTHORIZED] !== 0)
        {
            $avgTimeDiff = ($times['authorize_time'] / $result[Result::AUTHORIZED]);
        }

        $processedResults = [
            'filter'           => $filter,
            'verifiable_count' => $verifiableCount,
            'authorize_time'   => $avgTimeDiff,
            'total_time'       => $totalVerifyTime . ' secs'
        ];

        $processedResults = array_merge($processedResults, $result);

        return $processedResults;
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
            (($resultSet[Result::SUCCESS] > 4) or
             ($total !== $resultSet[Result::SUCCESS])))
        {
            // Drop all false values (NULL, 0, "")
            $slackArray = array_filter($summary);

            $message = 'Payment verify result';

            $this->slack->queue(
                $message,
                $slackArray,
                [
                    'channel' => Config::get('slack.channels.tech_logs')
                ]
            );
        }
    }

    public function verifyPayment(Payment\Entity $payment, $filter = null)
    {
        $result = Result::SUCCESS;

        $merchant = $payment->merchant;

        $cron = $this->app['basicauth']->isCron();


        // If filter is null, then verify is initiated manually, not via cron
        // Don't update VERIFY_BUCKET, in that case
        if (($cron === true) and
            ($this->route === 'payment_verify_multiple'))
        {
            $nextVerifyBucket = $this->getPaymentNextVerifyBucket($payment, $filter);

            $payment->setVerifyBucket($nextVerifyBucket);
        }

        //
        // Exception is thrown when the there's a mismatch
        // between payment status and status returned by gateway.
        // Most cases, this would mean that the payment is in failed
        // state and gateway returned back status authorized.
        //
        try
        {
            $response = $this->processor($merchant)->verify($payment);
        }
        catch (Exception\PaymentVerificationException $e)
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
                        [
                            'payment_id'    => $payment->getId(),
                            'status'        => $payment->getStatus(),
                            'verify_bucket' => $payment->getVerifyBucket(),
                            'error_message' => $ex->getMessage(),
                        ]);

                    return null;
                }
            }

            // Now Just continue
            $result = Result::AUTHORIZED;
        }
        catch (Exception\GatewayTimeoutException $e)
        {
            $this->trace->info(
                TraceCode::GATEWAY_REQUEST_TIMEOUT,
                ['payment_id' => $payment->getId()]);

            // Just continue
            $result = Result::TIMEOUT;
        }
        catch (\Exception $e)
        {
            // @note: If payment verification fails due to any reason
            // other than expected ones, we should log it as an error
            // exception.

            $this->trace->traceException($e);

            // Just continue
            $result = Result::ERROR;
        }
        catch (\Error $e)
        {
            // @note: If payment verification fails due to any reason
            // other than expected ones, we should log it as an error
            // exception.
            $this->trace->traceError($e);

            // Just continue
            $result = Result::ERROR;
        }

        $this->trace->info(
            TraceCode::PAYMENT_VERIFY_RESULT,
            [
                'payment_id'    => $payment->getId(),
                'result'        => $result,
            ]);

        return $result;
    }

    protected function getPaymentStatusForFilter($filter)
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

    protected function getVerifyStatusForFilter($filter)
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
    protected function getCurrentVerifyBucket($diff, $boundaries)
    {
        $currentVerifyBucket = $verifyBucket = 0;

        foreach ($boundaries as $boundary)
        {
            $verifyBucket += 1;

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

    protected function getPaymentNextVerifyBucket($payment, $filter)
    {
        // For Payment in created state and payment having verified as error,
        // verify bucket should be 0
        if (($filter === Filter::PAYMENTS_CREATED) or
            ($filter === Filter::VERIFY_ERROR))
        {
            return 0;
        }

        // Get Verify Boundary to update Verify Bucket
        $boundaries = $this->getBoundaryInSeconds($filter);

        $diff = Carbon::now('Asia/Kolkata')->timestamp - $payment->getCreatedAt();

        $currentVerifyBucket = $this->getCurrentVerifyBucket($diff, $boundaries);

        $nextVerifyBucket = $currentVerifyBucket + 1;

        return $nextVerifyBucket;
    }

    /**
     * @param string $filter filter for which boundary has to be returned
     * @return array verify boundary array
     * @throws Exception\LogicException
     */
    public function getBoundaryInSeconds($filter, $bucket = null)
    {
        switch ($filter)
        {
            case Filter::VERIFY_FAILED:
            case Filter::PAYMENTS_FAILED:
                // Return the proper boundary array
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
        $processor = new Payment\Processor\Processor($merchant);

        return $processor;
    }
}
