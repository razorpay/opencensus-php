<?php

namespace RZP\Models\Payment;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Error;

use Mail;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Card;
use RZP\Models\Transaction;

use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    protected $merchant;

    protected $core;

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
     * Processes a wallet payment
     */
    public function processWallet(array $input)
    {
        // Just a hack to get around mobikwik normal flow
        $input['_']['source']   = 's2s';
        $input['method']        = 'wallet';

        return $this->processor()->process($input);
    }

    public function processAndReturnFees(array & $input)
    {
        return $this->processor()->processAndReturnFees($input);
    }

    /**
     * Resend OTP
     *
     * @param string  $id
     * @param array   $input
     *
     * @return array
     */
    public function otpResend($id, $input)
    {
        return $this->processor()->otpResend($id, $input);
    }

    /*
     * Topup a wallet
     *
     * @param string $id
     * @param array  $input
     *
     * @return array
     */
    public function topup($id, $input)
    {
        $data = $this->processor()->topup($id, $input);

        return $data;
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

    public function cancel($id, $input)
    {
        $status = $this->processor()->cancel($id, $input);

        return ['status' => $status];
    }

    public function redirect($id)
    {
        return $this->processor()->redirect($id);
    }

    public function forceAuthorizeFailed($id, $input)
    {
        $payment = $this->core->retrieveById($id);

        $merchant = (new Merchant\Repository)->findOrFail($payment->getMerchantId());

        $data = $this->processor($merchant)
                     ->forceAuthorizeFailedPayment($payment, $input);

        return $data;
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

    public function getCardForPayment($id)
    {
        Payment\Entity::verifyIdAndStripSign($id);

        $payment = $this->repo->payment->findByIdAndMerchantId($id, $this->merchant->getId());

        $card = $payment->card;

        return $card->toArrayPublic();
    }

    public function retrieveRefundsForPayment($id)
    {
        Payment\Entity::verifyIdAndStripSign($id);

        $payment = $this->repo->payment->findByIdAndMerchantId($id, $this->merchant->getId());

        $refunds = (new Refund\Repository)->findForPayment($payment, $this->merchant);

        return $refunds->toArrayPublic();
    }

    /**
     * Captures a payment
     *
     * @param string $id
     * @param array  $input
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

    public function s2sCallback($id, $input)
    {
        Payment\Entity::verifyIdAndStripSign($id);

        $payment = $this->repo->payment->findOrFailPublic($id);

        $merchant = $payment->merchant;

        return $this->processor($merchant)->s2sCallback($payment, $input);
    }

    public function fetchMultiple(array $input)
    {
        $merchantId = $this->merchant->getId();

        $payments = (new Payment\Repository)->fetch($input, $merchantId);

        return $payments->toArrayPublic();
    }

    public function fetch($id)
    {
        $payment = $this->core->retrieveByIdAndMerchantId($id, $this->merchant->getId());

        return $payment->toArrayPublic();
    }

    public function addPaymentMetadata($id, $input)
    {
        $payment = $this->core->retrieveByIdAndMerchantId($id, $this->merchant->getKey());

        $this->trace->info(TraceCode::PAYMENT_METADATA, $input);

        if (isset($input['otp_read']) === true)
        {
            $otpRead = $input['otp_read'];

            if ($payment->isMethodCardOrEmi() === false)
            {
                return;
            }

            $card = $payment->card;

            $cardIin = $card->iin;
            $repo = new Card\IIN\Repository;
            $iin = $repo->find($cardIin);

            if ($iin === null)
            {
                return [];
            }

            if ($otpRead === '1')
            {
                $iin->setOtpRead(true);
                $repo->saveOrFail($iin);
            }
            else if (($otpRead === '0') and
                     ($iin->getOtpRead() === true))
            {
                $this->trace->error(
                    TraceCode::PAYMENT_OTP_READ_FAILURE,
                    ['iin' => $cardIin, 'otp_read' => $otpRead]);
            }
        }

        return [];
    }

    public function refundOldAuthorizedPayments()
    {
        // Since we are taking 12 am of today, we only need to subtract 4 days from today
        // to arrive at 5 days before.

        $days = 5;
        $date = Carbon::today('Asia/Kolkata');
        $ts = $date->subDays($days)->timestamp;

        $payments = (new Payment\Repository)->getAuthorizedPaymentsBeforeTimestamp($ts);

        $authorized = $payments->count();
        $refunded = 0;

        $timedOut = 0; $failed = 0; $error = 0;
        $time = time();

        $payments->shuffle();

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
                    TraceCode::GATEWAY_REQUEST_TIMEOUT,
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

                // @todo: Remove this in future.
                // $this->app['exception.handler']->traceException($e);

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
        $timestamp = time() - 9 * 60;

        // Timeout all the pending payments, changing the error to timeout
        $count = (new Payment\Repository)->timeoutOldPayments($timestamp);

        // Timeout old payment while retaining the error, if set
        $payments = (new Payment\Repository)->fetchCreatedPaymentsWithInternalError($timestamp);

        foreach ($payments as $payment)
        {
            $error = new Error\Error($payment->getInternalErrorCode());

            $code = $error->getPublicErrorCode();

            $desc = $error->getDescription();

            $internalCode = $error->getInternalErrorCode();

            $payment->setStatus(Payment\Status::FAILED);

            $payment->setError($code, $desc, $internalCode);

            $saved = $payment->save();

            if ($saved === true)
            {
                ++$count;
            }
        }

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

    public function verifyMultiplePayments($filter)
    {
        $verify = new Verify($this->mode, $this->trace);

        return $verify->verifyPaymentsWithFilter($filter);
    }

    public function verifyPayment($payment)
    {
        $verify = new Verify($this->mode, $this->trace);

        return $verify->verifyPayment($payment);
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

        $authorizedPayments = (new Payment\Repository)->getAuthorizedPaymentsBetweenTimestamps($from, $to);

        $grouped = $authorizedPayments->keyBy(Payment\Entity::MERCHANT_ID);

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
    protected function sendAuthorizedPaymentsReminderMail($merchantId, $payments, $final)
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

                $message->from('reports@razorpay.com');
                $message->cc('notifications@razorpay.com');
                $message->replyTo('support@razorpay.com', 'Razorpay Support');

                $message->subject($subject);
            });
    }

    protected function processor(Merchant\Entity $merchant = null)
    {
        if ($merchant === null)
        {
            $merchant = $this->merchant;
        }

        $processor = new Processor\Processor($merchant);

        return $processor;
    }

    public function computeServiceTax()
    {
        $repo = new Payment\Repository;
        $payments = $repo->getNonTaxComputedPayments();

        $totalRecords = 0;
        $updatedRecords = 0;
        $totalServiceTax = 0;

        $repo->transaction(function() use ($payments, &$totalRecords, &$updatedRecords, &$totalServiceTax)
        {
            $totalRecords = $payments->count();
            foreach ($payments as $payment)
            {
                $txn = $payment->transaction;
                $this->merchant = $payment->merchant;
                (new Transaction\Core)->fillServiceTax($txn, $payment);

                $payment->setServiceTax($txn->getServiceTax());
                $payment->setFee($txn->getFee());

                $txn->saveOrFail();
                $payment->saveOrFail();

                $updatedRecords++;
                $totalServiceTax += $txn -> getServiceTax();
            }
        });

        $results = array(
            'total'                 => $totalRecords,
            'updated'               => $updatedRecords,
            'total service tax'     => $totalServiceTax);

        return $results;
    }
}
