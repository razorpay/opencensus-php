<?php

namespace RZP\Models\Payment;

use App;
use Config;

use Carbon\Carbon;
use RZP\Constants;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;
use RZP\Models\Base;
use RZP\Exception;
use RZP\Trace\TraceCode;

class Verify extends Base\Core
{
    protected $trace;
    protected $mode;
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

        switch($filter)
        {
            case 'all':
            case Constants\Verify::PAYMENTS_FAILED:
                $paymentStatus = Payment\Status::FAILED;
                $minimumTime = Carbon::now('Asia/Kolkata')->timestamp - Constants\Verify::FAILURE_MIN_TIME;
                break;

            case 'created':
            case Constants\Verify::PAYMENTS_CREATED:
                $paymentStatus = Payment\Status::CREATED;
                $minimumTime = Carbon::now('Asia/Kolkata')->timestamp - Constants\Verify::CREATED_MIN_TIME;
                break;

            case 'failed':
                $verifyStatus = Constants\Verify::VERIFIED_FAILED;
                $minimumTime = Carbon::now('Asia/Kolkata')->timestamp - Constants\Verify::ERRORED_MIN_TIME;
                break;

            case 'error':
            case Constants\Verify::VERIFY_ERROR:
                $verifyStatus = Constants\Verify::VERIFIED_ERROR;
                $minimumTime = Carbon::now('Asia/Kolkata')->timestamp - Constants\Verify::ERRORED_MIN_TIME;
                break;

            default:
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_PARAMETERS, 'filter', $filter);
        }

        $boundary = Constants\Verify::getBoundaryInSeconds($filter);

        $payments = $this->repo->payment->getPaymentsToVerify(
                                    $minimumTime, $boundary, $verifyStatus, $paymentStatus);

        return $this->verifyMultiplePayments($payments, $filter);
    }

    /**
     * @param Base\PublicCollection $payments
     * @param string                $filter
     * @return array with aggregated results
    */
    public function verifyMultiplePayments(Base\PublicCollection $payments, $filter)
    {
        $result = [
            Constants\Verify::AUTHORIZED    => 0,
            Constants\Verify::SUCCESS       => 0,
            Constants\Verify::TIMEOUT       => 0,
            Constants\Verify::ERROR         => 0,
            'verify_start_time'             => time(),
            'time_diff'                     => 0,
        ];

        $lockedPayments = $this->lockPaymentsForVerify($payments);

        foreach ($lockedPayments as $payment)
        {
            $verifyStatus = $this->verifyPayment($payment, $filter);

            if ($verifyStatus === Constants\Verify::AUTHORIZED)
            {
                $result['time_diff'] += (time() - $payment->getCreatedAt());
            }

            $result[$verifyStatus] += 1;
        }

        $this->releasePaymentsAfterVerify($lockedPayments);

        $processedResults = $this->processResult($result, $filter);

        $this->notifyInSlack($result, $processedResults);

        return $processedResults;
    }

    /* Lock All Payments
     * @param Base\PublicCollection $payments
     * @return array with keys locked and not_locked,
     *         having payments which are locked and not_locked respectively
    */
    protected function lockPaymentsForVerify(Base\PublicCollection $payments)
    {
        $verifyLockKeys = [];

        $strict = false;

        $paymentIds = $payments->pluck(Entity::ID);

        $lockedPayments = $this->mutex->acquireMultiple(
            $paymentIds, 3600, $strict, Constants\Verify::KEY_SUFFIX);

        $payments->whereIn(Entity::ID, $lockedPayments);

        return $payments;
    }

    /* Release lock on all payments id lcoked for verify
     * @param array $lockedKeys array containing all keys which are locked
     * @return void
    */
    protected function releasePaymentsAfterVerify($payments)
    {
        $paymentIds = $payments->pluck(Entity::ID);

        $this->mutex->releaseMultiple($paymentIds, Constants\Verify::KEY_SUFFIX);
    }

    /* Process the result for displaying in slack and returning to caller
     * @param array  $result  raw result array
     * @param string $filter  filter used to fetch payments
     * @return array with processed result
    */
    protected function processResult(array $result, $filter)
    {
        $avgTimeDiff = 0;

        $totalTime = time() - $result['verify_start_time'];

        if ($result[Constants\Verify::AUTHORIZED] !== 0)
        {
            $avgTimeDiff = (int) ($result['time_diff'] / $result[Constants\Verify::AUTHORIZED]);
        }

        $processedResults = [
            'filter'            => $filter,
            'verified'          => $result[Constants\Verify::SUCCESS],
            'authorized/failed' => $result[Constants\Verify::AUTHORIZED],
            'timed_out'         => $result[Constants\Verify::TIMEOUT],
            'error'             => $result[Constants\Verify::ERROR],
            'authorized_time'   => $avgTimeDiff,
            'total_time'        => $totalTime . ' secs'
        ];

        return $processedResults;
    }

    /* Notify Processed Data in slack
     * @param array $result           raw result array
     * @param array $processedResults processed result array
     * @return void
    */
    protected function notifyInSlack(array $result, array $processedResults)
    {
        unset($result['time_diff']);

        unset($result['verify_start_time']);

        $total = array_sum($result);

        if (($total !== 0) and
            (($result[Constants\Verify::SUCCESS] > 4) or
             ($total !== $result[Constants\Verify::SUCCESS])))
        {
            // Drop all false values (NULL, 0, "")
            $slackArray = array_filter($processedResults);

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

    public function verifyPayment(Payment\Entity $payment, $filter)
    {
        $status = Constants\Verify::SUCCESS;

        $merchant = $payment->merchant;

        $cron = ($this->app['basicauth']->getAppName() === 'cron');

        // For Payment in created state, verify bucket should not be updated
        // as we want to run cron on specific interval, till payment is marked as failed/authorized
        // If filter is null, then verify is initiated manually, not via cron
        // Don't update VERIFY_BUCKET, in that case
        if (($payment->getStatus() !== Status::CREATED) and
            ($cron === true))
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
            $status = Constants\Verify::AUTHORIZED;
        }
        catch (Exception\GatewayTimeoutException $e)
        {
            $this->trace->info(
                TraceCode::GATEWAY_REQUEST_TIMEOUT,
                ['payment_id' => $payment->getId()]);

            // Just continue
            $status = Constants\Verify::TIMEOUT;
        }
        catch (\Exception $e)
        {
            // @note: If payment verification fails due to any reason
            // other than expected ones, we should log it as an error
            // exception.

            $this->trace->traceException($e);

            // Just continue
            $status = Constants\Verify::ERROR;
        }

        return $status;
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
        $boundaries = Constants\Verify::getBoundaryInSeconds($filter);

        $diff = Carbon::now('Asia/Kolkata')->timestamp - $payment->getCreatedAt();

        $currentVerifyBucket = $this->getCurrentVerifyBucket($diff, $boundaries);

        return $nextVerifyBucket = $currentVerifyBucket + 1;
    }

    protected function processor($merchant = null)
    {
        $processor = new Payment\Processor\Processor($merchant);

        return $processor;
    }
}
