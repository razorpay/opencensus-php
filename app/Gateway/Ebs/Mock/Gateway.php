<?php

namespace RZP\Gateway\Ebs\Mock;

use Requests_Cookie;
use Requests_Cookie_Jar;
use \WpOrg\Requests\Response;
use \WpOrg\Requests\Response\Headers;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Ebs;
use RZP\Gateway\Ebs\BankCodes;
use RZP\Models\Bank\IFSC;

class Gateway extends Ebs\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }

    protected function putMockPaymentGatewayUrl(array & $request, $route)
    {
        $gateway = $this->gateway;

        if (is_null($route) === true)
        {
            $route = 'mock_' . $gateway . '_payment';
        }

        $url = $this->route->getUrl($route);

        if ($request['method'] === 'get')
        {
            // The key thing now is to replace the url from gateway to our mock one!
            $parts = parse_url($request['url']);

            $url = $url . '&' .$parts['query'];

            $request['url'] = $url;
        }

        $request['url'] = $url;
    }

    public function sendFirstGatewayRequestForEbsAuthorize($request)
    {
        $this->content = $request['content'];

        $cookie = [
            'PGSID' => 'value1',
            'PIDs'  => 'value2',
            'sid'   => 'value3',
        ];

        $header = $this->getHeader();

        // For Central Bank of India Fail First Gateway Request
        if ($this->content['payment_option'] === BankCodes::getMappedCode(IFSC::CBIN))
        {
            $header = [];
        }

        $response = $this->createResponse('302', false);

        $response = $this->setCookie($response, $cookie);
        $response = $this->setBody($response, '');
        $response = $this->setHeader($response, $header);

        return $response;
    }

    public function sendSecondGatewayRequestForEbsAuthorize($request)
    {
        $header = $this->getHeader();

        $response = $this->createResponse();

        $response = $this->setBody($response, $this->getText());
        $response = $this->setHeader($response, $header);

        $response = $this->setBody($response, $this->getText($this->content));

        // For Canara Bank Fail Second Gateway Request
        if ($this->content['payment_option'] === BankCodes::getMappedCode(IFSC::CNRB))
        {
            $response = $this->setBody($response, '');
        }

        return $response;
    }

    public function sendThirdGatewayRequestForEbsAuthorize($request)
    {
        // For Union Bank of India, and Kotak and some banks
        // Redirection is done via 302 Page
        // For YES Bank and other banks
        // Redirection is done uisng Form post
        if ($this->content['payment_option'] === BankCodes::getMappedCode(IFSC::UBIN))
        {
            $header = $this->getHeader();

            $response = $this->createResponse('302', false);

            $response = $this->setHeader($response, $header);
        }
        else
        {
            $response = $this->createResponse();
        }

        $response = $this->setBody($response, $this->getText($this->content));

        // For Corporation Bank Fail Third Gatteway Request,
        if ($this->content['payment_option'] === BankCodes::getMappedCode(IFSC::JAKA))
        {
            $response = $this->setBody($response, '');
        }

        return $response;
    }

    protected function getHeader()
    {
        return [
            'location' => $this->app['config']->get('app.url')
        ];
    }

    protected function setHeader($response, $headerValue)
    {
        $header = new \WpOrg\Requests\Response\Headers();

        foreach ($headerValue as $key => $value)
        {
            $header->offsetSet($key, $value);
        }

        $response->headers = $header;

        return $response;
    }

    protected function setCookie($response, $cookieValue)
    {
        $cookie = [];

        foreach ($cookieValue as $key => $value)
        {
            $cookie[] = new Requests_Cookie($key, $value);
        }

        $cookies = new Requests_Cookie_Jar($cookie);

        $response->cookies = $cookies;

        return $response;
    }

    protected function setBody($response, $body)
    {
        $response->body = $body;

        return $response;
    }

    protected function createResponse($statusCode = 200, $success = true)
    {
        $response = new \WpOrg\Requests\Response();

        $response->status_code = $statusCode;
        $response->success = $success;

        return $response;
    }

    protected function getText($content = [])
    {
        $appUrl = $this->app['config']->get('app.url');

        $txt = '<form method="POST" name="payment" action = "'.$appUrl.'">';

        foreach ($content as $key => $value)
        {
            $txt .= '<input type="hidden" name="' . $key. '" value="' . $value . '">';
        }

        $txt .= '</form>';

        return $txt;
    }

    /*
     * Mocking this Function as mocked server need the requets to be POST,
    */
    public function getRequest($location, $method, $body)
    {
        $request = [
            'url' => $location,
            'method' => 'post',
            'content' => $this->content,
        ];

        return $request;
    }
}
