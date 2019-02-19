<?php

namespace RZP\Mail\Admin\FileUpload;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;

class Dispute extends Base
{
    protected $data;

    protected function addSubject()
    {
        $orgName = $this->data['orgName'];

        $today = Carbon::today(Timezone::IST)->format('d/m/Y');

        $this->data['date'] = $today;

        $subject = $orgName . '-' . $today . ' Chargeback Request';

        $this->subject($subject);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.admin.bank_dispute_file');

        return $this;
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::DISPUTES];

        $fromName = Constants::HEADERS[Constants::DISPUTES];

        $this->from($fromEmail, $fromName);

        return $this;
    }

    protected function addRecipients()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::BANK_DISPUTE_FILE];

        $name = Constants::HEADERS[Constants::BANK_DISPUTE_FILE];

        $this->to($email, $name);

        return $this;
    }
}
