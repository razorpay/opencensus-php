<?php

namespace RZP\Models\Payment;

use Mail;
use Config;
use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Exception;
use RZP\Error;
use RZP\Mail\Merchant\AuthorizedPaymentsReminder as AuthorizedPaymentsReminderMail;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Card;
use RZP\Models\Transaction;
use RZP\Trace\TraceCode;
use RZP\Constants;
use RZP\Constants\MailTags;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\Verify\Verify;

class Service extends Base\Service
{
    protected $merchant;

    protected $core;

    protected $slack;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Payment\Core;

        $this->slack = $this->app['slack'];
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
     *
     * @param array $input
     *
     * @return array|mixed
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     */
    public function processWallet(array $input)
    {
        // Just a hack to get around mobikwik normal flow
        $input['_']['source']   = 's2s';
        $input['method']        = 'wallet';

        if (Payment\Gateway::isPowerWallet($input['wallet']) === false)
        {
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_PAYMENT_WALLET_NOT_SUPPORTED);
        }

        return $this->getNewProcessor()->process($input);
    }

    /**
     * Processes a upi payment
     *
     * @param array $input
     *
     * @return array|mixed
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     */
    public function processUpi(array $input)
    {
        $input['_']['source']   = 's2s';
        $input['method']        = 'upi';

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
     * @return array
     */
    public function refund($id, array $input)
    {
        $this->trace->info(
            TraceCode::PAYMENT_REFUND_REQUEST,
            [
                'payment_id' => $id,
                'input'      => $input
            ]);

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
        $payment = $this->repo->payment->findByPublicIdAndMerchant($id, $this->merchant);

        $refund = $this->getNewProcessor()->refundAuthorizedPayment($payment, $input);

        return $refund->toArrayPublic();
    }

    public function refundAuthorizedInBulk(array $input)
    {
        $paymentIds = $input['payment_ids'];

        $count = count($paymentIds);

        $success = $failure = 0;

        $failurePayments = $successRefunds = [];

        foreach ($paymentIds as $paymentId)
        {
            Entity::verifyIdAndSilentlyStripSign($paymentId);

            $payment = $this->repo->payment->findOrFailPublic($paymentId);

            $merchant = $payment->merchant;

            try
            {
                $refund = $this->getNewProcessor($merchant)->refundAuthorizedPayment($payment);

                $success++;

                $successRefunds[] = $refund->getId();
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex);

                $failure++;

                $failurePayments[] = $paymentId;
            }
        }

        $data = [
            'count'            => $count,
            'success'          => $success,
            'failure'          => $failure,
            'failure_payments' => $failurePayments,
            'success_refunds'  => $successRefunds,
        ];

        $this->trace->info(
            TraceCode::REFUND_AUTHORIZE_BULK,
            $data
        );

        return $data;
    }

    public function verify($id)
    {
        $payment = $this->core->retrieveById($id);

        $merchant = $this->repo->merchant->fetchMerchantFromEntity($payment);

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

        $merchant = $this->repo->merchant->fetchMerchantFromEntity($payment);

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

        $merchant = $this->repo->merchant->fetchMerchantFromEntity($payment);

        $data = $this->getNewProcessor($merchant)->authorizeFailedPayment($payment);

        return $data;
    }

    public function fixAuthorizeAt($input)
    {
        $paymentIds = $input['payment_ids'];

        $failurePayments = [];

        $successes = $failures = 0;

        $total = count($paymentIds);

        foreach ($paymentIds as $paymentId)
        {
            $payment = $this->core->retrieveById($paymentId);

            if (($payment->isFailed() === false) or
                ($payment->hasBeenCaptured() === true))
            {
                $failures++;
                $failurePayments[] = $paymentId;
                continue;
            }

            $this->trace->info(TraceCode::PAYMENT_AUTHORIZED_NULL, [
                'payment_id' => $paymentId,
                'old_authorized_at' => $payment->getAuthorizeTimestamp()
            ]);

            $payment->setAuthorizedAtNull();

            $this->repo->saveOrFail($payment);

            $successes++;
        }

        $data = [
            'success_count'     => $successes,
            'failure_count'     => $failures,
            'failure_payments'  => $failurePayments,
            'total'             => $total,
        ];

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
        $payment = $this->repo->payment->findByPublicIdAndMerchant($id, $this->merchant);

        if ($payment->hasCard() === false)
        {
            throw new Exception\BadRequestException(Error\ErrorCode::BAD_REQUEST_NOT_CARD_PAYMENT);
        }

        $card = $this->repo->card->fetchForPayment($payment);

        return $card->toArrayPublic();
    }

    public function retrieveRefundsForPayment($id)
    {
        $payment = $this->repo->payment->findByPublicIdAndMerchant($id, $this->merchant);

        $refunds = $this->repo->refund->findForPaymentAndMerchant($payment, $this->merchant);

        return $refunds->toArrayPublic();
    }

    public function fetchTransactionByPaymentId($id)
    {
        $payment = $this->repo->payment->findByPublicIdAndMerchant($id, $this->merchant);

        $transaction = $this->repo->transaction->findByEntityId($payment->getId(), $this->merchant, true);

        return $transaction->toArrayPublic();
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
        $payment = $this->repo->payment->findByPublicIdAndMerchant($id, $this->merchant);

        $payment = $this->getNewProcessor()->capture($payment, $input);

        return $payment->toArrayPublic();
    }

    /**
     * Captures payments in bulk
     *
     * @param  array  $input
     *
     * @return array
     */
    public function captureInBulk(array $input)
    {
        $this->trace->info(
            TraceCode::PAYMENT_CAPTURE_BULK_REQUEST,
            $input
        );

        (new Payment\Validator)->validateInput('bulk_capture', $input);

        $payments = $input['payment_ids'];

        $success = $failure = 0;

        $failurePayments = [];

        foreach ($payments as $paymentId)
        {
            try
            {
                $payment = $this->repo->payment->findByPublicId($paymentId);

                $merchant = $payment->merchant;

                $captureInput = [
                    Payment\Entity::AMOUNT   => $payment->getAmount(),
                    Payment\Entity::CURRENCY => $payment->getCurrency()
                ];

                $payment = $this->getNewProcessor($merchant)
                                ->capture($payment, $captureInput);

                $success++;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::INFO,
                    TraceCode::PAYMENT_CAPTURE_BULK_FAILURE,
                    [
                        'payment_id' => $paymentId
                    ]);

                $failure++;

                $failurePayments[] = $paymentId;
            }
        }

        $data = [
            'count'            => count($payments),
            'success'          => $success,
            'failure'          => $failure,
            'failure_payments' => $failurePayments,
        ];

        $this->trace->info(
            TraceCode::PAYMENT_CAPTURE_BULK_RESPONSE,
            $data
        );

        return $data;
    }

    /**
     * Transfers a payment
     * /payment/:id/transfer
     *
     * @param string $id
     * @param array  $input
     *
     * @return array
     */
    public function transfer(string $id, array $input) : array
    {
        $transfers = $this->getNewProcessor()->transfer($id, $input);

        return $transfers->toArrayPublic();
    }

    /**
     * Get Transfers for a payment_id
     *
     * @param  string $id   Payment ID
     * @return array
     */
    public function getTransfers(string $id) : array
    {
        Payment\Entity::verifyIdAndStripSign($id);

        $transfers = $this->repo
                          ->transfer
                          ->fetchBySourceTypeAndIdAndMerchant(Constants\Entity::PAYMENT, $id, $this->merchant);

        return $transfers->toArrayPublic();
    }

    /**
     * Create a payout from a payment
     *
     * @param string    $id
     * @param array     $input
     *
     * @return array
     */
    public function payout(string $id, array $input) : array
    {
        $payout = $this->getNewProcessor()->payout($id, $input);

        return $payout->toArrayPublic();
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

    public function manualGatewayCapture($paymentId)
    {
        Entity::verifyIdAndSilentlyStripSign($paymentId);

        $payment = $this->repo->payment->findOrFail($paymentId);

        $data = $this->getNewProcessor($payment->merchant)->manualGatewayCapture($payment);

        $this->trace->info(
            TraceCode::MANUAL_GATEWAY_CAPTURE_RESPONSE,
            [
                'payment_id'    => $paymentId,
                'data'          => $data
            ]);

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
        $payment = $this->repo->payment->findByPublicId($id);

        $merchant = $this->repo->merchant->fetchMerchantFromEntity($payment);

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

    public function fetch(string $id, array $input = []): array
    {
        $payment = $this->repo
                        ->payment
                        ->findByPublicIdAndMerchant($id, $this->merchant, $input);

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
        $payment = $this->repo->payment->findByPublicIdAndMerchant($id, $this->merchant);

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
     * This method is triggered by a CRON job.
     *
     * Refunds all authorized (read extra) payments for paid orders.
     *
     * There is case when there will be authorized payment against paid order which
     * is mostly LATE_AUTHORIZED payments. Though those will get auto refunded
     * within 5 days (or set auto refund delay) but this CRON helps in refunding
     * those payments immediately.
     *
     * For optimization purposes we only pick payments in last 10 days. This picked
     * '10 days' is sufficient filter logically.
     */
    public function refundAuthorizedPaymentsOfPaidOrders()
    {
        $payments = $this->repo->payment->getAuthorizedPaymentsOfPaidOrderForRefund();

        $time = time();

        $failedPaymentIds = []; // Refund failed for these payments.

        foreach ($payments as $payment)
        {
            $paymentId = $payment->getId();
            $orderId   = $payment->getApiOrderId();

            $merchant  = $payment->merchant;

            $tracePayload = [
                'payment_id' => $paymentId,
                'order_id'   => $orderId,
            ];

            try
            {
                $this->getNewProcessor($merchant)->refundAuthorizedPayment($payment);

                $this->trace->info(TraceCode::ORDER_REFUNDED, $tracePayload);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::PAYMENT_AUTO_REFUND_FAILURE,
                    $tracePayload);

                $failedPaymentIds[] = $paymentId;
            }
        }

        $time = time() - $time;

        $summary = [
            'count'      => $payments->count(),
            'total_time' => $time . ' secs',
            'failed_ids' => $failedPaymentIds,
        ];

        $this->trace->info(TraceCode::ORDERS_MULTIPLE_AUTHORIZED_REFUNDS, $summary);

        $message = 'Authorized payments for paid orders refunded';
        $channel = Config::get('slack.channels.tech_logs');

        $this->slack->queue($message, $summary, ['channel' => $channel]);

        return $summary;
    }

    public function refundOldAuthorizedPayments()
    {
        //
        // Since we are taking 12 am of today,
        // we only need to subtract 4 days from today
        // to arrive at 5 days before.
        //
        $seconds = Merchant\Entity::AUTO_REFUND_DELAY_DEFAULT;

        $date = Carbon::today(Timezone::IST);
        $ts = $date->subSeconds($seconds)->getTimestamp();

        $payments = $this->repo->payment->getAuthorizedPaymentsBeforeTimestamp($ts, false);

        //
        // We fetch all the authorized payments eligible for refund.
        // Payments are identified on the basis of merchant auto_refund_delay
        // Maximum delay can be 10 days
        //
        $payments2 = $this->repo->payment->getAuthorizedPaymentsWithAutoRefundDelay();

        $payments = $payments->merge($payments2);

        $authorized = $payments->count();
        $refunded = 0;

        $timedOut = 0;
        $failed = 0;
        $error = 0;

        $time = time();

        $payments = $payments->shuffle();

        $this->trace->info(
            TraceCode::PAYMENT_AUTO_REFUND_CRON,
            [
                'count' => $authorized,
                'start_time' => $time
            ]);

        foreach ($payments as $payment)
        {
            try
            {
                assert ($payment->isAuthorized() === true);

                $merchant = $payment->merchant;

                $refund = $this->getNewProcessor($merchant)
                               ->refundAuthorizedPayment($payment);

                $this->trace->info(
                    TraceCode::PAYMENT_AUTO_REFUND,
                    [
                        'payment_id'        => $payment->getId(),
                        'refund_id'         => $refund->getId(),
                        'auto_refund_delay' => $merchant->getAutoRefundDelay()
                    ]);

                $refunded++;
            }
            catch (Exception\GatewayTimeoutException $e)
            {
                $this->trace->info(
                    TraceCode::GATEWAY_REQUEST_TIMEOUT,
                    ['payment_id' => $payment->getId()]);

                // Just continue
                $timedOut++;
            }
            catch (Exception\GatewayErrorException $e)
            {
                $failed++;

                $this->trace->traceException($e, Trace::INFO, TraceCode::REFUND_EXCEPTION);

                // Now Just continue
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
        $date = Carbon::yesterday(Timezone::IST);
        $timestamp = $date->getTimestamp();

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
        $error = 0;

        $startTime = microtime(true);

        // All Payments in created state will be marked as failed after 9 minutes
        $now = time();
        $timestamp = $now - Payment\Entity::PAYMENT_TIMEOUT_DEFAULT_OLD;

        $payments = $this->repo->payment->fetchOldCreatedPaymentsForTimeout($timestamp);

        foreach ($payments as $payment)
        {
            if ($payment->shouldTimeout($now) === true)
            {
                $this->repo->payment->lockForUpdateAndReload($payment);

                try
                {
                    $this->getNewProcessor($payment->merchant)
                         ->setPayment($payment)
                         ->timeoutPayment();

                    $count++;
                }
                catch (\Exception $e)
                {
                    $this->trace->traceException($e);

                    $error++;
                }
            }
        }

        $this->trace->info(
            TraceCode::PAYMENT_TIMED_OUT,
            [
                'count'      => $count,
                'error'      => $error,
                'timestamp'  => time(),
                'time_taken' => microtime(true) - $startTime
            ]);

        return ['count' => $count];
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

            try
            {
                $this->getNewProcessor()->autoCapturePayment($payment);
            }
            catch (Exception\RecoverableException $e)
            {
                continue;
            }

            $count++;
        }

        return ['count' => $count];
    }

    public function deliverAutoCaptureEmail()
    {
        $timeLowerLimit = Carbon::yesterday(Timezone::IST)->getTimestamp();
        $timeUpperLimit = Carbon::today(Timezone::IST)->getTimestamp();

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

    public function verifyMultiplePayments(string $filter, array $input)
    {
        $bucket = [];

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
            'initial'   => $this->sendReminderMerchantMailForAuthorizedPaymentsForSpecificDay(2, false),
            'final'     => $this->sendReminderMerchantMailForAuthorizedPaymentsForSpecificDay(4, true)
        ];

        $this->trace->info(TraceCode::PAYMENT_AUTHORIZE_REMINDER, $result);

        return $result;
    }

    public function sendReminderMerchantMailForAuthorizedPaymentsForSpecificDay($day, $final = false)
    {
        $result = [
            // This holds the counts
            'counts' => []
        ];

        // This is the start of the day 00:00, $day ago
        $start = Carbon::today(Timezone::IST)->subDays($day);
        $end   = Carbon::today(Timezone::IST)->subDays($day)->addDays(1);

        $to = $end->getTimestamp();
        $from = $start->getTimestamp();

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
                            'message'     => $ex->getMessage(),

                        ]);

                    $result['counts']['failures'] += 1;
                }
            }
        }

        return $result;
    }

    /**
     * Fetch and update on_hold flag for all payment
     * and source transfer with on_hold_until less than today's
     *
     * @param array $input
     * @return array
     */
    public function updateOnHold(array $input): array
    {
        $timestamp = Carbon::today(Timezone::IST)->getTimestamp();

        $paymentsToUpdate = $this->repo->payment->getPaymentsOnHoldBeforeTimestamp($timestamp);

        $this->trace->debug(
            TraceCode::PAYMENT_UPDATE_HOLD_CRON,
            [
                'step'          => 'fetch_payments',
                'ids_fetched'   => $paymentsToUpdate->getIds(),
                'timestamp'     => Carbon::createFromTimestamp($timestamp, Timezone::IST)->format('d-m-Y H:i:s')
            ]
        );

        $cronSummary = [
            'total_count' => $paymentsToUpdate->count(),
            'failed_ids'  => []
        ];

        foreach ($paymentsToUpdate as $payment)
        {
            try
            {
                $this->repo->transaction(
                    function() use ($payment)
                    {
                        $this->setHoldFalse($payment);
                    });
            }
            catch (\Exception $e)
            {
                $cronSummary['failed_ids'][] = $payment->getId();

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::PAYMENT_UPDATE_HOLD_CRON,
                    [
                        'step'  => 'update_failed',
                        'id'    => $payment->getId()
                    ]
                );
            }
        }

        $this->trace->debug(TraceCode::PAYMENT_UPDATE_HOLD_CRON, ['step' => 'summary', 'summary' => $cronSummary]);

        $slackMessage = 'CRON: Payment set on_hold=false for elapsed on_hold_until';

        $slackChannel = Config::get('slack.channels.tech_logs');

        $this->slack->queue($slackMessage, $cronSummary, ['channel' => $slackChannel]);

        return [
            'success'   => true,
            'summary'   => $cronSummary
        ];
    }

    protected function setHoldFalse(Payment\Entity $payment)
    {
        $this->repo->payment->lockForUpdateAndReload($payment);

        $payment->setOnHold(false);

        $payment->setOnHoldUntil(null);

        $this->repo->saveOrFail($payment);

        $txn = $this->repo->transaction->lockForUpdate($payment->getTransactionId());

        $txn->setOnHold(false);

        $this->repo->saveOrFail($txn);

        //
        // If the payment has a transfer, update the
        // on_hold flag for the transfer as well
        //
        if ($payment->hasTransfer() === true)
        {
            $transfer = $this->repo->transfer->lockForUpdate($payment->getTransferId());

            $transfer->setOnHold(false);

            $transfer->setOnHoldUntil(null);

            $this->repo->saveOrFail($transfer);
        }
        // Temp: Payments can't have hold enabled right now without a linked transfer
        // Fail if no associated transfer. @todo - Remove this when payment hold is added\
        else
        {
            throw new Exception\LogicException(
                'Hold update attempted for payment with no transfer',
                null,
                [
                    'transaction_id'    => $txn->getId(),
                    'payment_id'        => $payment->getId(),
                ]);
        }
    }

    /**
     * Sends the authorized payments reminder email
     * @param  string   $merchantId
     * @param  array    $payments
     * @param  boolean  $final Whether this is the final payment reminder
     */
    protected function sendAuthorizedPaymentsReminderMail($merchantId, $payments, $final)
    {
        $merchant = (new Merchant\Entity)->findOrFail($merchantId);

        if ($merchant->isLinkedAccount() === true)
        {
            return;
        }

        $merchant = $merchant->toArray();

        $data = compact('merchant', 'payments', 'final');

        $authorizedPaymentsReminderMail = new AuthorizedPaymentsReminderMail($data);

        Mail::send($authorizedPaymentsReminderMail);
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
