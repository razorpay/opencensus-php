<?php

namespace Models\Payment;

use Carbon\Carbon;
use EE\Exception;

use Mail;

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

        $merchant = (new Merchant\Repository)->findOrFail($merchantId);

        $data = $this->processor($merchant)->verify($payment);

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

        $merchant = (new Merchant\Repository)->findOrFail($merchantId);

        $data = $this->processor($merchant)->authorizeFailedPayment($payment);

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

    public function refundOldAuthorizedPayments()
    {
        // Since we are taking 12 am of today, we only need to subtract 4 days from today
        // to arrive at 5 days before.
        $days = 4;
        $date = Carbon::today('Asia/Kolkata');
        $ts = $date->subDays($days)->timestamp;

        $payments = (new Payment\Repository)->getAuthorizedPaymentsBeforeTimestamp($ts);

        $authorized = $payments->count();
        $refunded = 0;

        $timedOut = 0; $failed = 0; $error = 0;
        $time = time();

        foreach ($payments as $payment)
        {
            try
            {
                assert ($payment->isAuthorized() === true);

                $merchant = $payment->merchant;

                $refund = $this->processor($merchant)
                               ->refundAuthorizedPayment(
                                    $payment->getPublicId(), []);

                $refunded++;
            }
            catch (Exception\GatewayErrorException $e)
            {
                $failed++;

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
                // @note: If payment refund fails due to any reason
                // other than expected ones, we should log it as an error
                // exception.
                //
                // If for eg, exception is BadRequestException, then it won't
                // get logged by global handler because it's not a critical
                // exception but in this context it really shouldn't have
                // occurred.

                $this->app['exception.handler']->traceException($e);

                // Just continue
                $error++;
            }
        }

        $time = time() - $time;

        $results = array(
            'authorized'    => $authorized,
            'refunded'      => $refunded,
            'error'         => $error,
            'failed'        => $failed,
            'timed out'     => $timedOut,
            'total time'    => $time . ' secs');

        $message = 'Authorized payments refunded: ' . $refunded;
        $this->slackPost($message, $results, ['channel' => '#tech_logs']);

        return $results;
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
            $date->subDay(1);

            $message = 'Payment authorizations till ' .
                        $date->format('d-m-y') . ': ' . $count;

            $this->slackPost($message, [], ['channel' => '#tech_logs']);
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
        $ts = time() - 30 * 60;

        $payments = (new Payment\Repository)->getUnverifiedPayments($ts);

        $timedOut = 0; $verified = 0; $failed = 0; $authorized = 0; $error = 0;
        $time = time();

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

                // Attempt to authorize payments whose verification failed
                $this->processor()->authorizeFailedPayment($payment);

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

                $this->app['exception.handler']->traceException($e);

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

        $this->slackPost($message, $results, ['channel' => '#tech_logs']);

        return $results;
    }

    public function sendReminderMerchantMailForAuthorizedPayments()
    {
        $result = [
            'initial'   =>  $this->sendReminderMerchantMailForAuthorizedPaymentsForSpecificDay(2, false),
            'final'     =>  $this->sendReminderMerchantMailForAuthorizedPaymentsForSpecificDay(4, true)
        ];

        $this->trace->info(TraceCode::PAYMENT_AUTHORIZE_REMINDER, $result);

        return $result;
    }

    public function sendReminderMerchantMailForAuthorizedPaymentsForSpecificDay($day, $final = false)
    {
        $result = [
            // This holds the counts
            'counts'=>[]
        ];

        // This is the start of the day 00:00, $day ago
        $start = Carbon::today('Asia/Kolkata')->subDays($day);
        $end   = Carbon::today('Asia/Kolkata')->subDays($day)->addDays(1);

        $to = $end->timestamp;
        $from = $start->timestamp;

        $result['from'] = (string) $start;
        $result['to']   = (string) $end;

        $authorizedPayments = (new Payment\Repository)
            ->getAuthorizedPaymentsBetweenTimestamps($from, $to);

        $grouped = $authorizedPayments->groupBy(Payment\Entity::MERCHANT_ID);

        // Put the counts in for debug purposes
        $result['counts']['payments'] = count($authorizedPayments);
        $result['counts']['merchants'] = count($grouped);

        foreach ($grouped as $merchantId => $payments)
        {
            // Send mail only if we have some payments
            if (count($payments) > 0)
            {
                $this->sendAuthorizedPaymentsReminderMail(
                    $merchantId, $payments, $final);

                $result['counts'][$merchantId] = count($payments);
            }
        }

        return $result;
    }

    /**
     * Sends the authorized payments reminder email
     * @param  string $merchantId [description]
     * @param  array $payments   [description]
     * @param  string $subject Subject for the email
     * @param  boolean $final Whether this is the final payment reminder
     * @return null
     */
    protected function sendAuthorizedPaymentsReminderMail($merchantId, array $payments, $final)
    {
        // date format = 6th July 2015
        $date = Carbon::today('Asia/Kolkata')->format('jS F Y');
        $subject = "Razorpay | Authorized Payments Reminder for $date";

        if ($final)
        {
            $subject = "Razorpay | Final Authorized Payments Reminder for $date";
        }

        $merchant = (new Merchant\Entity)->findOrFail($merchantId)->toArray();

        $data = compact('merchant', 'payments', 'final');

        $emails = $merchant[Merchant\Entity::TRANSACTION_REPORT_EMAIL];
        $name = $merchant['name'];

        Mail::send(
            'emails.merchant.authorized_reminder',
            $data,
            function ($message) use ($subject, $emails, $name)
            {

                foreach ($emails as $email)
                {
                    $message->to($email, $name);
                }

                $message->from('reports@razorpay.com')
                $message->cc('notifications@razorpay.com');
                $message->replyTo('support@razorpay.com', 'Razorpay Support');

                $message->subject($subject);
            });
    }

    protected function processor($merchant = null)
    {
        return Payment\Processor\Processor::create($this->getBindings($merchant));
    }

    protected function getBindings(Merchant\Entity $merchant = null)
    {
        if ($merchant === null)
        {
            $merchant = $this->merchant;
        }

        $bindings = array(
            'merchant'  => $merchant,
            'core'      => $this->core,
            'trace'     => $this->trace,
            'mode'      => $this->mode);

        return $bindings;
    }
}
