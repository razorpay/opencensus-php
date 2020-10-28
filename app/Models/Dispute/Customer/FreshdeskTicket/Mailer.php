<?php

namespace RZP\Models\Dispute\Customer\FreshdeskTicket;

use Mail;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Mail\Dispute\Customer\Base as CustomerDisputeMailer;

trait Mailer {
	private function sendPaymentNotCapturedEmail()
    {
        $data = [
            'customer' => [
                'name'  => $this->freshdeskTicket->getCustomerName(),
                'email' => $this->freshdeskTicket->getCustomerEmail(),
            ],
            'merchant' => [
                'name' => $this->merchant->getName(),
            ],
            'payment' => [
                'id'                   => $this->payment->getPublicId(),
                'amount'               => $this->payment->getAmount() / 100,
                'created_at_str'       => Carbon::createFromTimestamp($this->payment->getCreatedAt(), Timezone::IST)->format('jS F, Y'),
                'refund_init_date_str' => Carbon::createFromTimestamp($this->payment->getRefundAt(), Timezone::IST)->format('jS F, Y'),
                'refund_done_date_str' => Carbon::createFromTimestamp($this->payment->getRefundAt(), Timezone::IST)->addDays(Constants::REFUND_BUFFER)->format('jS F, Y'),
            ],
            'ticket' => [
                'id'          => $this->freshdeskTicket->getTicketId(),
                'subcategory' => $this->freshdeskTicket->getSubcategory(),
            ],
        ];

        Mail::queue(new CustomerDisputeMailer($data, Constants::ACTION_PAYMENT_NOT_CAPTURED));
    }

    private function sendPaymentFullyRefundedEmail()
    {
        $refundIdList = [];
        $refunds = $this->payment->refunds;

        foreach ($refunds as $refund)
        {
            $refundIdList []= $refund->getPublicId();
        }

        $data = [
            'customer' => [
                'name'  => $this->freshdeskTicket->getCustomerName(),
                'email' => $this->freshdeskTicket->getCustomerEmail(),
            ],
            'ticket' => [
                'id'          => $this->freshdeskTicket->getTicketId(),
                'subcategory' => $this->freshdeskTicket->getSubcategory(),
            ],
            'refundIdList' => $refundIdList,
        ];

        Mail::queue(new CustomerDisputeMailer($data, Constants::ACTION_PAYMENT_FULLY_REFUNDED));
    }

    private function sendPaymentAlreadyDisputedEmail()
    {
        $dispute = $this->repo->dispute->getOpenDisputeByPaymentId($this->payment->getId());

        $data = [
            'customer' => [
                'name'  => $this->freshdeskTicket->getCustomerName(),
                'email' => $this->freshdeskTicket->getCustomerEmail(),
            ],
            'payment' => [
                'id' => $this->payment->getPublicId(),
            ],
            'ticket' => [
                'id'          => $this->freshdeskTicket->getTicketId(),
                'subcategory' => $this->freshdeskTicket->getSubcategory(),
            ],
            'dispute' => [
                'id' => $dispute->getPublicId(),
            ]
        ];

        Mail::queue(new CustomerDisputeMailer($data, Constants::ACTION_PAYMENT_DISPUTED));
    }

    private function sendMerchantDisabledEmail()
    {
        $data = [
            'customer' => [
                'name'  => $this->freshdeskTicket->getCustomerName(),
                'email' => $this->freshdeskTicket->getCustomerEmail(),
            ],
            'merchant' => [
                'name' => $this->merchant->getName(),
            ],
            'ticket' => [
                'id'          => $this->freshdeskTicket->getTicketId(),
                'subcategory' => $this->freshdeskTicket->getSubcategory(),
            ],
        ];

        Mail::queue(new CustomerDisputeMailer($data, Constants::ACTION_MERCHANT_DISABLED));
    }

    private function sendCreateDisputeEmail()
    {
        $data = [
            'customer' => [
                'name'  => $this->freshdeskTicket->getCustomerName(),
                'email' => $this->freshdeskTicket->getCustomerEmail(),
            ],
            'merchant' => [
                'name' => $this->merchant->getName(),
            ],
            'ticket' => [
                'id'          => $this->freshdeskTicket->getTicketId(),
                'subcategory' => $this->freshdeskTicket->getSubcategory(),
            ],
        ];

        Mail::queue(new CustomerDisputeMailer($data, Constants::ACTION_CREATE_DISPUTE));
    }
}
