<?php
namespace App\Admin;

use App\Trace\TraceCode;
use Illuminate\Config\Repository;
use Mailgun\Mailgun as MailgunClient;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Mailgun\HttpClient\HttpClientConfigurator;
use Illuminate\Contracts\Foundation\Application;

class Mailgun
{

    private MailgunClient $mailgunClient;

    /**
     * @var Repository|Application|mixed
     */
    private mixed $domain;

    public function __construct()
    {

        $configurator = (new HttpClientConfigurator())
            ->setApiKey(config('mailgun.api_key'))
            ->setEndpoint('https://api.mailgun.net');

        $this->mailgunClient = new MailgunClient($configurator);
        $this->domain = config('mailgun.domain');
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function getLogs($input)
    {
        return json_decode($this->mailgunClient->httpClient()->httpGet(sprintf('/v3/%s/events', $this->domain), $input)->getBody());
    }
}
