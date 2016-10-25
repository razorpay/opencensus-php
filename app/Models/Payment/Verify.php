<?php

namespace RZP\Models\Payment;

use App;
use Config;

use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Transaction;
use RZP\Exception;
use RZP\Trace\TraceCode;

class Verify
{
    const MIN_TIME_BEFORE_VERIFY = 120; // 2 minutes

    const SUCCESS       = 'success';
    const ERROR         = 'error';
    const AUTHORIZED    = 'authorized';
    const TIMEOUT       = 'timeout';

    protected $trace;
    protected $mode;
    protected $core;
    protected $paymentRepo;

    public function __construct($mode, $trace)
    {
        $app = App::getFacadeRoot();

        $this->mode = $mode;

        $this->trace = $trace;

        $this->core = new Payment\Core;

        $this->paymentRepo = $app['repo']->payment;

        $this->app = $app;
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

        $payments = $payments->shuffle();

        return $this->verifyMultiplePayments($payments, 'failed');
    }

    public function verifyPaymentsWithErrorVerifyResult()
    {
        $payments = $this->paymentRepo->get50PaymentsWithVerifyResult(VerifyResult::ERROR, true);

        $payments = $payments->shuffle();

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
        $ts = time() - self::MIN_TIME_BEFORE_VERIFY;

        $payments = $this->paymentRepo->getUnverifiedPayments($ts);

        $payments = $payments->shuffle();

        return $this->verifyMultiplePayments($payments, 'all');
    }

    public function verifyMultiplePayments($payments, $filter)
    {
        $timedOut = $verified = $failed = $authorized = $error = 0;

        $time = time();

        $timeDiff = 0;

        foreach ($payments as $payment)
        {
            $res = $this->verifyPayment($payment);

            switch ($res)
            {
                case self::SUCCESS:
                    $verified++;
                    break;

                case self::TIMEOUT:
                    $timedOut++;
                    break;

                case self::AUTHORIZED:
                    $failed++;
                    $timeDiff += $time - $payment->getCreatedAt();
                    $authorized++;
                    break;

                case self::ERROR:
                    $error++;
                    break;

                default:
                    throw new Exception\LogicException(
                        'Unknown result code: ' . $res);
            }
        }

        $totalTime = time() - $time;

        $avgTimeDiff = 0;

        if ($authorized !== 0)
        {
            $avgTimeDiff = (int) ($timeDiff / $authorized);
        }

        $results = array(
            'filter'            => $filter,
            'verified'          => $verified,
            'failed'            => $failed,
            'authorized'        => $authorized,
            'timed out'         => $timedOut,
            'error'             => $error,
            'authorizedTime'    => $avgTimeDiff,
            'totalTime'         => $totalTime . ' secs');

        $message = 'Payment verify result';

        $total = $timedOut + $verified + $failed + $authorized + $error;

        if (($total !== 0) and
            (($verified > 4) or
             ($total !== $verified)))
        {
            // Drop all false values (NULL, 0, "")
            $slackArray = array_filter($results);
            $this->app['slack']->queue($message, $slackArray, ['channel' => Config::get('slack.channels.tech_logs')]);
        }

        return $results;
    }

    public function verifyPayment($payment)
    {
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

            return self::SUCCESS;
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
            return self::AUTHORIZED;
        }
        catch (Exception\GatewayTimeoutException $e)
        {
            $this->trace->info(
                TraceCode::GATEWAY_REQUEST_TIMEOUT,
                ['payment_id' => $payment->getId()]);

            // Just continue
            return self::TIMEOUT;
        }
        catch (\Exception $e)
        {
            // @note: If payment verification fails due to any reason
            // other than expected ones, we should log it as an error
            // exception.

            $this->trace->traceException($e);

            // Just continue
            return self::ERROR;
        }
    }

    protected function processor($merchant = null)
    {
        $processor = new Payment\Processor\Processor($merchant);

        return $processor;
    }
}
