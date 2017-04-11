<?php
namespace App\Admin;

use Mailgun\Mailgun as MailgunClient;

class Mailgun
{

    public function __construct()
    {
        $this->mailgunClient = new MailgunClient(config('mailgun.api_key'));
    }

    public function getMailgunLogs($input)
    {
        $error = $data = null;

        $domain = config('mailgun.domain');

        try
        {
            $data = $this->mailgunClient->get("$domain/events", $input)->http_response_body;
        }
        catch (\Exception $e)
        {
            $error = [$e->getMessage()];
        }

        return [$error, $data];
    }
}
