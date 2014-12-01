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

    protected function runPaymentCallbackFlow($response)
    {
        $content = $response->getContent();

        $url = getTextBetweenStrings($content, 'url=', '"');
        $url = str_replace('&amp;', '&', $url);

        $response = Requests::get($url);
        $cookie = $response->cookies['JSESSIONID']->value;

        $atomBaseUrl = 'http://203.114.240.183:80';
        $headers = array('Cookie' => 'JSESSIONID=' . $cookie);

        // Atom fetches bank list and then auto-submits the form.
        // Completely unnecessary step! Even we skip it during testing
        // $response = \Requests::post($atomBaseUrl . '/paynetz/banklist.action', $headers);

        $content = array('bankID' => '2001');
        $url = $atomBaseUrl . '/paynetz/redirect.action';
        $response = Requests::post($url, $headers, $content);

        $crawler = new Crawler($response->body, $url);
        $form = $crawler->filter('form')->form();

        list($url, $method, $values) = $this->getDataFromForm($form);

        $response = Requests::$method($url, $headers, $values);
        $content = $response->body;

        $itc = getTextBetweenStrings($content, 'ITC = ', ';');
        $bid = getTextBetweenStrings($content, "BID = '", "';");
        $amt = getTextBetweenStrings($content, "amt = '", "';");
        $cc  = getTextBetweenStrings($content, 'clientCode = "', '";');

        $status = 'S';
        $url = $atomBaseUrl . '/paynetz/atom?' . 'ITC='.$itc . '&BID='.$bid.'&clientCode='.$cc.'&amt='.$amt.'&Status='.$status;
        $content = array('success' => $status);
        $response = Requests::post($url, $headers, $content);

        $crawler = new Crawler($response->body, $url);
        $form = $crawler->filter('form')->form();

        $response = $this->submitPaymentCallbackForm($form);

        return $response;
    }
}