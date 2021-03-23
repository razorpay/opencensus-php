<?php

namespace RZP\Mail\Beam;

use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class BeamRequestFailure extends Mailable
{
    /**
     * @var string
     */
    protected $recipient;

    /**
     * @var string
     */
    protected $headline;

    /**
     * @var string
     */
    protected $body;

    /**
     * BeamRequestFailure constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        parent::__construct();

        $this->body      = $data['body'];

        $this->headline  = $data['subject'];

        $this->recipient = $data['recipient'];
    }

    /**
     * @return $this
     */
    protected function addRecipients()
    {
        $this->to($this->recipient);

        return $this;
    }

    /**
     * @return $this
     */
    protected function addSender()
    {
        $fromName = Constants::HEADERS[Constants::BEAM_FAILURE];

        $sender = Constants::MAIL_ADDRESSES[Constants::SETTLEMENTS];

        $this->from($sender, $fromName);

        return $this;
    }

    /**
     * @return $this
     */
    protected function addSubject()
    {
        $this->subject($this->headline);

        return $this;
    }

    /**
     * @return $this
     */
    protected function addMailData()
    {
        $data['body'] = $this->body;

        $this->with($data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.message');

        return $this;
    }
}
