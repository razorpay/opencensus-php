<?php

namespace Models\Payment\Processor;

use Models\Merchant;
use Models\Payment;
use Models\Transaction;
use Trace\TraceCode;
use Mail;

trait Capture
{
    /**
     * Captures a previous auth payment
     *
     * @param  string  $id      Id of payment to be captured
     * @param  integer $amount  Amount to capture
     *
     * @return Payment\Entity   Payment\Entity object
     */
    public function capture($id, array $input = array())
    {
        $payment = $this->retrieve($id);

        (new Payment\Validator)->captureValidate($payment, $input);

        return $this->capturePayment($payment, $input['amount']);
    }

    /**
     * Captures a payment and sets auto-capture flag true
     *
     * @param  Payment\Entity $payment The payment entity to capture
     * @return boolean
     */
    public function autoCapturePayment($payment)
    {
        $this->payment = $payment;

        $amount = $payment->getAmount();

        // set auto-capture 1
        $payment->setAutoCaptureTrue();

        try
        {
            $payment = $this->capturePayment($payment, $amount);
        }
        catch (Exception\RecoverableException $e)
        {
            $this->trace->error(
                TraceCode::TRACE_MISC_CODE,
                ['auto_capture' => 1,
                'payment_id' => $payment->getPublicId()]);

            return false;
        }

        return true;
    }

    /**
     * Captures the payment.
     *
     * @param  Payment\Entity   $payment
     * @param  integer          $amount
     * @return Payment\Entity
     */
    protected function capturePayment($payment, $amount)
    {
        $data = array(
            'payment' => $payment->toArray(),
            'amount' => $amount);

        if ($payment->getMethod() === Payment\Method::CARD)
        {
            $data['card'] = $payment->card->toArray();
        }

        $payment->setCaptureAmount($amount);

        $this->captureOnGateway($data);

        return $payment;
    }

    protected function captureOnGateway($data)
    {
        try
        {
            $this->callGatewayFunction(Payment\Action::CAPTURE, $data);

            $this->recordCapture();
        }
        catch (BaseException $e)
        {
            $this->updatePaymentFailed(
                    $e->getError(),
                    TraceCode::PAYMENT_CAPTURE_FAILURE);

            throw $e;
        }
    }

    protected function recordCapture()
    {
        $this->repo->transaction(function()
        {
            $this->repo->lockForUpdate($this->payment->getKey());

            $this->updatePaymentCaptured();

            $txn = (new Transaction\Core)->createFromPayment($this->payment);

            $txn->save();
            $this->payment->save();
        });

        //
        // Analytics
        //
        $this->notifyDashboard('payment', $this->payment);

        //
        // Mail Customer
        //
        $this->emailCustomer($this->payment);
    }

    protected function updatePaymentCaptured()
    {
        $this->payment->setStatus(Payment\Status::CAPTURED);

        $this->payment->setCaptureTimestamp();

        $this->trace(TraceCode::PAYMENT_CAPTURE_SUCCESS);
    }

    protected function emailCustomer($payment)
    {
        $app = \App::getFacadeRoot();

        $templateData = [
            'customer'  =>  [
                'email' =>  $payment->getEmail(),
                'phone' =>  $payment->getContact()
            ],
            'merchant'  =>  [
                'billing_label' =>  $payment->merchant->getBillingLabel(),
                'website'       =>  $payment->merchant->getWebsite()
            ],
            'payment'   =>  [
                'id'        =>  $payment->getId(),
                'amount'    =>  $payment->getAmount(),
                'timestamp' =>  $payment->getCaptureTimestamp(),
                'method'    =>  $payment->getMethodWithDetail()
            ]
        ];

        $config = $app->config->get('applications.mailgun');

        if(isset($templateData['merchant']['billing_label']))
        {
            $subject = "Payment Successful for {$templateData['merchant']['billing_label']}";
        }
        else
        {
            $subject = "Payment Successful for {$templateData['payment']['amount']} INR";
        }

        Mail::queue(['html'=> 'emails/payment/customer', 'text'=> 'emails/payment/customer_text'], $templateData,
            function($message) use ($templateData, $config, $subject) {
                $message->to($templateData['customer']['email']);
                $message->from($config['from_email'], $config['from_name']);
                $message->subject($subject);
            }
        );
    }
}
