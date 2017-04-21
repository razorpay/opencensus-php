<?php
namespace App\Admin;

use Mailgun\Mailgun as MailgunClient;

class Mailgun
{

    public function __construct()
    {
        $this->mailgunClient = new MailgunClient(config('mailgun.api_key'));
        $this->domain = config('mailgun.domain');
    }

    public function getLogs($input)
    {
        return $this->mailgunClient->get($this->domain . "/events", $input)->http_response_body;
    }

    public function getBounce($email)
    {
        return $this->mailgunClient->get($this->domain . "/bounces/$email")->http_response_body;
    }

    public function deleteBounce($email)
    {
        return $this->mailgunClient->delete($this->domain . "/bounces/$email")->http_response_body;
    }
}
