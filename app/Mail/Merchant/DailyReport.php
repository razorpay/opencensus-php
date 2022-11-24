<?php

namespace RZP\Mail\Merchant;

use App;
use Symfony\Component\Mime\Email;

use RZP\Models\Merchant;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\OrgWiseConfig;

class DailyReport extends Mailable
{
    protected $data;

    protected $merchant;

    public function __construct(array $data, array $merchant)
    {
        parent::__construct();

        $orgData = $this->getOrgData($merchant);

        $this->data = array_merge($orgData, $data);

        $this->merchant = $merchant;
    }

    protected function getOrgData($merchant)
    {
        $app = App::getFacadeRoot();

        $repo = $app['repo'];

        $merchantEntity = $repo->merchant->findOrFailPublic($merchant[Merchant\Entity::ID]);

        return OrgWiseConfig::getOrgDataForEmail($merchantEntity);
    }

    protected function addRecipients()
    {
        $this->to($this->data['email']);

        return $this;
    }

    protected function addSender()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::REPORTS];

        $this->from($email);

        return $this;
    }

    protected function addReplyTo()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::NOREPLY];
        $header = Constants::HEADERS[Constants::NOREPLY];

        $this->replyTo($email, $header);

        return $this;
    }

    protected function addCc()
    {
        $email = [];

        $this->cc($email);

        return $this;
    }

    protected function addSubject()
    {
        $subject = 'Razorpay | Daily Transaction Report for ' . $this->data['date'];

        $this->subject($subject);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $this->merchant['id']);

            $headers->addTextHeader(MailTags::HEADER, MailTags::DAILY_REPORT);
        });

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.daily_report');

        return $this;
    }
}
