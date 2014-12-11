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
        $mockGatewaysConfig = \Config::get('applications.mock_gateways');
        $secret = $mockGatewaysConfig['secret'];

        $url = \Http\Route::getUrl('mockatom_choose_bank', $query, 'rzp_test', $secret);

        return $url;
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
        $key = 'rzp_test';

        $mockGatewaysConfig = \Config::get('applications.mock_gateways');
        $secret = $mockGatewaysConfig['secret'];

        $mockUrl = \Http\Route::getUrl('mockatom_init_netbanking', array(), 'rzp_test', $secret);

        $parts = parse_url($url);

        if (isset($parts['query']))
        {
            $mockUrl .= '?' . $parts['query'];
        }

        return $mockUrl;
    }

    protected function callGatewayRequestFunctionInternally($requestVar)
    {
        $server = new Server();
        $server->setInput($requestVar['content']);

        return $server->initiateNetBankingTransaction($requestVar['content']);
    }
}
