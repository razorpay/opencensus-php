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
     * Verify will run for all the created payments every 2 minutes.
     * All the created payments will be converted to failed in 10 minutes via timeout cron.
     * Hence, at max, verify for the payment (when it is in created state) will be run 5 times.
     */
    protected static $createdStartBoundary = [
        120,           // 2 Minutes
    ];

    /**
     * For all the payments which are in failed state,
     * verify for the payment will be run once for in every boundary bucket.
     */
    protected static $failureStartBoundary = [
        900,           // 15 Minutes
        2600,          // 60 Minutes
        86400,         // 1 Day
        172800,        // 2 Day
        259200,        // 3 Day
        345600,        // 4 Day
        432000,        // 5 Day
        518400,        // 6 Day
        604800,        // 7 Day
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

    // TODO: This is present here to ensure backward compatibility and
    // should be removed after the required changes in the cron are made.
    const FAILURE_MIN_TIME = 120;  // 2 Minutes

    /**
     * This is the minimum time for which the payment should be in
     * failed state, before we run a "failed/error" verify on it.
     */
    const ERRORED_MIN_TIME = 0; // 0 Minute

    // ================== End Configurations ==================

    protected $core;
    protected $mutex;
    protected $slack;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->slack = $this->app['slack'];
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
    public function verifyPaymentsWithFilter($filter)
    {
        $verifyStatus = null;

        $paymentStatus = null;

        $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

        switch($filter)
        {
            case 'all':
            case Filter::PAYMENTS_FAILED:
                $paymentStatus = Payment\Status::FAILED;
                $minimumTime = $currentTime - self::FAILURE_MIN_TIME;
                break;

            case 'created':
            case Filter::PAYMENTS_CREATED:
                $paymentStatus = Payment\Status::CREATED;
                $minimumTime = $currentTime - self::CREATED_MIN_TIME;
                break;

            case 'failed':
            case Filter::VERIFY_FAILED:
                $verifyStatus = Status::FAILED;
                $minimumTime = $currentTime - self::ERRORED_MIN_TIME;
                break;

            case 'error':
            case Filter::VERIFY_ERROR:
                $verifyStatus = Status::ERROR;
                $minimumTime = $currentTime - self::ERRORED_MIN_TIME;
                break;

            default:
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_PARAMETERS, 'filter', $filter);
        }

        $boundary = $this->getBoundaryInSeconds($filter);

        $payments = $this->repo->payment->getPaymentsToVerify(
            $minimumTime, $boundary, $verifyStatus, $paymentStatus);

        return $this->verifyMultiplePayments($payments, $filter);
    }

    /**
     * @param Base\PublicCollection $payments
     * @param string                $filter
     * @return array with aggregated results
    */
    protected function verifyMultiplePayments(Base\PublicCollection $payments, $filter)
    {
        $resultSet = [
            Result::AUTHORIZED    => 0,
            Result::SUCCESS       => 0,
            Result::TIMEOUT       => 0,
            Result::ERROR         => 0,
        ];

        $lockedPayments = $this->lockPaymentsForVerify($payments);

        $this->trace->info(
            TraceCode::VERIFY_LOCKED_PAYMENTS,
            [
                'payment_ids' => $lockedPayments,
                'filter'      => $filter,
            ]);

        $totalAuthTimeDiff = 0;

        $verifyStart = time();

        foreach ($lockedPayments as $payment)
        {
            $verifyResult = $this->verifyPayment($payment, $filter);

            if ($verifyResult === Result::AUTHORIZED)
            {
                $totalAuthTimeDiff += (time() - $payment->getCreatedAt());
            }

            $resultSet[$verifyResult] += 1;

            $this->releasePaymentAfterVerify($payment);
        }

        $verifyEnd = time();

        $times = [
            'start'             => $verifyStart,
            'end'               => $verifyEnd,
            'authorize_time'    => $totalAuthTimeDiff
        ];

        $summary = $this->processResult($resultSet, $times, $filter);

        $this->trace->info(
            TraceCode::VERIFY_PROCESSED_SUMMARY,
            $summary
        );

        $this->notifyInSlack($resultSet, $summary);

        return $summary;
    }

    /** Lock All Payments

     * @param Base\PublicCollection $payments
     * @return array with keys locked and not_locked,
     *         having payments which are locked and not_locked respectively
     */
    protected function lockPaymentsForVerify(Base\PublicCollection $payments)
    {
        $paymentIds = $payments->pluck(Payment\Entity::ID);

        $lockedPaymentIds = $this->mutex->acquireMultiple($paymentIds, 3600, self::KEY_SUFFIX);

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
    protected function processResult(array $result, $times, $filter)
    {
        $avgTimeDiff = 0;

        $totalVerifyTime = $times['end'] - $times['start'];

        if ($result[Result::AUTHORIZED] !== 0)
        {
            $avgTimeDiff = ($times['authorize_time'] / $result[Result::AUTHORIZED]);
        }

        $processedResults = [
            'filter'           => $filter,
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

        $cron = ($this->app['basicauth']->getInternalApp() === 'cron');

        $route = $this->app['api.route']->getCurrentRouteName();

        // For Payment in created state, verify bucket should not be updated
        // as we want to run cron on specific interval, till payment is marked as failed/authorized
        // If filter is null, then verify is initiated manually, not via cron
        // Don't update VERIFY_BUCKET, in that case
        if (($payment->getStatus() !== Payment\Status::CREATED) and
            ($cron === true) and ($route === 'payment_verify_multiple_post'))
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
            $res = $this->processor($merchant)->verify($payment);
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
                // Attempt to authorize payments whose verification failed
                $this->processor($merchant)->authorizeFailedPayment($payment);
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

        $this->trace->info(
            TraceCode::PAYMENT_VERIFY_RESULT,
            [
                'payment_id'    => $payment->getId(),
                'result'        => $result,
            ]);

        return $result;
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
        // For Payment in created state, verify bucket should not be updated
        // as we want to run cron on specific interval, till payment is marked as failed/authorized
        // If filter is null, then verify is initiated manually, not via cron
        // Don't update VERIFY_BUCKET, in that case

        // Get Verify Boundary to update Verify Bucket
        $boundaries = $this->getBoundaryInSeconds($filter);

        $diff = Carbon::now('Asia/Kolkata')->timestamp - $payment->getCreatedAt();

        $currentVerifyBucket = $this->getCurrentVerifyBucket($diff, $boundaries);

        return $nextVerifyBucket = $currentVerifyBucket + 1;
    }

    /**
     * @param string $filter filter for which boundary has to be returned
     * @return array verify boundary array
     * @throws Exception\LogicException
     */
    public function getBoundaryInSeconds($filter)
    {
        switch($filter)
        {
            // TODO: remove 'created', 'failure', 'error' and 'all' filter
            case 'created':
            case Filter::PAYMENTS_CREATED:
                $boundaries = self::$createdStartBoundary;
                break;

            case 'failure':
            case 'error':
            case Filter::VERIFY_ERROR:
            case Filter::VERIFY_FAILED:
            case 'all':
            case Filter::PAYMENTS_FAILED:
                $boundaries = self::$failureStartBoundary;
                break;

            default:
                throw new Exception\LogicException('Unknown filter provided.', null, ['filter' => $filter]);
        }

        return $boundaries;
    }

    protected function processor($merchant = null)
    {
        $processor = new Payment\Processor\Processor($merchant);

        return $processor;
    }
}
