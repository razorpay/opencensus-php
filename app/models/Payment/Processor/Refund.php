<?php

namespace Models\Payment\Processor;

use BasicAuth;
use Constants\Mode;
use EE\Exception;
use EE\Error\ErrorCode;
use Http\Route;
use Mail;
use Models\Card;
use Models\Merchant;
use Models\Payment;
use Models\Transaction;
use Request;
use Trace\Trace;
use Trace\TraceCode;

trait Refund
{
    /**
     * Refunds a payment
     * @param  string   $id     Payment Id
     * @param  array    $input  Refund input params
     *
     * @return Payment\Refund\Entity
     */
    protected function refund($id, $input)
    {
        $payment = $this->retrieve($id);

        $refund = (new Payment\Refund\Entity)->build($input, $payment);

        $refund->merchant()->associate($this->merchant);

        if ($this->payment->isCaptured())
        {
            $this->validateMerchantBalance($refund);
        }

        $this->refund = $refund;

        $data = array(
            'payment'   => $payment->toArray(),
            'refund'    => $refund->toArray(),
            'amount'    => $refund->getAmount());

        $method = $refund->payment->getMethod();

        if ($method === Payment\Method::CARD)
        {
            $data['card'] = $refund->payment->card->toArray();
        }

        $gateway = $payment->getGateway();

        if (($payment->getTransactionId() !== null) or
            ($payment->isAuthorized() === false))
        {
            $this->callGatewayForRefund($data);
        }

        $this->recordRefund();

        //
        // Analytics
        //
        $this->notifyDashboard('refund', $this->refund);

        $this->sendRefundNotification();

        return $refund;
    }

    public function refundAuthorizedPayment($id, $input)
    {
        $payment = $this->retrieve($id);

        if ($this->payment->isAuthorized() === false)
        {
            throw new Exception\InvalidArgumentException(
                'Can only refund authorized payments here but ' .
                'the status is ' . $payment->getStatus());
        }

        $days = 5;

        if ($this->payment->getDaysSinceAuthorized() <= $days)
        {
            if ((isset($input['force'])) and
                ($input['force'] === '1'))
            {
                unset($input['force']);
            }
            else
            {
                throw new Exception\BadRequestValidationFailureException(
                    'The authorized payment is not older than: ' . $days . ' days');
            }
        }

        return $this->refund($id, $input);
    }

    public function refundCapturedPayment($id, $input)
    {
        $payment = $this->retrieve($id);

        if ($payment->isFullyRefunded())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_FULLY_REFUNDED);
        }

        if ($payment->isCaptured() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED);
        }

        return $this->refund($id, $input);
    }

    protected function callGatewayForRefund($data)
    {
        try
        {
            $this->callGatewayFunction(Payment\Action::REFUND, $data);
        }
        catch(BaseException $e)
        {
            $this->tracePaymentFailed(
                    $e->getError(),
                    TraceCode::PAYMENT_REFUND_FAILURE);

            throw $e;
        }
    }

    protected function refundTemplate()
    {
        return [
            'customer'  =>  [
                'email' =>  $this->payment->getEmail(),
                'phone' =>  $this->payment->getContact()
            ],
            'merchant'  =>  [
                'billing_label' =>  $this->payment->merchant->getBillingLabel(),
                'website'       =>  $this->payment->merchant->getWebsite(),
                'email'         =>  $this->payment->merchant->getTransactionReportEmail()
            ],
            'payment'   =>  [
                'id'        =>  $this->payment->getId(),
                'amount'    =>  "INR ".number_format($this->payment['amount']/100, 2),
                'timestamp' =>  $this->payment->getUpdatedAt(),
                'method'    =>  $this->payment->getMethodWithDetail()
            ],
            'refund'    => [
                'id'        =>  $this->refund->getId(),
                'amount'    =>  "INR ".number_format($this->refund->getAmount()/100, 2),
            ]
        ];
    }

    /**
     * Returns an array containing subjects for both
     * refund mails (merchant and customer)
     * @return array
     */
    protected function getRefundSubject($template)
    {
        $amount = $suffix = $template['payment']['amount'];

        if (isset($template['merchant']['billing_label']))
        {
            $suffix = $template['merchant']['billing_label'];
        }

        return [
            'merchant' => "Razorpay | Payment refunded for $amount",
            'customer' => "Refund Successful for $suffix"
        ];
    }

    protected function sendRefundNotification()
    {
        // Dont send mails in test mode
        // @todo: remove this somehow
        if (($this->mode === Mode::TEST) and
            ($this->app->environment('production') === true))
        {
            return;
        }

        $templateData = $this->refundTemplate();
        $subjects = $this->getRefundSubject($templateData);

        // First email the merchant
        Mail::queue('emails/refund/common',
            $templateData,
            function ($message) use ($templateData, $subjects)
            {
                $message->to($templateData['merchant']['email']);
                $message->subject($subjects['merchant']);
                // @todo: Keep this enabled for a while
                //$message->cc('notifications@razorpay.com');
            }
        );

        // And then the customer
        Mail::queue('emails/refund/common',
            $templateData,
            function ($message) use ($templateData, $subjects)
            {
                $message->to($templateData['customer']['email']);
                $message->subject($subjects['customer']);
            }
        );
    }

    protected function recordRefund()
    {
        $this->repo->transaction(function()
        {
            $payment = $this->payment;

            $this->repo->lockForUpdate($payment->getKey());

            $this->updatePaymentRefunded();

            $gateway = $payment->getGateway();

            if ((Payment\Gateway::supportsAuthAndCapture($gateway) === false) or
                ($payment->getCaptureTimestamp() !== null))
            {
                $txn = (new Transaction\Core)->createFromRefund($this->refund);

                $txn->saveOrFail();
            }

            $this->payment->saveOrFail();
            $this->refund->saveOrFail();
        });
    }

    protected function updatePaymentRefunded()
    {
        $this->payment->refundAmount($this->refund->getAmount());

        $this->trace(TraceCode::PAYMENT_REFUND_SUCCESS);
    }

    protected function validateMerchantBalance($refund)
    {
        $merchant = $refund->merchant;

        $balance = (new Merchant\Repository)->getMerchantBalance($merchant);

        if ($balance->getBalance() < $refund->getAmount())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_BALANCE);
        }
    }
}
