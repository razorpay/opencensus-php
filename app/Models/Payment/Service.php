<?php

namespace RZP\Models\Payment;

use Mail;
use Config;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Error;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Card;
use RZP\Models\Transaction;
use RZP\Trace\TraceCode;
use RZP\Models\Settlement\Kotak\FileHandlerTrait;

class Service extends Base\Service
{
    use FileHandlerTrait;

    protected static $fileToReadName = 'Refund_File';

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
        return $this->getNewProcessor()->process($input);
    }

    /**
     * Processes a wallet payment
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
     * @param  string   $id
     *
     * @return Payment\Entity
     */
    public function refund($id, $input)
    {
        $refund = $this->getNewProcessor()->refundCapturedPayment($id, $input);

        return $refund->toArrayPublic();
    }

    public function uploadRefundFile($input)
    {

        $entries = $this->parseExcelFile($input['file']);

        $totalEntries = count($entries);

        if($totalEntries > 1000){
              throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_REFUND_FILE_EXCEED_LIMIT);
        }

        $totalAmountToBeRefunded = 0;

        foreach ($entries as $entry)
        {
            if(isset($entry[1]))
            {
                $totalAmountToBeRefunded += $entry[1];
            }
            else
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_REFUND_FILE_VALIDATION); 
            }    
        }

        $merchant = $this->merchant;

        $balance = $this->repo->balance->getMerchantBalance($merchant);
        $balanceAmount = $balance->getBalance();

        if($totalAmountToBeRefunded > $balanceAmount)
        {
            // Warning to merchant about insufficient balance
             // throw new Exception\BadRequestException(
             //    ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_BALANCE); 

        }

        $refundFile = [
            'total_count' => $totalEntries
        ];
        
        $refundFile = (new RefundFile\Entity)->build($refundFile);

        $refundFile->merchant()->associate($merchant);

        $this->repo->saveOrFail($refundFile);

        $xlsxMimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $url = $this->saveToAws($refundFile->getId().'.xlsx', $input['file'], $xlsxMimeType, 'refund_file_upload_bucket');

        $refundFile->setUploadFileUrl($url);

        $this->repo->saveOrFail($refundFile);

        return $refundFile->toArrayPublic();
        
    }

    public function processRefundFile()
    {
        $refundFiles = $this->repo->refund_file->findUnprocessedRefunds();

        foreach ($refundFiles as $refundFile)
        {

            if($refundFile->getStatus() == 'CREATED')
            {
                $filePath = $refundFile->getUploadFileUrl();
                $fileFromAws = $this->getFileFromAws('refund_file_upload_bucket', $refundFile->getId().'.xlsx', $filePath);
            }

            elseif($refundFile->getStatus() == 'FAILURE')
            {
                $filePath = $refundFile->getDownloadFileUrl();
                $fileFromAws = $this->getFileFromAws('refund_file_download_bucket', $refundFile->getId().'.xlsx', $filePath);

            }

            $entries = $this->parseExcelFile($fileFromAws);

            $totalRefundedAmount = $refundFile->getAmount();
            $totalSuccessCount = $refundFile->getSuccessCount();
            $totalFailureCount = 0;

            $processedFile = array();

            foreach ($entries as $entry) 
            {

                $refundEntry = array();

                // Refund has already been made and the refund id is set
                if(isset($entry[3]))
                {
                    continue;
                }

                // The complete refund for the payment has already been done
                if(isset($entry[4]) && $entry[4] == 'BAD_REQUEST_PAYMENT_FULLY_REFUNDED')
                {
                    $totalFailureCount++;
                    continue;
                }

                $paymentId = $entry[0];

                $refundRequest = [
                    'amount' => (int) $entry[1]
                ];

                array_push($refundEntry, $paymentId);
                
                try 
                {     
                    $refund = $this->getNewProcessor()->refundCapturedPayment($paymentId, $refundRequest);
               
                    array_push($refundEntry, $refund->getAmount(), $refund->getId(), "SUCCESS");

                    $totalSuccessCount++;
                    $totalRefundedAmount += $refund->getAmount();

   
                } catch (\Exception $e)
                {

                    array_push($refundEntry, 0, '', $e->getMessage());  
                    
                    $totalFailureCount++;           
                }    

                array_push($processedFile, $refundEntry);
            }

            $refundFile->setAmount($totalRefundedAmount);
            $refundFile->setSuccessCount($totalSuccessCount);
            $refundFile->setFailureCount($totalFailureCount);
            
            $retryAttempts = $refundFile->getRetryAttempt() + 1;
            $refundFile->setRetryAttempt($retryAttempts);

            $shouldSendMail = false;

            if($totalFailureCount > 0)
            {
                if($retryAttempts == 3)
                {
                    $refundFile->setStatus('FAILED');
                    $shouldSendMail = true;
                }
                else
                {
                    $refundFile->setStatus('FAILURE');
                }
            }
            else
            {
                $refundFile->setStatus('PROCESSED');
                $shouldSendMail = true;
            }

            $excel = $this->createExcelObject($processedFile, $refundFile->getId());

            $fileMetadata = $excel->store('xlsx', storage_path('files/refund_file_download'), true);
            $fullpath = $fileMetadata['full'];

            $xlsxMimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            $downloadUrl = $this->saveToAws($refundFile->getId().'.xlsx', $fullpath, $xlsxMimeType, 'refund_file_download_bucket');

            $refundFile->setDownloadFileUrl($downloadUrl);

            $this->repo->saveOrFail($refundFile);

            if($shouldSendMail)
            {
                $this->sendMail($fullpath, $totalRefundedAmount, $refundFile->merchant);
            }
        }
        
        
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
        $refund = $this->getNewProcessor()->refundAuthorizedPayment($id, $input);

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

    public function redirect($id)
    {
        return $this->getNewProcessor()->redirect($id);
    }

    public function forceAuthorizeFailed($id, $input)
    {
        $payment = $this->core->retrieveById($id);

        $merchant = $this->repo->merchant->findOrFail($payment->getMerchantId());

        $data = $this->getNewProcessor($merchant)
                     ->forceAuthorizeFailedPayment($payment, $input);

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
     * USE WITH EXTREME CAUTION
     * This calls the gateway for refund and does nothing on the api side.
     *
     * @param $refundId
     * @return array
     */
    public function manualGatewayRefund($refundIds)
    {
        $refundIds = explode(',', $refundIds);

        $data = [];

        foreach ($refundIds as $refundId)
        {
            $refund = $this->repo->refund->findOrFail($refundId);
            $merchantId = $refund->getMerchantId();
            $merchant = $this->repo->merchant->findOrFail($merchantId);

            try
            {
                $response = $this->getNewProcessor($merchant)->manualGatewayRefund($refund);
            }
            catch(\Exception $ex)
            {
                $response = [
                    'refund_id'     => $refundId,
                    'payment_id'    => $refund->getPaymentId(),
                    'error_message' => $ex->getMessage(),
                ];

                $this->trace->traceException($ex);
            }

            $this->trace->info(
                TraceCode::MANUAL_GATEWAY_REFUND_RESPONSE,
                [
                    'refund_id'  => $refundId,
                    'payment_id' => $refund->getPaymentId(),
                    'response'   => $response
                ]
            );

            $data[] = $response;
        }

        $this->trace->info(
            TraceCode::MANUAL_GATEWAY_ALL_REFUNDS_RESPONSE,
            $data
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

        $payments->shuffle();

        foreach ($payments as $payment)
        {
            try
            {
                assert ($payment->isAuthorized() === true);

                $merchant = $payment->merchant;

                $refund = $this->getNewProcessor($merchant)
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

        $timestamp = time() - 9 * 60;

        $payments = $this->repo->payment->fetchOldCreatedPaymentsForTimeout($timestamp);

        foreach ($payments as $payment)
        {
            $internalErrorCode = $payment->getInternalErrorCode();

            if ($internalErrorCode !== null)
            {
                $error = new Error\Error($internalErrorCode);

                $code = $error->getPublicErrorCode();

                $desc = $error->getDescription();

                $internalCode = $error->getInternalErrorCode();

                $payment->setStatus(Payment\Status::FAILED);

                $payment->setError($code, $desc, $internalCode);
            }
            else
            {
                $payment->setStatus(Payment\Status::FAILED);

                $payment->setError(
                    Error\ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT,
                    Error\PublicErrorDescription::BAD_REQUEST_PAYMENT_TIMED_OUT,
                    null);
            }

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

        $authorizedPayments = $this->repo->payment->getAuthorizedPaymentsBetweenTimestamps($from, $to);

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

    protected function getNewProcessor(Merchant\Entity $merchant = null)
    {
        if ($merchant === null)
        {
            $merchant = $this->merchant;
        }

        $processor = new Processor\Processor($merchant);

        return $processor;
    }

    protected function sendMail($filePath, $amount, $merchant)
    {
        //TODO: Get new blade for the refund mail which will have the attached file
        // $data = [
        //     'refundFile' => $filePath,
        //     'subject' => 'subject',
        //     'amounts' => $amount,
        //     'merchant' => $merchant

        // ];

        // Mail::send('emails.refund.common', $data, function($message) use ($data)
        // {
        //     $emails = ['settlements@razorpay.com'];

        //     $message->from('settlement@razorpay.com', 'Kotak Settlement');

        //     $message->subject('Refund File Processed');

        //     $message->to($emails);

        //     $message->attach($data['refundFile']);
        // });
    }
}
