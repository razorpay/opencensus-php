<?php

namespace RZP\Services;

use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use RZP\Foundation\Application;
use RZP\Models\Merchant\FreshdeskTicket\Metric;
use RZP\Models\Merchant\FreshdeskTicket\Constants;

class FreshdeskTicketClient
{
    protected $isSandbox;

    protected $isMock;

    protected $route;

    const HTTP_GET     = 'GET';
    const HTTP_POST    = 'POST';
    const HTTP_PUT     = 'PUT';

    // Freshdesk API Endpoints
    const CREATE_TICKET       = 'tickets';
    const FETCH_TICKET        = 'tickets/%s';
    const FILTER_TICKETS      = 'search/tickets';
    const FETCH_CONVERSATIONS = 'tickets/%s/conversations';
    const POST_TICKET_REPLY   = 'tickets/%s/reply';
    const LIST_TICKETS        = 'tickets';
    const UPDATE_TICKET       = 'tickets/%s';
    const UPDATE_NOTE         = 'tickets/%s/notes';
    const SEND_OUTBOUND_EMAIL = 'tickets/outbound_email';
    const FETCH_AGENT         = 'agents/%s';
    const FETCH_AGENTS        = 'agents';

    const REQUEST_REDACT_FIELDS = [
        'content',
        'headers',
    ];

    const URL_TOKEN_MAP = [
        Constants::URL2        => 'token2',
        Constants::URLX        => 'tokenx',
        Constants::URLCAP      => 'tokencap',
        Constants::URLIND      => 'tokenind',
    ];

    public function __construct(Application $app)
    {
        $this->app          = $app;

        $this->trace        = $app['trace'];

        $this->config       = $app['config']->get('applications.freshdesk');

        $this->isSandbox    = $this->config['sandbox'];

        $this->isMock       = $this->config['mock'];

        $this->route        = $this->app['api.route'];

        return $this;
    }

    /**
     * Get ticket status for the given $ticketId
     *
     * @param string $ticketId
     * @param string $urlKey
     * @return array $ticketStatus
     */
    public function getReserveBalanceTicketStatus(string $ticketId, $urlKey = 'url') : array
    {
        $url = $this->getUrl('tickets/'.$ticketId, $urlKey);

        $auth = $this->getAuth();

        $response = $this->makeRequestAndGetStatus(self::HTTP_GET, $url, $auth, []);

        return $response;
    }

    /*
     * Get tickets for the given $merchantID
     *
     * @param array $queryParams
     * @param string $urlKey
     * @param string $authKey
     * @return array $response
     */
    public function getTickets(array $queryParams, string $urlKey = 'urlind') : array
    {
        $authKey = $this->getAuthKey($urlKey);

        $queryString = http_build_query($queryParams);

        $url = $this->getUrl(self::FILTER_TICKETS . '?' . $queryString, $urlKey);

        $auth = $this->getAuth($authKey);

        $response = $this->makeRequestAndGetFreshdeskResponse(self::HTTP_GET, $url, $auth, []);

        return $response ?? [];
    }

    /**
     * Create ticket
     *
     * @param array $queryParams
     * @param string $urlKey
     * @return array $response
     */
    public function postTicket(array $input, $urlKey = 'urlind') : array
    {
        $authKey = $this->getAuthKey($urlKey);

        $url = $this->getUrl(self::CREATE_TICKET, $urlKey);

        $auth = $this->getAuth($authKey);

        $response = $this->makeRequestAndGetFreshdeskResponse(self::HTTP_POST, $url, $auth, $input);

        return $response ?? [];
    }

