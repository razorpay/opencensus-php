<?php

namespace Models\Payment\Processor;

use App;
use Constants\Mode;
use Mail;
use Models\Payment;
use Services\SlackPoster;

class Notify
{
    use SlackPoster;
    const AUTHORIZED = 'authorized';
    const CAPTURED   = 'captured';
    const REFUNDED   = 'refunded';

    function __construct(Payment\Entity $payment)
    {
        $this->app = App::getFacadeRoot();

        $this->payment = $payment;
        $this->template = $this->templateData();
        $this->flatTemplate = $this->flatten($this->template);
        $this->config = $this->app->config->get('applications.mailgun');
        $this->mode = $this->app['rzp.mode'];
    }

    /**
     * Allows the notifier to be used for a refund as well
     * @param Payment\Refund\Entity $refund Refund entity
     */
    public function addRefund(Payment\Refund\Entity $refund)
    {
        $this->refund = $refund;
    }

    public function trigger($event)
    {
        if(!$this->isEnabled())
        {
            return;
        }
        switch ($event) {
            case self::AUTHORIZED:
                $this->notifyCustomerAuthorized();
                $this->postSlackAuthorized();
                break;

            case self::CAPTURED:
                $this->postSlackCaptured();
                // We send an email to the merchant
                $this->notifyMerchantCaptured();
                break;

            case self::REFUNDED:
                $this->postSlackRefunded();
                $this->notifyMerchantRefunded();
                $this->notifyCustomerRefunded();
        }
    }

    protected function postSlackAuthorized()
    {
        $this->slackPost('Payment Authorized', $this->flatTemplate);
    }

    protected function postSlackCaptured()
    {
        $this->slackPost('Payment Captured', $this->template['payment']);
    }

    protected function subjectPaymentSuccessful()
    {
        if(isset($this->template['merchant']['billing_label']))
        {
            return "Razorpay | Payment Successful for {$this->template['merchant']['billing_label']}";
        }
        else
        {
            return "Razorpay | Payment Successful for {$this->template['payment']['amount']}";
        }
    }

    protected function notifyCustomerAuthorized()
    {
        $data = $this->template;
        $subject = $this->subjectPaymentSuccessful();
        $config = $this->config;

        Mail::queue(['html'=> 'emails.payment.customer', 'text'=> 'emails.payment.customer_text'], $this->template,
            function($message) use ($data, $config, $subject){
                $message->to($data['customer']['email']);
                $message->from($config['from_email'], $config['from_name']);
                $message->subject($subject);
            }
        );
    }

    protected function notifyMerchantCaptured()
    {
        $subject = $this->subjectPaymentSuccessful();
        if($this->template['payment']['orderId'])
        {
            $subject = "Payment Successful for #{$this->template['payment']['orderId']}";
        }

        $data = $this->template;
        $config = $this->config;

        Mail::queue(['html'=> 'emails.payment.merchant', 'text'=> 'emails.payment.merchant_text'], $this->template,
            function($message) use ($data, $subject, $config){
                $message->to($data['merchant']['email']);
                $message->from($config['from_email'], $config['from_name']);
                $message->subject($subject);
            }
        );
    }

    protected function templateData()
    {
        $data  = [
            'customer'  =>  [
                'email' =>  $this->payment->getEmail(),
                'phone' =>  $this->payment->getContact()
            ],
            'merchant'  =>  [
                'billing_label' =>  $this->payment->merchant->getBillingLabel(),
                'website'       =>  $this->payment->merchant->getWebsite(),
                // This is the reporting email address for the merchant
                'email'         =>  $this->payment->merchant->getTransactionReportEmail()
            ],
            'payment'   =>  [
                'id'        =>  $this->payment->getId(),
                'amount'    =>  "INR ".number_format($this->payment['amount']/100, 2),
                'timestamp' =>  $this->payment->getUpdatedAt(),
                'captured_at' => $this->payment->getAttribute('captured_at'),
                // note that payment method is unavailable to the merchant
                'method'    =>  $this->payment->getMethodWithDetail(),
                'orderId'   =>  $this->payment->getOrderId()
            ]
        ];

        if ($this->refund)
        {
            $data['refund'] = [
                'id'        =>  $this->refund->getId(),
                'amount'    =>  $this->refund->getAmount(),
                'timestamp' =>  $this->refund->getCreatedAt()
            ];
        }

        return $data;
    }

    protected function flatten(array $data)
    {
        $result = [];
        foreach ($data as $category => $arr)
        {
            foreach ($arr as $key => $value)
            {
                $result["$category.$key"] = $value;
            }
        }
        return $result;
    }

    /**
     * Whether or not we need to trigger the notifications
     * @return boolean
     */
    protected function isEnabled()
    {
        // We only send mails if Mode is not TEST
        // or if the env=dev
        if($this->app->environment('dev'))
            return true;

        if($this->mode === Mode::TEST)
            return false;

        return true;
    }
}
