<?php

namespace RZP\Mail\Settlement;

class Reconciliation extends Base
{
    protected $channel;

    public function __construct(array $data)
    {
        $this->channel = $data['channel'];

        parent::__construct($data);
    }

    protected function getFromHeader()
    {
        return Constants::HEADER_MAP[$this->channel];
    }

    protected function addSubject()
    {
        $subject = $this->getSubject();

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $subject = $this->getSubject();

        $this->data['subject'] = $subject;

        $this->with($this->data);

        return $this;
    }

    protected function getSubject()
    {
        return 'Re: '. ucfirst($this->channel) . ' Settlement files for ' . $this->data['date'];
    }

    protected function getMailTag()
    {
        return Constants::MAILTAG_MAP[$this->channel];
    }
}
