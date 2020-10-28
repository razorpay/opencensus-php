<?php

namespace RZP\Mail\Dispute\Customer;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Exception\LogicException;
use RZP\Models\Dispute\Customer\FreshdeskTicket\Subcategory;

class Base extends Mailable
{
    const ACTION_PARTIAL_VIEW_MAP = [
        'create_dispute'         => 'emails.partials.dispute.customer.create_dispute',
        'merchant_disabled'      => 'emails.partials.dispute.customer.merchant_disabled',
        'payment_disputed'       => 'emails.partials.dispute.customer.payment_disputed',
        'payment_fully_refunded' => 'emails.partials.dispute.customer.payment_fully_refunded',
        'payment_not_captured'   => 'emails.partials.dispute.customer.payment_not_captured',
    ];

    protected $data;

    protected $action;

    public function __construct(array $data, string $action)
    {
        parent::__construct();

        $this->data = $data;

        $this->action = $action;
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::NOREPLY];

        $fromName = Constants::HEADERS[Constants::NOREPLY];

        $this->from($fromEmail, $fromName);

        return $this;
    }

    protected function addRecipients()
    {
        $customerEmail = $this->data['customer']['email'];

        $this->to($customerEmail);

        return $this;
    }

    protected function addMailData()
    {
        $this->setPartialView();

        $this->with($this->data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $ticketId = $this->data['ticket']['id'];

            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::CUSTOMER_DISPUTE_FRESHDESK);

            $headers->addTextHeader(MailTags::HEADER, $ticketId);
        });

        return $this;
    }

    protected function addSubject()
    {
        $subject = null;

        if ($this->data['ticket']['subcategory'] === Subcategory::DISPUTE_A_PAYMENT)
        {
            $subject = '[Customer] Dispute a Payment';
        }
        else if ($this->data['ticket']['subcategory'] === Subcategory::REPORT_FRAUD)
        {
            $subject = '[Customer] Report a Fraud';
        }

        $this->subject($subject);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->setPartialView();

        $this->view('emails.dispute.customer.base');

        return $this;
    }

    protected function setPartialView()
    {
        if (isset($this->data['partialView']) === true)
        {
            return;
        }

        if (isset(self::ACTION_PARTIAL_VIEW_MAP[$this->action]) === false)
        {
            throw new LogicException("[Dispute] Invalid Customer mail action: {$this->action}");
        }

        $this->data['partialView'] = self::ACTION_PARTIAL_VIEW_MAP[$this->action];
    }
}
