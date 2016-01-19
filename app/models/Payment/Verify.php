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

    public function __construct($mode, $trace, $exceptionHandler)
    {
        $this->mode = $mode;
        $this->trace = $trace;
        $this->core = new Payment\Core;
        $this->exceptionHandler = $exceptionHandler;
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
        else
        {
            ;
        }
    }

    public function verifyPaymentsWithFailedVerifyResult()
    {
        $payments = (new Payment\Repository)->get50PaymentsWithVerifyResult(VerifyResult::FAILED);

        return $this->verifyMultiplePayments($payments);
    }

    public function verifyPaymentsWithErrorVerifyResult()
    {
        $payments = (new Payment\Repository)->get50PaymentsWithVerifyResult(VerifyResult::ERROR);

        return $this->verifyMultiplePayments($payments);
    }

    public function verifyAllPayments()
    {
        $ts = time() - 30 * 60;

        $payments = (new Payment\Repository)->getUnverifiedPayments($ts);

        return $this->verifyMultiplePayments($payments);
    }

    public function verifyMultiplePayments($payments)
    {
        $timedOut = 0; $verified = 0; $failed = 0; $authorized = 0; $error = 0;
        $time = time();

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

                $authorized++;

                // Now Just continue
            }
            catch (Exception\GatewayTimeoutException $e)
            {
                $this->trace->info(
                    TraceCode::GATEWAY_REQUESTY_TIMEOUT,
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

        $time = time() - $time;

        $results = array(
            'verified'      => $verified,
            'failed'        => $failed,
            'authorized'    => $authorized,
            'timed out'     => $timedOut,
            'error'         => $error,
            'total time'    => $time . ' secs');

        $message = 'Payment verify result';

        $total = $timedOut + $verified + $failed + $authorized + $error;

        if ($total !== 0)
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