    /**
     * Send outbound email
     *
     * @param array  $input
     * @param string $urlKey
     * @return array $response
     */
    public function sendOutboundEmail(array $input, $urlKey = 'urlind') : array
    {
        $authKey = $this->getAuthKey($urlKey);

        $url = $this->getUrl(self::SEND_OUTBOUND_EMAIL, $urlKey);

        $auth = $this->getAuth($authKey);

        $response = $this->makeRequestAndGetFreshdeskResponse(self::HTTP_POST, $url, $auth, $input);

        return $response ?? [];
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
    public function getTicketConversations(string $ticketId, array $queryParams, $urlKey = 'urlind') : array
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
    public function getTicketWithStats(string $ticketId, $urlKey = 'urlind') : array
    {
        $authKey = $this->getAuthKey($urlKey);

        $url = $this->getUrl(sprintf(self::FETCH_TICKET, $ticketId) . '?include=stats', $urlKey);

        $auth = $this->getAuth($authKey);

        $response = $this->makeRequestAndGetFreshdeskResponse(self::HTTP_GET, $url, $auth, []);

        return $response ?? [];
    }

    public function getCustomerTickets($queryString, string $urlKey = 'urlind')
    {
        $authKey = $this->getAuthKey($urlKey);

        $url = $this->getUrl(self::LIST_TICKETS . '?' . $queryString.'&include=description', $urlKey);

        $auth = $this->getAuth($authKey);

        $response = $this->makeRequestAndGetFreshdeskResponse(self::HTTP_GET, $url, $auth, []);

        return $response;
    }

    public function getAgents($queryString, string $urlKey = 'urlind')
    {
        $authKey = $this->getAuthKey($urlKey);

        $url = $this->getUrl(self::FETCH_AGENTS . '?' . $queryString, $urlKey);

        $auth = $this->getAuth($authKey);

        $response = $this->makeRequestAndGetFreshdeskResponse(self::HTTP_GET, $url, $auth, []);

        return $response;
    }

    public function fetchTicketById(string $ticketId, $urlKey = 'urlind')
    {
        $authKey = $this->getAuthKey($urlKey);

        $url = $this->getUrl(sprintf(self::FETCH_TICKET, $ticketId) . '?include=requester', $urlKey);

        $auth = $this->getAuth($authKey);

        $response = $this->makeRequestAndGetFreshdeskResponse(self::HTTP_GET, $url, $auth, []);

        return $response ?? [];
    }

    public function fetchAgentById(string $agentId, $urlKey = 'urlind')
    {
        $authKey = $this->getAuthKey($urlKey);

        $url = $this->getUrl(sprintf(self::FETCH_AGENT, $agentId), $urlKey);

        $auth = $this->getAuth($authKey);

        $response = $this->makeRequestAndGetFreshdeskResponse(self::HTTP_GET, $url, $auth, []);

        return $response ?? [];
    }

    public function updateTicketV2(string $ticketId, array $input, $urlKey = 'urlind')
    {
        $authKey = $this->getAuthKey($urlKey);

        $url = $this->getUrl(sprintf(self::UPDATE_TICKET, $ticketId), $urlKey);

        $auth = $this->getAuth($authKey);

        $response = $this->makeRequestAndGetFreshdeskResponse(self::HTTP_PUT, $url, $auth, $input);

        return $response ?? [];
    }

    public function addNoteToTicket(string $ticketId, array $input, $urlKey = 'urlind')
    {
        $authKey = $this->getAuthKey($urlKey);

        $url = $this->getUrl(sprintf(self::UPDATE_NOTE, $ticketId), $urlKey);

        $auth = $this->getAuth($authKey);

        $response = $this->makeRequestAndGetFreshdeskResponse(self::HTTP_POST, $url, $auth, $input);

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
    public function postTicketReply(string $ticketId, array $input, $urlKey = 'urlind') : array
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
    protected function getUrl($route, $urlKey = 'urlind') : string
    {
        if ($this->isSandbox === true)
        {
            return trim($this->config['sandbox_url']) . '/' . $route;
        }

        return trim($this->config[$urlKey]) . '/' . $route;
    }

    private function getRequest(string $method, string $url, string $auth, array $content) : array
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

        return $request;
    }

    protected function getFdInstanceFromUrl(string $url) : string
    {
        if($this->isSandbox === true)
        {
            return 'rzpsandbox';
        }

        foreach (Constants::FRESHDESK_URL_LIST as $instance)
        {
            if(preg_match('#' .$this->config[$instance].'#i', $url) === 1)
            {
                return Constants::URL_VS_INSTANCES[$instance];
            }
        }

        return 'other';
    }

    protected function getResponse($request)
    {
        $fdInstance = $this->getFdInstanceFromUrl($request['url']);

        $this->trace->info(TraceCode::FRESHDESK_SUPPORT_TICKETS_INSTANCE,
            [ '$fdInstance', $fdInstance]
        );
        $startTimeMs = round(microtime(true) * 1000);

        $response = Requests::request(
            $request['url'],
            $request['headers'],
            $request['content'],
            $request['method'],
            $request['options']
        );

        $endTimeMs = round(microtime(true) * 1000);

        $execution_time = ($endTimeMs - $startTimeMs);

        $dimensions = [
            Constants::ROUTE       => $this->route->getCurrentRouteName(),
            Constants::FD_INSTANCE => $fdInstance
        ];

        $this->trace->count(Metric::FRESHDESK, $this->getDimension($this->route->getCurrentRouteName(), $response->status_code ?? 520) );

        $this->trace->histogram(Metric::FRESHDESK_RESPONSE_TIME, $execution_time, $dimensions);

        return $response;
    }

    protected function makeRequestAndGetStatus(string $method, string $url, string $auth, array $content)
    {
        $request = $this->getRequest($method, $url, $auth, $content);

        $trace_request = $this->getRedactedRequest($request);

        $this->trace->info(TraceCode::SUPPORT_TICKET_STATUS_REQUEST,
            [
                'request' => $trace_request
            ]
        );

        $response = $this->getResponse($request);

        $response = json_decode($response->body, true);

        $this->trace->info(TraceCode::SUPPORT_TICKET_STATUS_RESPONSE,
            [
                'response status' => $response['status']
            ]
        );

        return $response;
    }

    protected function getCurlData($content, $mime_boundary)
    {
        $eol = "\r\n";

        $data = '';

        if (isset($content['attachments[]']) === true)
        {
            foreach ($content['attachments[]'] as $attachment)
            {
                $data .= "--" . $mime_boundary . $eol;
                $data .= 'Content-Disposition: form-data; name="attachments[]"; filename="' . $attachment->getClientOriginalName() . '"' . $eol;
                $data .= 'Content-Transfer-Encoding: binary'.$eol.$eol;
                $data .= file_get_contents($attachment) . $eol;
            }

            unset($content['attachments[]']);
        }

        $arrayKeys = ['cc_emails[]', 'tags'];

        foreach ($arrayKeys as $arrayKey)
        {
            if (isset($content[$arrayKey]) === true)
            {
                $curlKey = $arrayKey;

                if (is_array($content[$arrayKey]) and ends_with($arrayKey, '[]') === false)
                {
                   $curlKey .= '[]';
                }

                foreach ($content[$arrayKey] as $email)
                {
                    $data .= "--" . $mime_boundary . $eol;
                    $data .= 'Content-Disposition: form-data; name="' . $curlKey . '"' . $eol . $eol;
                    $data .= $email . $eol;
                }

                unset($content[$arrayKey]);
            }
        }

        self::httpBuildQuery($content);

        foreach ($content as $key => $value)
        {
            $data .= '--' . $mime_boundary . $eol;
            $data .= 'Content-Disposition: form-data; name="' . $key . '"' . $eol . $eol;
            $data .= $value . $eol;
        }

        $data .= '--' . $mime_boundary . '--';

        return $data;
    }

    protected static function httpBuildQuery(array &$data, $key = '', $value = null)
    {
        foreach ($value ?? $data as $k => $v)
        {
            $cur_key = $key ? "{$key}[{$k}]" : "{$k}";

            if (is_array($v))
            {
                self::httpBuildQuery($data, "{$cur_key}", $v);

                unset($data[$k]);
            }
            else
            {
                $data[$cur_key] = $v;
            }
        }
    }

    protected function makeCurlRequest(array &$request)
    {
        $mime_boundary = md5(time()); // nosemgrep : php.lang.security.weak-crypto.weak-crypto

        $curl = curl_init();

        $headers = array (
            "authorization: " . $request['headers']['Authorization'],
            "content-type: " . 'multipart/form-data; boundary=' . $mime_boundary
        );

        $content = $this->getCurlData($request['content'], $mime_boundary);

        curl_setopt_array($curl, array(
            CURLOPT_URL => $request['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $content,
            CURLOPT_HTTPHEADER => $headers,
        ));

        $response = curl_exec($curl);

        $curlInfo = curl_getinfo($curl);

        $this->trace->count(Metric::FRESHDESK, $this->getDimension($this->route->getCurrentRouteName(), $curlInfo['http_code'] ?? 520));

        curl_close($curl);

        return [$response, $curlInfo['http_code']];
    }

    protected function makeRequestAndGetFreshdeskResponse(string $method, string $url, string $auth, array $content)
    {
        $contentType = 'application/json';

        $this->processArrayRequestFields($content);

        if ((isset($content['attachments[]']) === true) and (is_null($content['attachments[]']) === false))
        {
            $contentType = 'multipart/form-data';
        }
        else
        {
            unset($content['attachments[]']);

            if (empty($content) === false)
            {
                $content = json_encode($content);
            }
        }

        $request = [
            'options'            => [
                'timeout'        => 180,
            ],
            'content'            => $content,
            'method'             => $method,
            'headers'            => [
                'Content-Type'   => $contentType,
                'Authorization'  => $auth
            ],
            'url'                => $url,
        ];

        $trace_request = $this->getRedactedRequest($request);

        $this->trace->info(TraceCode::FRESHDESK_SUPPORT_TICKETS_REQUEST,
            $trace_request
        );

        $statusCode = null;
        if ($contentType === 'multipart/form-data')
        {
            list($responseBody, $statusCode) = $this->makeCurlRequest($request);
        }
        else
        {
            list($responseBody, $statusCode) = $this->getResponseAndStatusCode($request);
        }

        $responseBody = json_decode($responseBody, true);

        $this->trace->info(TraceCode::FRESHDESK_SUPPORT_TICKETS_RESPONSE,
                           [
                               'response'       => $responseBody['total'] ?? (isset($responseBody) ? count($responseBody) : 0),
                               'response_code'  =>  $statusCode
                           ]
        );

        if (isset($responseBody['errors']) === true)
        {
            $this->trace->info(TraceCode::FRESHDESK_SUPPORT_TICKETS_ERROR_RESPONSE,
                               [
                                   'response' => $responseBody
                               ]
            );


        }

        return $responseBody;
    }

    private function processArrayRequestFields(array &$content)
    {
        if (isset($content['attachments']) === false)
        {
            return;

        }

        $content['attachments[]'] = $content['attachments'];

        unset($content['attachments']);

        if (isset($content['cc_emails']) === true)
        {
            $content['cc_emails[]'] = $content['cc_emails'];

            unset($content['cc_emails']);
        }

    }

    private function getAuthKey($urlKey) : string
    {
        return self::URL_TOKEN_MAP[$urlKey] ?? 'token';
    }

    private function getRedactedRequest(array $request) : array
    {
        foreach (self::REQUEST_REDACT_FIELDS as $field)
        {
            unset($request[$field]);
        }

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

    protected function getDimension($route, $responseCode){

        return [
            Constants::ROUTE            =>  $route,
            Constants::RESPONSE_CODE    =>  $responseCode,
            ];
    }

    protected function getResponseAndStatusCode(array $request): array
    {
        $response = $this->getResponse($request);

        $responseBody = $response->body;

        $statusCode = $response->status_code;

        return array($responseBody, $statusCode);
    }

}
