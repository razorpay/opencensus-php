<?php

namespace RZP\Services;

use Requests;
use RZP\Trace\TraceCode;
use RZP\Foundation\Application;
use RZP\Models\Merchant\FreshdeskTicket\Constants;

class FreshdeskTicketClient
{
    protected $isSandbox;

    protected $isMock;

    const HTTP_GET     = 'GET';
    const HTTP_POST    = 'POST';

    // Freshdesk API Endpoints
    const FETCH_TICKET        = 'tickets/%s';
    const FILTER_TICKETS      = 'search/tickets';
    const FETCH_CONVERSATIONS = 'tickets/%s/conversations';
    const POST_TICKET_REPLY   = 'tickets/%s/reply';

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
     * Get tickets for the given $merchantID
     *
     * @param array $queryParams
     * @param string $urlKey
     * @param string $authKey
     * @return array $response
     */
    public function getTickets(array $queryParams, string $urlKey = 'url') : array
    {
        $authKey = $this->getAuthKey($urlKey);

        $queryString = http_build_query($queryParams);

        $url = $this->getUrl(self::FILTER_TICKETS . '?' . $queryString, $urlKey);

        $auth = $this->getAuth($authKey);

        $response = $this->makeRequestAndGetFreshdeskResponse(self::HTTP_GET, $url, $auth, []);

        return $response;
    }

    /**
     * Get tickets for the given $merchantID
     *
     * @param string $ticketId
     * @param array $queryParams
     * @param $urlKey
     * @param $authKey
     * @return array $response
     */
    public function getTicketConversations(string $ticketId, array $queryParams, $urlKey = 'url') : array
    {
        $authKey = $this->getAuthKey($urlKey);

        $queryString = http_build_query($queryParams);

        $url = $this->getUrl(sprintf(self::FETCH_CONVERSATIONS, $ticketId) . '?' . $queryString, $urlKey);

        $auth = $this->getAuth($authKey);

        $response = $this->makeRequestAndGetFreshdeskResponse(self::HTTP_GET, $url, $auth, []);

        return $response;
    }

    /**
     * Get tickets for the given $merchantID
     *
     * @param string $ticketId
     * @param $urlKey
     * @param $authKey
     * @return array $response
     */
    public function getTicketWithStats(string $ticketId, $urlKey = 'url') : array
    {
        $authKey = $this->getAuthKey($urlKey);

        $url = $this->getUrl(sprintf(self::FETCH_TICKET, $ticketId) . '?include=stats', $urlKey);

        $auth = $this->getAuth($authKey);

        $response = $this->makeRequestAndGetFreshdeskResponse(self::HTTP_GET, $url, $auth, []);

        return $response ?? [];
    }

    /**
     * Get tickets for the given $merchantID
     *
     * @param string $ticketId
     * @param array $input
     * @param $urlKey
     * @param $authKey
     * @return array $response
     */
    public function postTicketReply(string $ticketId, array $input, $urlKey = 'url') : array
    {
        $authKey = $this->getAuthKey($urlKey);

        $url = $this->getUrl(sprintf(self::POST_TICKET_REPLY, $ticketId), $urlKey);

        $auth = $this->getAuth($authKey);

        $response = $this->makeRequestAndGetFreshdeskResponse(self::HTTP_POST, $url, $auth, $input);

        return $response ?? [];
    }

    /**
     * Get URL
     * @param $route
     * @param $urlKey
     * @return string
     */
    protected function getUrl($route, $urlKey = 'url') : string
    {
        if ($this->isSandbox === true)
        {
            return trim($this->config['sandbox_url']) . '/' . $route;
        }

        return trim($this->config[$urlKey]) . '/' . $route;
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

    protected function makeRequestAndGetFreshdeskResponse(string $method, string $url, string $auth, array $content)
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

        $this->trace->info(TraceCode::FRESHDESK_SUPPORT_TICKETS_REQUEST,
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

        $this->trace->info(TraceCode::FRESHDESK_SUPPORT_TICKETS_RESPONSE,
            [
                'response' => $response['total'] ?? count($response) ?? 0
            ]
        );

        return $response;
    }

    private function getAuthKey($urlKey) : string
    {
        $authKey = 'token';

        if ($urlKey === Constants::URL2)
        {
            $authKey = 'token2';
        }

        return $authKey;
    }

    private function getRedactedRequest(array $request) : array
    {
        unset($request['headers']['Authorization']);

        return $request;
    }

    private function getAuth($authKey = 'token') : string
    {
        return $this->isMock ? '' :
            (($this->isSandbox === true) ?
                $this->config['sandbox_token'] :
                $this->config[$authKey]
            );
    }
}
