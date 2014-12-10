<?php

namespace Gateway\MockAtom;

use Carbon\Carbon;
use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\BaseGateway;
use Gateway\Atom;
use Models\Card;
use Requests_Response;

class Gateway extends Atom\Gateway
{
    protected $url = 'http://203.114.240.183/paynetz/epi/fts';

    public function __construct()
    {
        parent::__construct();

        $this->mock = true;
    }

    public function capture(array $input)
    {
        $data = parent::capture($input);

        $url = $data['redirectUrl'];

        $parts = parse_url($url);

        $newRedirectUrl = $this->getNetBankingAtomMockUrl($parts['query']);

        $data['redirectUrl'] = $newRedirectUrl;

        return $data;
    }

    protected function getNetBankingAtomMockUrl($query)
    {
        $url = \URL::route('mockatom_choose_bank', $query, false);
        $mockGatewaysConfig = \Config::get('applications.mock_gateways');
        $secret = $mockGatewaysConfig['secret'];

        $scheme = \Request::getScheme().'://';
        $host = \Request::getHost();

        $key = 'rzp_test';

        $redirectUrl = $scheme . $key . ':' . $secret . '@' . $host . $url;

        return $redirectUrl;
    }

    protected function sendGatewayRequest($request)
    {
        $request['url'] = $this->makeMockRequestUrl($request['url']);

        $serverResponse = $this->callGatewayRequestFunctionInternally($request);

        return $this->prepareInternalResponse($serverResponse);
    }

    protected function prepareInternalResponse($serverResponse)
    {
        $response = new Requests_Response();

        $response->headers = $serverResponse->headers->all();

        foreach ($response->headers as $key => &$value)
        {
            $value = implode(';', $value);
        }

        $response->body = $serverResponse->getContent();
        $response->status_code = $serverResponse->getStatusCode();
        $response->success = true;
        // @todo: add url to response var

        return $response;
    }

    protected function makeMockRequestUrl($url)
    {
        $this->request = \Request::getFacadeRoot();
        $relativeUrl = 'gateway/mockatom';
        $scheme = $this->request->getScheme().'://';
        $host = $this->request->getHost();
        $key = 'rzp_test';
        $secret = 'DASHBOARD_AUTH_PASS';

        $newUrl = $scheme . $key . ':' . $secret. '@' . $host . '/v1/' . $url;

        $parts = parse_url($url);

        if (isset($parts['query']))
            $newUrl .= '?'.$parts['query'];

        return $newUrl;
    }

    protected function callGatewayRequestFunctionInternally($requestVar)
    {
        $server = new Server();
        $server->setInput($requestVar['content']);

        return $server->initiateNetBankingTransaction($requestVar['content']);
    }
}
