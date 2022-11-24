<?php

namespace RZP\Mail\Gateway\Nach;

use App;
use Carbon\Carbon;
use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Mailable;

class BaseV2 extends Mailable
{
    protected $data;

    protected $type;

    protected $emails;

    public function __construct(array $data, string $type, array $emails = [])
    {
        parent::__construct();

        $this->data = $data;

        $this->type = $type;

        $this->emails = $emails;
    }

    protected function addSender()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::NACH];

        $header = Constants::HEADER_MAP[$this->type];

        $this->from($email, $header);

        return $this;
    }

    protected function addRecipients()
    {
        // added internal recipients of razorpay and citi mail ids in gateway parameters
        // "bangalore.clearing@citi.com", "cgsl.iwdw.ecsdr@citi.com", "payment-apps-subscriptions@razorpay.com"

        $this->to($this->emails);
        
        return $this;
    }

    protected function addSubject()
    {
        $subject = $this->getSubject();

        $this->subject($subject);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view("emails.nach.debit");

        return $this;
    }

    protected function addMailData()
    {
        $mailData = [
            'debit_files'            =>  $this->data['debit_files'],
            'summary_files'          =>  $this->data['summary_files'],
        ];

        $this->with($mailData);

        return $this;
    }

    protected function addHeaders()
    {
        $header = Constants::MAILTAG_MAP[$this->type];

        $this->withSymfonyMessage(function (Email $message) use ($header)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $header);
        });

        return $this;
    }

    protected function getSubject()
    {
        $today = Carbon::now(Timezone::IST)->format('d-m-Y');

        return Constants::SUBJECT_MAP[$this->type] . $today;
    }
}
