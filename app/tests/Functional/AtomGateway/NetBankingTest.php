<?php

namespace Tests\Functional\AtomGateway;

use Carbon\Carbon;
use Config;
use Mockery;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\Functional\Payment\PaymentAuthFlowTrait;
use Tests\Functional\PaymentCallbackTrait;
use Tests\Functional\TestCase;

class NetBankingTest extends TestCase
{
    use PaymentCallbackTrait;

    public function setUp()
    {
        parent::setUp();

        $this->fixtures->createTerminalEntityForAtomGateway();
    }

    public function testNetBankingTransaction()
    {
        $this->setupPublicBasicAuthParams();

        $request = array(
            'content' => array(
                'method' => 'net banking',
                'bank' => 'SBIN',
                'amount' => '5000',
                'email' => 'ab@g.com',
                'contact' => '9431495816',
                'currency' => 'INR')
            );

        $payment = $this->makeRequestAndGetContent($request);

        $this->assertEquals('authorized', $payment['status']);
    }

    /**
     * Runs payment callback flow for atom net-banking transactions
     * @param  array $response
     */
    protected function runPaymentCallbackFlow($response)
    {
        $url = $response->getTargetUrl();

        $headers = array();

        $gateway = $this->app['config']->get('gateway');
        $mock = $gateway['mock_atom'];

        if ($mock)
        {
            $this->ba->appAuth();

            // Extract the uri part after 'v1'.
            // This removes the basic auth user/pwd from absolute url
            // which would otherwise interfere with later requests.
            // @note: In laravel tests, later requests will take up the basic auth
            //        parameters of previous requests if the basic auth params were
            //        supplied via absolute url and in the process ignore the ones
            //        provided via $server. Weird gotcha!
            $ix = strpos($url, '/v1/');
            $uri = substr($url, $ix + 3);

            $request = array('method' => 'GET', 'url' => $uri);
            $response = $this->makeRequestParent($request);
            $statusCode = $response->getStatusCode();
        }
        else
        {
            $response = Requests::get($url);
            $cookie = $response->cookies['JSESSIONID']->value;
            $headers = array('Cookie' => 'JSESSIONID=' . $cookie);
            $statusCode = $response->status_code;
        }

        $this->assertEquals('200', $statusCode, 'Request failed with status code: ' . $statusCode);

        $atomBaseUrl = 'http://203.114.240.183:80';

        // Atom fetches bank list and then auto-submits the form.
        // Completely unnecessary step! We skip it during testing
        // $response = \Requests::post($atomBaseUrl . '/paynetz/banklist.action', $headers);

        $content = array('bankID' => '2001');
        if ($mock)
        {
            $crawler = new Crawler($response->getContent(), $url);
            $form = $crawler->filter('form')->form();
            list($url, $method, $values) = $this->getDataFromForm($form);

            // See above note.
            $ix = strpos($url, '/v1/');
            $uri = substr($url, $ix + 3);

            $request = array(
                'method' => $method,
                'url' => $uri,
                'content' => $values);

            $response = $this->makeRequestParent($request);
            $content = $response->getContent();
        }
        else
        {
            $url = $atomBaseUrl . '/paynetz/redirect.action';
            $response = Requests::post($url, $headers, $content);
        }

        if ($mock === false)
        {
            $crawler = new Crawler($response->body, $url);
            $form = $crawler->filter('form')->form();

            list($url, $method, $values) = $this->getDataFromForm($form);

            $response = Requests::$method($url, $headers, $values);
            $content = $response->body;
        }

        $itc = getTextBetweenStrings($content, 'ITC = ', ';');
        $bid = getTextBetweenStrings($content, "BID = '", "';");
        $amt = getTextBetweenStrings($content, "amt = '", "';");
        $cc  = getTextBetweenStrings($content, 'clientCode = "', '";');

        $status = 'S';

        $url = ($mock) ? '/gateway/mockatom/rzp_bank/submit' : $atomBaseUrl . '/paynetz/atom';
        $url .= '?' . 'ITC='.$itc . '&BID='.$bid.'&clientCode='.$cc.'&amt='.$amt.'&Status='.$status;

        $values = array('success' => $status);

        if ($mock)
        {
            // For testing case, we add back tempTxnId because we don't maintian it
            // in session
            $tempTxnId = getTextBetweenStrings($content, 'tempTxnId = "', '";');
            $url .= '&tempTxnId='.$tempTxnId;

            $request = array(
                'method' => 'POST',
                'url' => $url,
                'content' => $values);

            $response = $this->makeRequestParent($request);
            $content = $response->getContent();
        }
        else
        {
            $response = Requests::post($url, $headers, $content);
            $content = $response->body;
        }

        $crawler = new Crawler($content, 'http://ab.com');
        $form = $crawler->filter('form')->form();

        $response = $this->submitPaymentCallbackForm($form);

        return $response;
    }
}