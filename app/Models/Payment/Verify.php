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

    public function __construct($mode, $trace)
    {
        $this->app = App::getFacadeRoot();

        $this->mode = $mode;

        $this->trace = $trace;

        $this->core = new Payment\Core;

        $this->paymentRepo = $this->app['repo']->payment;

        $this->mutex = $this->app['api.mutex'];
    }

    public function verifyPaymentsWithFilter($filter)
    {
        if ($filter === 'all')
        {
            return $this->verifyAllPayments();
        }
        else if ($filter === 'failed')
        {
            return $this->verifyPaymentsWithFailedVerifyResult();
        }
        else if ($filter === 'error')
        {
            return $this->verifyPaymentsWithErrorVerifyResult();
        }
        else if ($filter === 'created')
        {
            return $this->verifyPaymentsWithCreatedStatus();
        }
        else
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_PARAMETERS, $filter);
        }
    }

    public function verifyPaymentsWithFailedVerifyResult()
    {
        $payments = $this->paymentRepo->get50PaymentsWithVerifyResult(VerifyResult::FAILED);

        $payments->shuffle();

        return $this->verifyMultiplePayments($payments, 'failed');
    }

    public function verifyPaymentsWithErrorVerifyResult()
    {
        $payments = $this->paymentRepo->get50PaymentsWithVerifyResult(VerifyResult::ERROR);

        $payments->shuffle();

        return $this->verifyMultiplePayments($payments, 'error');
    }

    public function verifyPaymentsWithCreatedStatus()
    {
        $ts = time() - (int) (2.5 * 60);

        $payments = $this->paymentRepo->getPaymentsWithCreatedStatusForVerification($ts);

        return $this->verifyMultiplePayments($payments, 'created');
    }

    public function verifyAllPayments()
    {
        $currentTime  = time();

        $ts = $currentTime - Constants\Verify::MIN_TIME_BEFORE_VERIFY;

        $boundary = Constants\Verify::getBoundayInSeconds();

        $boundaryQueryData = [];

        foreach ($boundary as $key=> $value)
        {
            $boundaryQueryData[$key] = time() - $value;
        }

        $payments = $this->paymentRepo->getUnverifiedPayments($ts, $boundaryQueryData);

        return $this->verifyMultiplePayments($payments, 'all');
    }

    public function verifyMultiplePayments($payments, $filter)
    {
        $timedOut = $verified = $failed = $authorized = $error = 0;

        $time = time();

        $timeDiff = 0;

        $verifyLockKeys = [];

        $strict = false;

        foreach ($payments as $payment)
        {
            $verifyLockKeys[] = $payment->getId() . "_verify";
        }

        $verifyKeys = $this->mutex->acquireMultiple($verifyLockKeys, 86400, $strict);

        foreach ($payments as $payment)
        {
            if (in_array($payment->getId() . "_verify", $verifyKeys['locked']) === false)
            {
                continue;
            }

            $res = $this->verifyPayment($payment);

            switch ($res)
            {
                case Constants\Verify::SUCCESS:
                    $verified++;
                    break;

                case Constants\Verify::TIMEOUT:
                    $timedOut++;
                    break;

                case Constants\Verify::AUTHORIZED:
                    $failed++;
                    $timeDiff += time() - $payment->getCreatedAt();
                    $authorized++;
                    break;

                case Constants\Verify::ERROR:
                    $error++;
                    break;

                default:
                    throw new Exception\LogicException(
                        'Unknown result code: ' . $res);
            }
        }

        $this->mutex->releaseMultiple($verifyKeys['locked']);

        $totalTime = time() - $time;

        $avgTimeDiff = 0;

        if ($authorized !== 0)
        {
            $avgTimeDiff = (int) ($timeDiff / $authorized);
        }

        $results = [
            'filter'            => $filter,
            'verified'          => $verified,
            'failed'            => $failed,
            'authorized'        => $authorized,
            'timed out'         => $timedOut,
            'error'             => $error,
            'authorizedTime'    => $avgTimeDiff,
            'totalTime'         => $totalTime . ' secs'
        ];

        $message = 'Payment verify result';

        $total = $timedOut + $verified + $authorized + $error;

        if (($total !== 0) and
            (($verified > 4) or
             ($total !== $verified)))
        {
            // Drop all false values (NULL, 0, "")
            $slackArray = array_filter($results);

            $this->app['slack']->queue(
                $message,
                $slackArray,
                [
                    'channel' => Config::get('slack.channels.tech_logs')
                ]
            );
        }

        return $results;
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
