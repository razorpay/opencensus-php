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

    public function authorize(array $input)
    {
        // Call atom gateway authorize
        $data = parent::authorize($input);

        // The key thing now is to replace redirectUrl from atom's to ours!
        $url = $data['redirectUrl'];

        $parts = parse_url($url);

        $newRedirectUrl = $this->getNetBankingAtomMockUrl($parts['query']);

        // Put the new redirect url back in!
        $data['redirectUrl'] = $newRedirectUrl;

        // Voila
        return $data;
    }

    protected function getNetBankingAtomMockUrl($query)
    {
        $key = \BasicAuth::getPublicKey();

        $url = \Http\Route::getUrl('mockatom_choose_bank', $query, $key);

        return $url;
    }

    protected function sendGatewayRequest($request)
    {
        // Although we reset the url, it's not being used currently.
        $request['url'] = $this->makeMockRequestUrl($request['url']);

        // When sending the first request to atom gateway,
        // quietly redirect it to mock atom  gateway internally!
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
