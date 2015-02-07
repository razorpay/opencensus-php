<?php

namespace Tests\Functional\Helpers\Payment;

use Config;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Functional\TestCase;

trait PaymentAtomTrait
{
    /**
     * Runs payment callback flow for atom net-banking transactions
     * @param  array $response
     */
    protected function runPaymentCallbackFlowAtom($response)
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

        $atomBaseUrl = 'http://203.114.240.183:80';

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

            $this->assertEquals('200', $statusCode, 'Request failed with status code: ' . $statusCode);

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
            // @note: The Requests library follows through the redirects which reduces steps for us.

            //
            // Txn stage 1
            // Submits to api. Which gives 302 redirect and gets redirected.
            // Which gives a form with bank id and other weird fields
            //
            // Note that Requests library follows the redirect to banklist and fetches the form
            // so we can skip that step.
            //
            list($url, $method, $values, $response) = $this->makeRequestAndGetFormData($url, 'GET');

            //
            // Atom cookie. Provide it in every subsequent request
            //
            $cookie = $response->cookies['JSESSIONID']->value;
            $headers = array('Cookie' => 'JSESSIONID=' . $cookie);

            //
            // Once we submit the bank id to url, it gets redirected to txnStage2 url,
            // which has another form that we got to submit.
            //

            // This is submitted at the txnStage 2 url from data received from fetching bank list url
            list($url, $method, $values) = $this->makeRequestAndGetFormData($url, $method, $headers, $values);

            // This is submitted at the .jsp url from data received from txnStage 2 url.
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