<?php

namespace RZP\Mail\Transaction;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class Transaction extends Mailable
{
    /**
     * @see \RZP\Models\Transaction\Notifier::$event
     * @var string
     */
    protected $event;

    /**
     * @var array
     */
    protected $balance;

    /**
     * @var array
     */
    protected $txn;

    /**
     * @var array
     */
    protected $source;

    /**
     * @var array
     */
    protected $merchant;

    public function __construct(string $event, array $balance, array $txn, array $source, array $merchant)
    {
        parent::__construct();

        $this->event    = $event;
        $this->balance  = $balance;
        $this->txn      = $txn;
        $this->source   = $source;
        $this->merchant = $merchant;
    }

    protected function addSender()
    {
        return $this->from(Constants::MAIL_ADDRESSES[Constants::NOREPLY], Constants::HEADERS[Constants::NOREPLY]);
    }

    protected function addReplyTo()
    {
        return $this->replyTo(Constants::MAIL_ADDRESSES[Constants::NOREPLY]);
    }

    protected function addRecipients()
    {
        return $this->to($this->merchant['email']);
    }

    protected function addMailData()
    {
        return $this->with('event', $this->event)
                    ->with('balance', $this->balance)
                    ->with('txn', $this->txn)
                    ->with('source', $this->source)
                    ->with('merchant', $this->merchant);
    }

    protected function addHeaders()
    {
        return $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $this->txn['id']);
            $headers->addTextHeader(MailTags::HEADER, MailTags::TRANSACTION_CREATED);
        });
    }

    protected function addSubject()
    {
        return $this->subject($this->getSubject());
    }
}
