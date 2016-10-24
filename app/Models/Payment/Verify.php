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

class Verify
{
    protected $trace;
    protected $mode;
    protected $core;
    protected $paymentRepo;
    protected $mutex;
    protected $slack;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->mode = $app['rzp.mode'];

        $this->trace = $app['trace'];

        $this->paymentRepo = $app['repo']->payment;

        $this->mutex = $app['api.mutex'];

        $this->slack = $app['slack'];
    }

    /* Verify Payments Based on filter
     *
     * @param  string $filter filter
     *
     * @return return aggregrated result of verify results
     *         Sample Result
     *         [
     *          'filter'            => <filter>,
     *          'verified'          => <count>,
     *          'authorized/failed' => <count>,
     *          'timed out'         => <count>,
     *          'error'             => <count>,
     *          'authorizedTime'    => <time>,
     *          'totalTime'         => <time>
     *         ]
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
                $ts = Carbon::now()->timestamp - Constants\Verify::FAILURE_MIN_TIME;
                break;

            case 'created':
            case Constants\Verify::PAYMENTS_CREATED:
                $paymentStatus = Payment\Status::CREATED;
                $ts = Carbon::now()->timestamp - Constants\Verify::CREATED_MIN_TIME;
                break;

            case 'failed':
                $verifyStatus = Constants\Verify::VERIFIED_FAILED;
                $ts = Carbon::now()->timestamp - Constants\Verify::ERRORED_MIN_TIME;
                break;

            case 'error':
            case Constants\Verify::VERIFY_ERROR:
                $verifyStatus = Constants\Verify::VERIFIED_ERROR;
                $ts = Carbon::now()->timestamp - Constants\Verify::ERRORED_MIN_TIME;
                break;

            default:
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_PARAMETERS, 'filter', $filter);
        }

        $boundary = Constants\Verify::getBoundaryInSeconds($filter);

        $boundaryQueryData = [];

        // $boundary have time in seconds, signifying payment should be X second old
        // For querying on db, need to change that to absolute value
        foreach ($boundary as $key => $value)
        {
            $boundaryQueryData[$key] = Carbon::now()->timestamp - $value;
        }

        $payments = $this->paymentRepo->getPaymentsToVerify($ts, $boundaryQueryData, $verifyStatus, $paymentStatus, true);

        return $this->verifyMultiplePayments($payments, $filter);
    }

    /*
     * @param Base\PublicCollection $payments
     * @param string                $filter
     * @return array with aggregrated results
    */
    public function verifyMultiplePayments(Base\PublicCollection $payments, $filter)
    {
        $result = [
            Constants\Verify::AUTHORIZED    => 0,
            Constants\Verify::SUCCESS       => 0,
            Constants\Verify::TIMEOUT       => 0,
            Constants\Verify::ERROR         => 0,
            'verifyStartTime'               => time(),
            'timeDiff'                      => 0,
        ];

        $verifyKeys = $this->lockPaymentsForVerify($payments);

        foreach ($payments as $payment)
        {
            if (in_array($payment->getId() . Constants\Verify::KEY_SUFFIX, $verifyKeys['locked']) === false)
            {
                // If a payment cannot be locked for verify, don't run verify for those payments
                continue;
            }

            $verifyStatus = $this->verifyPayment($payment, $filter);

            if ($verifyStatus === Constants\Verify::AUTHORIZED)
            {
                $result['timeDiff'] += (time() - $payment->getCreatedAt());
            }

            $result[$verifyStatus] += 1;
        }

        $this->releasePaymentsAfterVerify($verifyKeys['locked']);

        $processedResults = $this->processResult($result, $filter);

        $this->notifyInSlack($result, $processedResults);

        return $processedResults;
    }

    /* Lock All Paymnets
     * @param Base\PublicCollection $payments
     * @return array with keys locked and not_locked,
     *         having payments which are locked and not_locked respectively
    */
    protected function lockPaymentsForVerify(Base\PublicCollection $payments)
    {
        $verifyLockKeys = [];

        $strict = false;

        foreach ($payments as $payment)
        {
            $verifyLockKeys[] = $payment->getId() . Constants\Verify::KEY_SUFFIX;
        }

        $verifyKeys = $this->mutex->acquireMultiple($verifyLockKeys, 3600, $strict);

        return $verifyKeys;
    }

    /* Release lock on all payments id lcoked for verify
     * @param array $lockedKeys array containing all keys which are locked
     * @return void
    */
    protected function releasePaymentsAfterVerify(array $lockedKeys)
    {
        $this->mutex->releaseMultiple($lockedKeys);
    }

    /* Process the result for displaying in slack and returning to caller
     * @param arary  $result  raw result array
     * @param string $filter  filter used to fetch payments
     * @return array with processed result
    */
    protected function processResult(array $result, $filter)
    {
        $avgTimeDiff = 0;

        $totalTime = time() - $result['verifyStartTime'];

        if ($result[Constants\Verify::AUTHORIZED] !== 0)
        {
            $avgTimeDiff = (int) ($result['timeDiff'] / $result[Constants\Verify::AUTHORIZED]);
        }

        $processedResults = [
            'filter'            => $filter,
            'verified'          => $result[Constants\Verify::SUCCESS],
            'authorized/failed' => $result[Constants\Verify::AUTHORIZED],
            'timed out'         => $result[Constants\Verify::TIMEOUT],
            'error'             => $result[Constants\Verify::ERROR],
            'authorizedTime'    => $avgTimeDiff,
            'totalTime'         => $totalTime . ' secs'
        ];

        return $processedResults;
    }

    /* Notify Processed Data in slack
     * @param arary  $result           raw result array
     * @param string $processedResults processed result array
     * @return void
    */
    protected function notifyInSlack(array $result, array $processedResults)
    {
        unset($result['timeDiff']);

        unset($result['verifyStartTime']);

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

        //
        // Exception is thrown when the there's a mismatch
        // between payment status and status returned by gateway.
        // Most cases, this would mean that the payment is in failed
        // state and gateway returned back status authorized.
        //
        try
        {
            $res = $this->processor($merchant)->verify($payment, $filter);
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

    protected function processor($merchant = null)
    {
        $processor = new Payment\Processor\Processor($merchant);

        return $processor;
    }
}
