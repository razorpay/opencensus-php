<?php

namespace RZP\Mail\TrustedBadge;

use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;

abstract class Base extends Mailable
{
    /**
     * @param string $merchantId    The Primary Key  of the Merchant
     * @param string $merchantEmail The email address of the merchant
     * @param array  $data          Addition mail data for use by the template
     */
    public function __construct(string $merchantId, string $merchantEmail, array $data = [])
    {
        parent::__construct();

        $data['merchantId'] = $merchantId;
        $data['merchantEmail'] = $merchantEmail;

        $this->data = $data;
    }

    /**
     * Set the recipients of the message.
     *
     * @return $this
     */
    protected function addRecipients(): self
    {
        return $this->to($this->data['merchantEmail']);
    }

    /**
     * Set the sender of the message.
     *
     * @return $this
     */
    protected function addSender(): self
    {
        return $this->from(Constants::MAIL_ADDRESSES[Constants::NOREPLY], Constants::HEADERS[Constants::NOREPLY]);
    }

    /**
     * Set the "reply to" address of the message.
     *
     * @return $this
     */
    protected function addReplyTo(): self
    {
        return $this->replyTo(Constants::MAIL_ADDRESSES[Constants::NOREPLY], Constants::HEADERS[Constants::NOREPLY]);
    }

    /**
     * Set the "data" which is consumed by mail template
     *
     * @return $this
     */
    protected function addMailData(): self
    {
        $data = [
            'merchant_id' => $this->data['merchantId'],
        ];

        $this->with($data);

        return $this;
    }
}
