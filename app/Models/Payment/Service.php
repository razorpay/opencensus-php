<?php

namespace RZP\Models\Payment;

use Mail;
use Config;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Error;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Card;
use RZP\Models\Transaction;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Verify\Verify;

class Service extends Base\Service
{
    protected $merchant;

    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Payment\Core;
    }

    /**
     * Processes a payment.
     * @param array $input
     * @return array|mixed
     * @throws Exception\BadRequestValidationFailureException
     */
    public function process(array $input)
    {
        return $this->getNewProcessor()->process($input);
    }

    /**
     * Processes a wallet payment
     * @param array $input
     * @return array|mixed
     * @throws Exception\BadRequestValidationFailureException
     */
    public function processWallet(array $input)
    {
        // Just a hack to get around mobikwik normal flow
        $input['_']['source']   = 's2s';
        $input['method']        = 'wallet';

        return $this->getNewProcessor()->process($input);
    }

    public function processAndReturnFees(array & $input)
    {
        return $this->getNewProcessor()->processAndReturnFees($input);
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
        return $this->getNewProcessor()->otpResend($id, $input);
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
        $data = $this->getNewProcessor()->topup($id, $input);

        return $data;
    }

    /**
     * Refunds a payment
     *
     * @param  string $id
     * @param  array  $input
     *
     * @return Payment\Entity
     */
    public function refund($id, array $input)
    {
        $refund = $this->getNewProcessor()->refundPaymentViaMerchant($id, $input);

        return $refund->toArrayPublic();
    }

    /**
     * Refunds a payment
     *
     * @param string  $id
     * @param array   $input
     *
     * @return Payment\Entity
     */
    public function refundAuthorized($id, array $input)
    {
        Payment\Entity::verifyIdAndStripSign($id);

        $payment = $this->repo->payment->findByIdAndMerchantId($id, $this->merchant->getId());

        $refund = $this->getNewProcessor()->refundAuthorizedPayment($payment, $input);

        return $refund->toArrayPublic();
    }

    public function verify($id)
    {
        $payment = $this->core->retrieveById($id);

        $merchantId = $payment->getMerchantId();

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $data = $this->getNewProcessor($merchant)->verify($payment);

        return $data;
    }

    public function cancel($id, $input)
    {
        $data = $this->getNewProcessor()->cancel($id, $input);

        return $data;
    }

    public function redirectCallback($id)
    {
        return $this->getNewProcessor()->redirectCallback($id);
    }

    public function forceAuthorizeFailed($id, $input)
    {
        $payment = $this->core->retrieveById($id);

        $merchant = $this->repo->merchant->findOrFail($payment->getMerchantId());

        $data = $this->getNewProcessor($merchant)
                     ->forceAuthorizeFailedPayment($payment, $input);

        return $data;
    }

    public function authorizeLockTimeOutPayments($paymentIds)
    {
        $paymentIds = explode(',', $paymentIds);

        $failurePayments = [];

        $successes = $failures = 0;

        $total = count($paymentIds);

        foreach ($paymentIds as $paymentId)
        {
            $payment = $this->repo->payment->findOrFail($paymentId);

            $merchant = $payment->merchant;

            try
            {
                $this->getNewProcessor($merchant)->forceAuthorizeFailedPayment($payment);
                $successes++;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex);
                $failures++;
                $failurePayments[] = $paymentId;
            }
        }

        $data = [
            'success_count'     => $successes,
            'failure_count'     => $failures,
            'failure_payments'  => $failurePayments,
            'total'             => $total,
        ];

        $this->trace->info(
            TraceCode::FORCE_AUTHORIZE_TIMEOUT_PAYMENTS_RESPONSE,
            $data);

        return $data;
    }

    public function authorizeFailed($id)
    {
        $payment = $this->core->retrieveById($id);

        $merchantId = $payment->getMerchantId();

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $data = $this->getNewProcessor($merchant)->authorizeFailedPayment($payment);

        return $data;
    }

    public function retrieveRefundByIdAndPaymentId($paymentId, $rfndId)
    {
        Payment\Entity::verifyIdAndStripSign($paymentId);
        Refund\Entity::verifyIdAndStripSign($rfndId);

        $refund = $this->repo->refund->fetchByIdPaymentIdMerchantId(
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

        $refunds = $this->repo->refund->findForPayment($payment, $this->merchant);

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
        $payment = $this->getNewProcessor()->capture($id, $input);

        return $payment->toArrayPublic();
    }


    /**
     * If a payment has been captured on gateway but not on the api side,
     * we create a transaction for the payment.
     *
     * @param $paymentId
     * @return array
     */
    public function verifyCapture($paymentId)
    {
        $payment = $this->repo->payment->findOrFail($paymentId);

        $merchantId = $payment->getMerchantId();

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $data = $this->getNewProcessor($merchant)->verifyCapture($payment);

        $this->trace->info(
            TraceCode::VERIFY_CAPTURE_RESPONSE,
            [
                'payment_id'    => $paymentId,
                'data'          => $data
            ]
        );

        return $data;
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
        return $this->getNewProcessor()->callback($id, $hash, $input);
    }

    public function s2sCallback($id, $input)
    {
        Payment\Entity::verifyIdAndStripSign($id);

        $payment = $this->repo->payment->findOrFailPublic($id);

        $merchant = $payment->merchant;

        // TODO: Hack to prevent S2S callback processing for TPV Merchants.
        // All TPV Merchant transactions will be made through BILLDESK.
        // Issue is currently on BILLDESK end. Remove once the fix has been
        // made from the BILLDESK side.
        if ($merchant->isTPVRequired())
        {
            return ['success' => true];
        }

        return $this->getNewProcessor($merchant)->s2sCallback($payment, $input);
    }

    public function fetchMultiple(array $input)
    {
        $merchantId = $this->merchant->getId();

        $payments = $this->repo->payment->fetch($input, $merchantId);

        return $payments->toArrayPublic();
    }

    public function fetch($id)
    {
        $payment = $this->core->retrieveByIdAndMerchantId($id, $this->merchant->getId());

        return $payment->toArrayPublic();
    }

    /**
     * We only return the payment status in case of an async
     * payment + status being either of created or authorized
     *
     * Note: This will only work within 15 minutes of the payment creation
     *
     * @return array
     */
    public function fetchStatus($id)
    {
        $data = $this->getNewProcessor()->getAsyncResponse($id);

        return $data;
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
                return [];
            }

            $card = $payment->card;

            $cardIin = $card->iin;
            $iin = $this->repo->iin->find($cardIin);

            if ($iin === null)
            {
                return [];
            }

            if ($otpRead === '1')
            {
                $iin->setOtpRead(true);
                $this->repo->saveOrFail($iin);
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

    /**
     * If there are multiple authorized payments for a single order,
     * and if at least one of them has a captured payment,
     * we refund all the other payments immediately.
     */
    public function refundMultipleAuthorizedPaymentsForOrders()
    {
        // We get all the orders which have multiple authorized or captured payments.
        $orders = $this->repo->order->getOrdersWithMultipleAuthorizedOrCapturedPayments();

        $data = [];
        $time = time();

        $totalOrdersCount = $orders->count();

        foreach ($orders as $order)
        {
            $data[] = $this->refundMultipleAuthorizedPaymentsForOrder($order);
        }

        $time = time() - $time;

        $results = [
            'total_orders'          => $totalOrdersCount,
            'order_level_details'   => $data,
            'total time'            => $time . ' secs'
        ];

        $this->trace->info(
            TraceCode::ORDERS_MULTIPLE_AUTHORIZED_REFUNDS,
            $results
        );

        $message = 'Multiple authorized payments for orders with a captured payment refunded';

        $this->slack->queue($message, $results, ['channel' => Config::get('slack.channels.tech_logs')]);

        return $results;
    }

    protected function refundMultipleAuthorizedPaymentsForOrder(Order\Entity $order)
    {
        $payments = $order->payments;

        // Check if there are any captured payments.
        $capturedPayments = $payments->filter(function ($item)
        {
            return $item->hasBeenCaptured();
        })->values();

        $refundDetails = [];

        if ($capturedPayments->count() === 0)
        {
            //do nothing
        }
        else if ($capturedPayments->count() === 1)
        {
            $refundDetails = $this->refundAuthorizedPaymentsForOrderWithCapturedPayment($payments);
        }
        else
        {
            $this->trace->error(
                TraceCode::ORDER_MULTIPLE_CAPTURED_PAYMENTS,
                [
                    'order_id'      => $order->getId(),
                    'payment_ids'   => $capturedPayments->getIds()
                ]);
        }

        return [
            'order_id'                  => $order->getId(),
            'total_payments'            => $payments->count(),
            'total_captured_payments'   => $capturedPayments->count(),
            'refund_details'            => $refundDetails,
        ];
    }

    protected function refundAuthorizedPaymentsForOrderWithCapturedPayment(Base\PublicCollection $payments)
    {
        $refundedCount = $failureCount = 0;

        // Get all payments which are in authorized state currently
        $authorizedPayments = $payments->filter(function ($item)
        {
            return $item->isAuthorized();
        })->values();

        foreach ($authorizedPayments as $authorizedPayment)
        {
            $merchant = $authorizedPayment->merchant;

            try
            {
                $this->getNewProcessor($merchant)->refundAuthorizedPayment($authorizedPayment);

                $this->trace->info(
                    TraceCode::ORDER_REFUNDED,
                    [
                        'payment_id' => $authorizedPayment->getId()
                    ]);

                $refundedCount++;
            }
            catch (\Exception $ex)
            {
                $this->trace->error(
                    TraceCode::PAYMENT_AUTO_REFUND_FAILURE,
                    [
                        'payment_id'    => $authorizedPayment->getId(),
                    ]);

                $this->trace->traceException($ex);

                $failureCount++;
            }
        }

        return [
            'total_authorized_payments' => $authorizedPayments->count(),
            'total_refunded_payments'   => $refundedCount,
            'total_failed_refunds'      => $failureCount,
        ];
    }

    public function refundOldAuthorizedPayments()
    {
        // Since we are taking 12 am of today, we only need to subtract 4 days from today
        // to arrive at 5 days before.

        $days = Processor\Processor::AUTO_REFUND_TIME_PERIOD;
        $date = Carbon::today('Asia/Kolkata');
        $ts = $date->subDays($days)->timestamp;

        $payments = $this->repo->payment->getAuthorizedPaymentsBeforeTimestamp($ts);

        $authorized = $payments->count();
        $refunded = 0;

        $timedOut = 0; $failed = 0; $error = 0;
        $time = time();

        $payments = $payments->shuffle();

        foreach ($payments as $payment)
        {
            try
            {
                assert ($payment->isAuthorized() === true);

                $merchant = $payment->merchant;

                $refund = $this->getNewProcessor($merchant)
                               ->refundAuthorizedPayment($payment);

                $refunded++;
            }
            catch (Exception\GatewayErrorException $e)
            {
                $failed++;

                $this->trace->traceException($e, Trace::INFO, TraceCode::REFUND_EXCEPTION);

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

                $this->trace->traceException($e, Trace::INFO, TraceCode::REFUND_EXCEPTION);

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

        $this->slack->queue($message, $results, ['channel' => Config::get('slack.channels.tech_logs')]);

        return $results;
    }

    public function notifyAuthorizedPayments()
    {
        $date = Carbon::yesterday('Asia/Kolkata');
        $timestamp = $date->timestamp;

        $payments = $this->repo->payment->getAuthorizedPaymentsBeforeTimestamp(
                            $timestamp);

        $count = $payments->count();

        if ($count !== 0)
        {
            $date->subDay(1);

            $message = 'Payment authorizations till ' .
                        $date->format('d-m-y') . ': ' . $count;

            $this->slack->queue($message, [], ['channel' => Config::get('slack.channels.tech_logs')]);
        }

        return ['count' => $count];
    }

    public function timeoutOldPayments()
    {
        $count = 0;

        // All Payments in created state will be marked as failed after 9 minutes
        $timestamp = time() - 9 * 60;

        $payments = $this->repo->payment->fetchOldCreatedPaymentsForTimeout($timestamp);

        foreach ($payments as $payment)
        {
            $this->setErrorCodeAndDescription($payment);

            $payment->setStatus(Payment\Status::FAILED);

            $payment->setVerifyBucket(0);

            $saved = $this->repo->save($payment);

            if ($saved === true)
            {
                ++$count;

                $this->app['events']->fire('api.payment.failed', array($payment));
            }
        }

        $this->trace->info(
            TraceCode::PAYMENT_TIMED_OUT,
            ['count' => $count,
             'timestamp' => time()]);

        return ['count' => $count];
    }

    protected function setErrorCodeAndDescription($payment)
    {
        $internalErrorCode = $payment->getInternalErrorCode();

        $code = Error\ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT;

        $desc = Error\PublicErrorDescription::BAD_REQUEST_PAYMENT_TIMED_OUT;

        $internalCode = null;

        if ($internalErrorCode !== null)
        {
            $error = new Error\Error($internalErrorCode);

            $code = $error->getPublicErrorCode();

            $desc = $error->getDescription();

            $internalCode = $error->getInternalErrorCode();
        }

        $payment->setError($code, $desc, $internalCode);
    }

    public function autoCaptureOldAuthorizedPayments()
    {
        $timeLowerLimit = time() - (48 * 60 * 60);
        $timeUpperLimit = time() - (24 * 60 * 60);

        $payments = $this->repo->payment->getAuthorizedPaymentsBetweenTimestamps(
                            $timeLowerLimit, $timeUpperLimit);

        $count = 0;

        foreach ($payments as $payment)
        {
            $this->merchant = $payment->merchant;

            $res = $this->getNewProcessor()->autoCapturePayment($payment);

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

        $payments = $this->repo->payment->getAutoCapturedPaymentsBetweenTimestamps(
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

            $merchant = $this->repo->merchant->findOrFail($merchantId);
            $this->app['mailgun']->sendAutoCaptureEmail($merchant->email, $str);
            $emailCount++;
        }

        return ['payments_count' => $count, 'emails_count' => $emailCount];
    }

    public function verifyMultiplePayments($filter, $input)
    {
        $bucket = null;

        if (isset($input['bucket']) === true)
        {
            $bucket = $input['bucket'];
        }

        return (new Verify)->verifyPaymentsWithFilter($filter, $bucket);
    }

    public function verifyPayment($payment)
    {
        return (new Verify)->verifyPayment($payment);
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

        $authorizedPayments = $this->repo->payment->getAuthorizedPaymentsBetweenTimestamps($from, $to);

        $grouped = $authorizedPayments->groupBy(Payment\Entity::MERCHANT_ID);

        // Put the counts in for debug purposes
        $result['counts'] = [
            'payments'  => count($authorizedPayments),
            'merchants' => count($grouped),
            'failures'  => 0,
        ];

        foreach ($grouped as $merchantId => $payments)
        {
            // Send mail only if we have some payments
            if (count($payments) > 0)
            {
                try
                {
                    $this->sendAuthorizedPaymentsReminderMail(
                        $merchantId, $payments, $final);

                    $result['counts'][$merchantId] = count($payments);
                }
                catch (\Exception $ex)
                {
                    $this->trace->warning(TraceCode::PAYMENT_AUTHORIZE_REMINDER_FAILURE,
                        [
                            'merchant_id' => $merchantId,
                            'payments'    => count($payments),

                        ]);

                    $result['counts']['failures'] += 1;
                }
            }
        }

        return $result;
    }

    /**
     * Sends the authorized payments reminder email
     * @param  string   $merchantId
     * @param  array    $payments
     * @param  boolean  $final Whether this is the final payment reminder
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

    protected function getNewProcessor(Merchant\Entity $merchant = null)
    {
        if ($merchant === null)
        {
            $merchant = $this->merchant;
        }

        $processor = new Processor\Processor($merchant);

        return $processor;
    }
}
