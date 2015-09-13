<?php

namespace Models\Payment;

use Carbon\Carbon;
use EE\Exception;

use Models\Base;
use Models\Merchant;
use Models\Payment;

use Trace\Trace;
use Trace\TraceCode;

class Service extends Base\Service
{
    protected $merchant;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Payment\Core();
    }

    /**
     * Processes a payment.
     */
    public function process(array $input)
    {
        return $this->processor()->process($input);
    }

    /**
     * Refunds a payment
     *
     * @param  string   $id
     *
     * @return Payment\Entity
     */
    public function refund($id, $input)
    {
        $refund = $this->processor()->refundCapturedPayment($id, $input);

        return $refund->toArrayPublic();
    }

    /**
     * Refunds a payment
     *
     * @param  string   $id
     *
     * @return Payment\Entity
     */
    public function refundAuthorized($id, $input)
    {
        $refund = $this->processor()->refundAuthorizedPayment($id, $input);

        return $refund->toArrayPublic();
    }

    public function verify($id)
    {
        $payment = $this->core->retrieveById($id);

        $merchantId = $payment->getMerchantId();

        $this->merchant = (new Merchant\Repository)->findOrFail($merchantId);

        $data = $this->processor()->verify($payment);

        return $data;
    }

    public function cancel($id)
    {
        $this->processor()->cancel($id);

        return ['success' => true];
    }

    public function authorizeFailed($id)
    {
        $payment = $this->core->retrieveById($id);

        $merchantId = $payment->getMerchantId();

        $this->merchant = (new Merchant\Repository)->findOrFail($merchantId);

        $data = $this->processor()->authorizeFailedPayment($payment);

        return $data;
    }

    public function retrieveRefundByIdAndPaymentId($paymentId, $rfndId)
    {
        Payment\Entity::verifyIdAndStripSign($paymentId);
        Refund\Entity::verifyIdAndStripSign($rfndId);

        $refund = (new Refund\Repository)->fetchByIdPaymentIdMerchantId(
                                    $rfndId,
                                    $paymentId,
                                    $this->merchant->getKey());

        return $refund->toArrayPublic();
    }

    public function retrieveRefundsForPayment($paymentId)
    {
        Payment\Entity::verifyIdAndStripSign($paymentId);

        $refunds = (new Refund\Repository)->findForPayment($paymentId);

        return $refunds->toArrayPublic();
    }

    /**
     * Captures a payment
     *
     * @param  string   $id
     *
     * @return Payment\Entity
     */
    public function capture($id, $input)
    {
        $payment = $this->processor()->capture($id, $input);

        return $payment->toArrayPublic();
    }

    /**
     * After card enroll, bank redirects to us
     * and we send it to gateway for further
     * processing (auth).
     * Returning from this function implies
     * 'auth' is successful.
     *
     * @param  array  $input Contains fields provided
     *                       by bank
     *
     * @return array
     */
    public function callback($id, $hash, array $input)
    {
        return $this->processor()->callback($id, $hash, $input);
    }

    public function fetchMultiple(array $input)
    {
        $payments = (new Payment\Repository)->fetch($input, $this->merchant->getKey());

        return $payments->toArrayPublic();
    }

    public function fetch($id)
    {
        $payment = $this->core->retrieveByIdAndMerchantId($id, $this->merchant->getKey());

        return $payment->toArrayPublic();
    }

    public function expireAuthorizations()
    {
        $timestamp = time() - 24 * 60 * 60;

        $count = (new Payment\Repository)->expireAuthorizedPayments($timestamp);

        return array('count' => $count);
    }

    public function notifyAuthorizedPayments()
    {
        $date = Carbon::yesterday('Asia/Kolkata');
        $timestamp = $date->timestamp;

        $payments = (new Payment\Repository)->getAuthorizedPaymentsBeforeTimestamp(
                            $timestamp);

        $count = $payments->count();

        if ($count !== 0)
        {
            $paymentIds = $payments->getIds();

            $channel = '#transactions';
            $username = 'transactions';

            $date->subDay(1);

            $message = '@harshil @shk Payment authorizations till ' . $date->format('d-m-y');

            foreach ($payments as $payment)
            {
                $message .= ' \n ' . $payment->getPublicId();
            }

            $this->app['slack']->send($message, $channel, $username);
        }

        return ['count' => $count];
    }

    public function timeoutOldPayments()
    {
        $timestamp = time() - 10 * 60;

        $count = (new Payment\Repository)->timeoutOldPayments($timestamp);

        $this->trace->info(
            TraceCode::PAYMENT_TIMED_OUT,
            ['count' => $count,
             'timestamp' => time()]);

        return ['count' => $count];
    }

    public function updateOldPayments()
    {
        ;
    }

    public function autoCaptureOldAuthorizedPayments()
    {
        $timeLowerLimit = time() - (48 * 60 * 60);
        $timeUpperLimit = time() - (24 * 60 * 60);

        $payments = (new Payment\Repository)->getAuthorizedPaymentsBetweenTimestamps(
                            $timeLowerLimit, $timeUpperLimit);

        $count = 0;

        foreach ($payments as $payment)
        {
            $this->merchant = $payment->merchant;

            $res = $this->processor()->autoCapturePayment($payment);

            if ($res)
            {
                $count++;
            }
        }

        return ['count' => $count];
    }

    public function deliverAutoCaptureEmail()
    {
        $timeLowerLimit = Carbon::yesterday('Asia/Kolkata')->timestamp;
        $timeUpperLimit = Carbon::today('Asia/Kolkata')->timestamp;

        $payments = (new Payment\Repository)->getAutoCapturedPaymentsBetweenTimestamps(
                                                        $timeLowerLimit, $timeUpperLimit);

        $count = $payments->count();
        $emailCount = 0;
        $i = 0;

        while ($i < $count)
        {
            $autoCaptured = new Base\Collection;
            $merchantId = $payments[$i]->getMerchantId();
            $str = 'Payments with below Ids have been auto-captured:\n';

            while (($i < $count) and
                   ($payments[$i]->getMerchantId() === $merchantId))
            {
                $autoCaptured->push($payments[$i]->getPublicId());
                $str .= $payments[$i]->getPublicId() . '\n';
                $i++;
            }

            $merchant = (new Merchant\Repository)->findOrFail($merchantId);
            $this->app['mailgun']->sendAutoCaptureEmail($merchant->email, $str);
            $emailCount++;
        }

        return ['payments_count' => $count, 'emails_count' => $emailCount];
    }

    public function verifyAllPayments()
    {
        $ts = time() - 60 * 60;

        $payments = (new Payment\Repository)->getUnverifiedPayments($ts);

        $timedOut = 0; $verified = 0; $failed = 0; $time = time();

        foreach ($payments as $payment)
        {
            try
            {
                $this->merchant = $payment->merchant;

                $res = $this->processor()->verify($payment);

                $verified++;
            }
            catch (Exception\PaymentVerificationException $e)
            {
                $failed++;
                // Just continue
            }
            catch (Exception\GatewayTimeoutException $e)
            {
                // Just continue
                $timedOut++;
            }
        }

        $time = time() - $time;

        $results = array(
            'verified'      => $verified,
            'failed'        => $failed,
            'timed out'     => $timedOut,
            'total time'    => $time . ' secs');

        $message = 'Payment verify result - ' . json_encode($results, JSON_PRETTY_PRINT);

        $this->app['slack']->send($message, '#transactions', 'transactions');

        return $results;
    }

    protected function processor()
    {
        return Payment\Processor\Processor::create($this->getBindings());
    }

    protected function getBindings()
    {
        $bindings = array(
            'merchant'  => $this->merchant,
            'core'      => $this->core,
            'trace'     => $this->trace,
            'mode'      => $this->mode);

        return $bindings;
    }
}
