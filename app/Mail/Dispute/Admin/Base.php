<?php

namespace RZP\Mail\Dispute\Admin;

use RZP\Mail\Base\Constants;
use RZP\Mail\Dispute\Base as DisputeBase;

class Base extends DisputeBase
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->data = $data;
    }

    protected function addRecipients()
    {
        $this->to(Constants::MAIL_ADDRESSES[Constants::DISPUTES], Constants::DISPUTES);

        return $this;
    }

    protected function addCc()
    {
        return $this;
    }

    protected function addReplyTo()
    {
        return $this;
    }
}
