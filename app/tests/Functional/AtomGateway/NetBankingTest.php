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

    /**
     * Whether atom gateway is mocked or not
     * @var boolean
     */
    protected $mock;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/netbanking.php';

        parent::setUp();

        $this->fixtures->create('terminal:atom_terminal');

        $gateway = $this->app['config']->get('gateway');
        $this->mock = $gateway['mock_atom'];

        $this->payment = array(
            'method' => 'netbanking',
            'bank' => 'SBIN',
            'amount' => '5000',
            'email' => 'ab@g.com',
            'contact' => '9431495816',
            'currency' => 'INR');
    }

    public function testNetBankingPaymentAuthorize()
    {
        $this->ba->publicAuth();

        $content = $this->startTest();

        $this->assertArrayHasKey('razorpay_payment_id', $content);
    }

    public function testNetBankingPaymentCapture()
    {
        $content = $this->doAtomPaymentAuthorize();

        $id = $content['razorpay_payment_id'];

        $this->ba->privateAuth();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payments/'.$id.'/capture';

        $this->runRequestResponseFlow($testData);
    }

    public function testNetBankingPaymentRefund()
    {
        $this->ba->privateAuth();

        $payment = $this->fixtures->create('payment:netbanking_captured');
        $id = $payment->getPublicId();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payments/'.$id.'/refund';

        $refund = $this->runRequestResponseFlow($testData);
    }

    public function testNBPaymentFailureAtBank()
    {
        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testCardPayment()
    {
        $this->markTestSkipped();
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $payment = &$this->payment;

        unset($this->payment['bank']);
        $cardData = [
            'number' => '4111111111111111',
            'cvv' => '500',
            'expiry_month' => '05',
            'expiry_year' => '20', 'name' => 'shk'];

        $payment['card'] = $cardData;
        $payment['method'] = 'card';

        $content = $this->doAtomPaymentAuthorize();

        $id = $content['razorpay_payment_id'];

        $this->ba->privateAuth();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payments/'.$id.'/capture';

        $this->runRequestResponseFlow($testData);
    }

    public function testMockOnLiveMode()
    {
        $this->app['config']->set('gateway.mock_atom', true);

        $this->ba->publicAuth('rzp_live_TheLiveAuthKey');

        $this->fixtures
            ->on('live')
            ->create('terminal:atom_terminal');

        $this->startTest();
    }

    public function startTest()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $testData = $this->testData[$func];

        $this->replaceValuesRecursively($this->payment, $testData['request']['content']);

        $testData['request']['content'] = $this->payment;

        $this->currentTestData = $testData;

        return $this->runRequestResponseFlow($testData);
    }

    protected function doAtomPaymentAuthorize()
    {
        $request = array(
            'content' => $this->payment);

        $this->ba->publicAuth();

        return $this->makeRequestAndGetContent($request);
    }

    /**
     * Runs payment callback flow for atom net-banking transactions
     * @param  array $response
     */
    protected function runPaymentCallbackFlow($response)
    {
        $content = $response->getContent();

        if ((json_decode($content) !== null) or
            (get_class($response) !== 'Illuminate\Http\RedirectResponse') or
            ($response->getStatusCode() !== 302))
        {
            return $response;
        }

        $url = $response->getTargetUrl();

        $headers = array();

        $mock = $this->mock;

        if ($mock)
        {
            $this->ba->publicAuth();

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
            //
            // Txn stage 1
            //
            $response = Requests::get($url);

            //
            // Atom cookie. Provide it in every subsequent request
            //
            $cookie = $response->cookies['JSESSIONID']->value;
            $headers = array(
                'Cookie' => 'JSESSIONID=' . $cookie,
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.93 Safari/537.36');

            $statusCode = $response->status_code;
        }

        $this->assertEquals('200', $statusCode, 'Request failed with status code: ' . $statusCode);

        $atomBaseUrl = 'http://203.114.240.183:80';

        $content = array('bankID' => '2001');

        if ($mock)
        {
            //
            // Now, we are going to submit the data to bank
            // Which in this case is Razorpay bank
            //
            list($url, $method, $values) = $this->getFormDataFromResponse($response->getContent(), $url);

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
            //
            // Atom fetches bank list and then auto-submits the form.
            //
            $url = $atomBaseUrl.'/paynetz/banklist.action';

            $response = Requests::get($url, $headers);
            list($url, $method, $values) = $this->getFormDataFromResponse($response->body, $url);

            //
            // Making follow_redirects false is important because we have to provide the cookie for
            // the redirect. It was returning error otherwise
            //
            $response = Requests::$method($url, $headers, $values, ['follow_redirects' => false]);

            $url = $response->headers['location'];

            $response = Requests::get($url, $headers);
            // Somehow, the response body is not being echoed by 's' function so don't try it! Weirds me out.

            list($url, $method, $values) = $this->getFormDataFromResponse($response->body, $response->url);

            $response = Requests::$method($url, $headers, $values);
            $content = $response->body;
        }

        // Be careful of different quotes(',") or lack of it! Weird!
        $itc = getTextBetweenStrings($content, 'ITC = ', ';');
        $bid = getTextBetweenStrings($content, "BID = '", "';");
        $amt = getTextBetweenStrings($content, "amt = '", "';");
        $cc  = getTextBetweenStrings($content, 'clientCode = "', '";');

        //
        // Decide whether to make the transaction succeed or fail
        //

        $status = 'Ok';

        if ((isset($this->currentTestData['success'])) and
            ($this->currentTestData['success'] === false))
        {
            $status = 'F';
        }

        $url = ($mock) ? '/gateway/mockanb/rzp_bank/submit' : $atomBaseUrl . '/paynetz/atom';
        $url .= '?' . 'ITC='.$itc . '&BID='.$bid.'&clientCode='.$cc.'&amt='.$amt.'&Status='.$status;

        $values = array('success' => $status);

        // Finally, we are on the bank page and now need to submit the bank
        // page with the decision true or false as decided above.

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

        //
        // This is the final submission. Basically, atom returns a bunch of data
        // like mmp_txn etc, which we now submit to the rzp return url
        // provided earlier.
        //
        // The url to submit to is the action field of the form in this case
        //

        $response = $this->submitPaymentCallbackForm($form);

        return $response;
    }
}