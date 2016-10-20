<?php

namespace RZP\Models\Payment;

use App;
use Config;

use RZP\Constants;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;
use RZP\Exception;
use RZP\Trace\TraceCode;

class Verify
{
    protected $trace;
    protected $mode;
    protected $core;
    protected $paymentRepo;
    protected $mutex;
    protected $slackHandler;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->mode = $app['rzp.mode'];

        $this->trace = $app['trace'];

        $this->paymentRepo = $app['repo']->payment;

        $this->mutex = $app['api.mutex'];

        $this->slackHandler = $app['slack'];
    }

    /* Verify Payments Based on filter
     * params $filter - filter
     * returns Return aggregrated result of verify results
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
                $paymentStatus = Payment\Status::FAILED;
                break;

            case 'created':
                $paymentStatus = Payment\Status::CREATED;
                break;

            case 'failed':
                $verifyStatus = Constants\Verify::VERIFIED_FAILED;
                break;

            case 'error':
                $verifyStatus = Constants\Verify::VERIFIED_ERROR;
                break;

            default:
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_PARAMETERS, $filter);
        }

        $ts = time() - Constants\Verify::getMinimumTimeBeforeVerify($filter);

        $boundary = Constants\Verify::getBoundaryInSeconds($filter);

        $boundaryQueryData = [];

        // $boundary have time in seconds, signifying payment should be X second old
        // For querying on db, need to change that to absolute value
        foreach ($boundary as $key => $value)
        {
            $boundaryQueryData[$key] = time() - $value;
        }

        $payments = $this->paymentRepo->getPaymentsToVerify($ts, $boundaryQueryData, $verifyStatus, $paymentStatus);

        return $this->verifyMultiplePayments($payments, $filter);
    }

    public function verifyMultiplePayments($payments, $filter)
    {
        $result = [
            Constants\Verify::AUTHORIZED    => 0,
            Constants\Verify::SUCCESS       => 0,
            Constants\Verify::TIMEOUT       => 0,
            Constants\Verify::ERROR         => 0,
        ];

        $avgTimeDiff = 0;

        $verifyStartTime = time();

        $timeDiff = 0;

        $verifyLockKeys = [];

        $strict = false;

        foreach ($payments as $payment)
        {
            $verifyLockKeys[] = $payment->getId() . Constants\Verify::KEY_SUFFIX;
        }

        $verifyKeys = $this->mutex->acquireMultiple($verifyLockKeys, 86400, $strict);

        foreach ($payments as $payment)
        {
            if (in_array($payment->getId() . Constants\Verify::KEY_SUFFIX, $verifyKeys['locked']) === false)
            {
                // If a payment cannot be locked for verify,
                // Ignore the payment for running verify
                continue;
            }

            $verifyStatus = $this->verifyPayment($payment);

            if ($verifyStatus === Constants\Verify::AUTHORIZED)
            {
                $timeDiff += time() - $payment->getCreatedAt();
            }

            $result[$verifyStatus] += 1;
        }

        $this->mutex->releaseMultiple($verifyKeys['locked']);

        $totalTime = time() - $verifyStartTime;

        if ($result[Constants\Verify::AUTHORIZED] !== 0)
        {
            $avgTimeDiff = (int) ($timeDiff / $results[Constants\Verify::AUTHORIZED]);
        }

        $total = array_sum($result);

        $processedResults = [
            'filter'            => $filter,
            'verified'          => $result[Constants\Verify::SUCCESS],
            'authorized/failed' => $result[Constants\Verify::AUTHORIZED],
            'timed out'         => $result[Constants\Verify::TIMEOUT],
            'error'             => $result[Constants\Verify::ERROR],
            'authorizedTime'    => $avgTimeDiff,
            'totalTime'         => $totalTime . ' secs'
        ];

        if (($total !== 0) and
            (($result[Constants\Verify::SUCCESS] > 4) or
             ($total !== $result[Constants\Verify::SUCCESS])))
        {
            // Drop all false values (NULL, 0, "")
            $slackArray = array_filter($processedResults);

            $message = 'Payment verify result';

            $this->slackHandler->queue(
                $message,
                $slackArray,
                [
                    'channel' => Config::get('slack.channels.tech_logs')
                ]
            );
        }

        return $processedResults;
    }

    public function verifyPayment($payment)
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

    protected function processor($merchant = null)
    {
        $processor = new Payment\Processor\Processor($merchant);

        return $processor;
    }
}
