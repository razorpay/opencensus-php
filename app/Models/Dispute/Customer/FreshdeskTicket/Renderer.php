<?php

namespace RZP\Models\Dispute\Customer\FreshdeskTicket;

use View;
use Carbon\Carbon;
use RZP\Constants\Timezone;

trait Renderer {
	private function renderPaymentNotCapturedBody()
    {
        $data = [
            'customer' => [
                'name'  => $this->freshdeskTicket->getCustomerName(),
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
            'partialView' => Constants::PARTIAL_VIEW_TEMPLATE_PREFIX . '.' . Constants::ACTION_PAYMENT_NOT_CAPTURED,
        ];

        $renderedBody = View::make(Constants::BASE_VIEW_TEMPLATE)->with($data)->render();

        return $renderedBody;
    }

    private function renderPaymentFullyRefundedBody()
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
            ],
            'ticket' => [
                'id'          => $this->freshdeskTicket->getTicketId(),
                'subcategory' => $this->freshdeskTicket->getSubcategory(),
            ],
            'refundIdList' => $refundIdList,
            'partialView' => Constants::PARTIAL_VIEW_TEMPLATE_PREFIX . '.' . Constants::ACTION_PAYMENT_FULLY_REFUNDED,
        ];

        $renderedBody = View::make(Constants::BASE_VIEW_TEMPLATE)->with($data)->render();

        return $renderedBody;
    }

    private function renderPaymentAlreadyDisputedBody()
    {
        $dispute = $this->repo->dispute->getOpenDisputeByPaymentId($this->payment->getId());

        $data = [
            'customer' => [
                'name'  => $this->freshdeskTicket->getCustomerName(),
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
            ],
            'partialView' => Constants::PARTIAL_VIEW_TEMPLATE_PREFIX . '.' . Constants::ACTION_PAYMENT_DISPUTED,
        ];

        $renderedBody = View::make(Constants::BASE_VIEW_TEMPLATE)->with($data)->render();

        return $renderedBody;
    }

    private function renderMerchantDisabledBody()
    {
        $data = [
            'customer' => [
                'name'  => $this->freshdeskTicket->getCustomerName(),
            ],
            'merchant' => [
                'name' => $this->merchant->getName(),
            ],
            'ticket' => [
                'id'          => $this->freshdeskTicket->getTicketId(),
                'subcategory' => $this->freshdeskTicket->getSubcategory(),
            ],
            'partialView' => Constants::PARTIAL_VIEW_TEMPLATE_PREFIX . '.' . Constants::ACTION_MERCHANT_DISABLED,
        ];

        $renderedBody = View::make(Constants::BASE_VIEW_TEMPLATE)->with($data)->render();

        return $renderedBody;
    }

    private function renderCreateDisputeBody()
    {
        $data = [
            'customer' => [
                'name'  => $this->freshdeskTicket->getCustomerName(),
            ],
            'merchant' => [
                'name' => $this->merchant->getName(),
            ],
            'ticket' => [
                'id'          => $this->freshdeskTicket->getTicketId(),
                'subcategory' => $this->freshdeskTicket->getSubcategory(),
            ],
            'partialView' => Constants::PARTIAL_VIEW_TEMPLATE_PREFIX . '.' . Constants::ACTION_CREATE_DISPUTE,
        ];

        $renderedBody = View::make(Constants::BASE_VIEW_TEMPLATE)->with($data)->render();

        return $renderedBody;
    }
}
