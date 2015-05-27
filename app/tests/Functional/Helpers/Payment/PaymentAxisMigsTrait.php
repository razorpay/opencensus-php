<?php

namespace Tests\Functional\Helpers\Payment;

use Config;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Functional\TestCase;

trait PaymentAxisMigsTrait
{
    protected function runPaymentCallbackFlowAxisMigs($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $url = $this->makeFirstGatewayPaymentMockRequest($url, $method, $values);
        }
        else
        {
            $url = $this->runAxisMigsGatewayAutomation($url, $method, $values);
        }

        return $this->submitPaymentCallbackRedirect($url);
    }

    protected function runAxisMigsGatewayAutomation($url, $method, $values)
    {
            $options = ['follow_redirects' => false];
            $method = strtoupper($method);
            $response = Requests::request($url, [], $values, $method, $options);

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

            return $url;
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