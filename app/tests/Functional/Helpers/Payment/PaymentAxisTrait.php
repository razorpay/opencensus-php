<?php

namespace Tests\Functional\Helpers\Payment;

use Config;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Functional\TestCase;

trait PaymentAxisTrait
{
    /**
     * Runs payment callback flow for atom net-banking transactions
     * @param  array $response
     */
    protected function runPaymentCallbackFlowAxis($response, &$callback = null)
    {
        $content = $response->getContent();

        $headers = array();

        $gateway = $this->app['config']->get('gateway');

        $mock = $gateway['mock_axis_migs'];

        if ($callback)
        {
            $content = $this->getJsonContentFromResponse($response, $callback);
            $callback = null;

            $request = $content['request'];
            list($url, $method, $values) = [$request['url'], $request['method'], $request['content']];
        }
        else
        {
           list($url, $method, $values) = $this->getFormDataFromResponse($response->getContent(), 'https://localhost');
        }

        if ($mock)
        {
            $request = array(
               'url' => $url,
               'method' => $method,
               'content' => $values);

            $response = $this->makeRequestParent($request);

            $statusCode = $response->getStatusCode();
            $this->assertEquals($statusCode, '302');

            $url = $response->getTargetUrl();
        }
        else
        {
            $options = ['follow_redirects' => false];
            $response = Requests::$method($url, [], $values, $options);

            $url = $response->headers['location'];

            $cookiesArray = $this->collectCookiesInArray($response);
            $cookies = $this->mapCookiesArrayToString($cookiesArray);

            $headers = array('Cookie' => $cookies, 'Host' => 'migs.mastercard.com.au');
            $response = Requests::get($url, $headers, $options);

            $url = $response->headers['location'];

            $cookiesArray = array_merge($cookiesArray, $this->collectCookiesInArray($response));
            $cookies = $this->mapCookiesArrayToString($cookiesArray);

            $headers = array('Cookie' => $cookies, 'Host' => 'migs.mastercard.com.au');
            $response = Requests::get($url, $headers, $options);

            $url = $response->headers['location'];
            list($url, $method, $values) = $this->makeRequestAndGetFormData($url, 'get', $headers);

            $response = Requests::$method($url, $headers, $values);
            $crawler = new Crawler($response->body, $url);

            // Weirdly, this page has two forms and the second needs to be submitted
            $form = $crawler->filter('form')->siblings()->eq(1)->form();
            list($url, $method, $values) = $this->getDataFromForm($form);

            $response = Requests::$method($url, $headers, $values, $options);
            $url = $response->headers['location'];
        }

        return $this->submitPaymentCallbackRedirect($url);
    }

    protected function mapCookiesArrayToString($cookies)
    {
        $str = '';
        foreach ($cookies as $key => $value)
        {
            $str .= $key . '=' . $value . '; ';
        }

        return $str;
    }

    protected function collectCookiesInArray($response)
    {
        $cookiesArray = [];
        foreach ($response->cookies as $cookie)
        {
            $cookiesArray[$cookie->name] = $cookie->value;
        }

        return $cookiesArray;
    }
}