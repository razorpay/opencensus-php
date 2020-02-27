<?php

namespace RZP\Services;

use Requests;
use RZP\Trace\TraceCode;
use RZP\Foundation\Application;
use RZP\Models\Merchant\FreshdeskTicket\Service as FreshdeskTicketService;

class FreshdeskTicketClient
{
    protected $isSandbox;

    protected $isMock;

    const HTTP_GET     = 'GET';
    const HTTP_POST    = 'POST';

    public function __construct(Application $app)
    {
        $this->app    = $app;

        $this->trace  = $app['trace'];

        $this->config = $app['config']->get('applications.freshdesk');

        $this->isSandbox = $this->config['sandbox'];

        $this->isMock    = $this->config['mock'];

        return $this;
    }

    /**
     * Get ticket status for the given $ticketId
     *
     * @param string $ticketId
     * @return string $ticketStatus
     */
    public function getReserveBalanceTicketStatus(string $ticketId) : array
    {
        $url = $this->getUrl('tickets/'.$ticketId);

        $auth = $this->getAuth();

        $response = $this->makeRequestAndGetStatus(self::HTTP_GET, $url, $auth, []);

        return $response;
    }

    /**
     * Get URL
     * @param $route
     * @return string
     */
    protected function getUrl($route) : string
    {
        if ($this->isSandbox === true)
        {
            return trim($this->config['sandbox_url']) . '/' . $route;
        }

        return trim($this->config['url']) . '/' . $route;
    }

    protected function makeRequestAndGetStatus(string $method, string $url, string $auth, array $content)
    {
        if (empty($content) === false)
        {
            $content = json_encode($content);
        }

        $request = [
            'options'            => [
                'timeout'        => 180,
            ],
            'content'            => $content,
            'method'             => $method,
            'headers'            => [
                'Content-Type'   => 'application/json',
                'Authorization'  => $auth
            ],
            'url'                => $url,
        ];

        $trace_request = $this->getRedactedRequest($request);

        $this->trace->info(TraceCode::SUPPORT_TICKET_STATUS_REQUEST,
            [
                'request' => $trace_request
            ]
        );

        $response = Requests::request(
            $request['url'],
            $request['headers'],
            $request['content'],
            $request['method'],
            $request['options']
        );

        $response = json_decode($response->body, true);

        $this->trace->info(TraceCode::SUPPORT_TICKET_STATUS_RESPONSE,
            [
                'response status' => $response['status']
            ]
        );

        return $response;
    }

    private function getRedactedRequest(array $request) : array
    {
        unset($request['headers']['Authorization']);

        return $request;
    }

    private function getAuth() : string
    {
        return $this->isMock ? '' :
            (($this->isSandbox === true) ?
                $this->config['sandbox_token'] :
                $this->config['token']
            );
    }
}
