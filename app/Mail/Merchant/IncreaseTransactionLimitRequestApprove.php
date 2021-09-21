<?php


namespace RZP\Mail\Merchant;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Models\Merchant\Entity;

class IncreaseTransactionLimitRequestApprove extends Mailable
{
    protected $newTransactionLimit;

    protected $merchant;

    protected $user;

    public function __construct(array $merchant, int $newTransactionLimit, array $user)
    {
        parent::__construct();

        $this->user = $user;

        $this->merchant = $merchant;

        $this->newTransactionLimit = $newTransactionLimit;
    }

    protected function addRecipients()
    {
        $this->to($this->user['email']);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject('Razorpay: Transaction Limit updated successfully ' . $this->merchant['name'] ?? $this->merchant['id']);

        return $this;
    }

    protected function addMailData()
    {
        parent::addMailData();

        $data = [
            'updated_transaction_limit'  => $this->merchant[Entity::MAX_PAYMENT_AMOUNT],
            'merchant_name'              => $this->merchant[Entity::NAME]
        ];

        $this->with($data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.increase_transaction_limit_request_approve');

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE);
        });

        return $this;
    }
}
