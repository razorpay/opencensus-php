<?php

namespace Models\Payment;

use EE\Exception;
use Models\Base;
use Models\Card;
use Models\Merchant;
use Models\Payment;
use Models\Transaction;
use Services\SlackPoster;
use Trace\Trace;
use Trace\TraceCode;

class Verify
{
    use SlackPoster;

    const MIN_TIME_BEFORE_VERIFY = 120; // 1 minute

    public function __construct($mode, $trace, $exceptionHandler)
    {
        $this->mode = $mode;
        $this->trace = $trace;
        $this->core = new Payment\Core;
        $this->exceptionHandler = $exceptionHandler;
        $this->repo = new Payment\Repository;
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
            ;
        }
    }

    public function verifyPaymentsWithFailedVerifyResult()
    {
        $payments = $this->repo->get50PaymentsWithVerifyResult(VerifyResult::FAILED);

        return $this->verifyMultiplePayments($payments, 'failed');
    }

    public function verifyPaymentsWithErrorVerifyResult()
    {
        $payments = $this->repo->get50PaymentsWithVerifyResult(VerifyResult::ERROR);

        return $this->verifyMultiplePayments($payments, 'error');
    }

    public function verifyPaymentsWithCreatedStatus()
    {
        $ts = time() - 4 * 60;

        $payments = $this->repo->getPaymentsWithCreatedStatusForVerification($ts);

        return $this->verifyMultiplePayments($payments, 'created');
    }

    public function verifyAllPayments()
    {
        $ts = time() - self::MIN_TIME_BEFORE_VERIFY;

        $payments = $this->repo->getUnverifiedPayments($ts);

        return $this->verifyMultiplePayments($payments, 'all');
    }

    public function verifyMultiplePayments($payments, $filter)
    {
        $timedOut = 0; $verified = 0; $failed = 0; $authorized = 0; $error = 0;
        $time = time();

        $timeDiff = 0;

        foreach ($payments as $payment)
        {
            $merchant = $payment->merchant;

            try
            {
                $res = $this->processor($merchant)->verify($payment);

                $verified++;
            }
            catch (Exception\PaymentVerificationException $e)
            {
                $failed++;

                // Attempt to authorize payments whose verification failed
                $this->processor($merchant)->authorizeFailedPayment($payment);

                $timeDiff += $time - $payment->getCreatedAt();

                $authorized++;

                // Now Just continue
            }
            catch (Exception\GatewayTimeoutException $e)
            {
                $this->trace->info(
                    TraceCode::GATEWAY_REQUEST_TIMEOUT,
                    ['payment_id' => $payment->getId()]);

                // Just continue
                $timedOut++;
            }
            catch (\Exception $e)
            {
                // @note: If payment verification fails due to any reason
                // other than expected ones, we should log it as an error
                // exception.
                //
                // If for eg, exception is BadRequestException, then it won't
                // get logged by global handler because it's not a critical
                // exception but in this context it really shouldn't have
                // occurred.

                $this->exceptionHandler->traceException($e);

                // Just continue
                $error++;
            }
        }

        $totalTime = time() - $time;

        $avgTimeDiff = 0;

        if ($authorized !== 0)
        {
            $avgTimeDiff = $timeDiff / $authorized;
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
            $this->slackPost($message, $slackArray, ['channel' => '#tech_logs']);
        }

        return $results;
    }

    protected function processor($merchant = null)
    {
        $bindings = $this->getBindings($merchant);

        return Payment\Processor\Processor::create($bindings);
    }

    protected function getBindings(Merchant\Entity $merchant = null)
    {
        $trace = \Trace::getFacadeRoot();

        $bindings = array(
            'merchant'  => $merchant,
            'core'      => $this->core,
            'trace'     => $this->trace,
            'mode'      => $this->mode);

        return $bindings;
    }
}
