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
    protected function runPaymentCallbackFlowAtom($response, &$callback = null)
    {
        //
        // Figure out whether to runn atom payment flow or not
        //

        list($runAtomFlow, $redirectUrl) = $this->isAtomFlowRequired($response, $callback);

        if ($runAtomFlow === false)
        {
            return $response;
        }

        $content = $response->getContent();
        $callback = null;

        $headers = array();

        $gateway = $this->app['config']->get('gateway');
        $mock = $gateway['mock_atom'];

        $atomBaseUrl = 'http://203.114.240.183:80';

        if ($mock)
        {
            $server = array('HTTP_REFERER' => 'http://localhost');

            $request = array('method' => 'GET', 'url' => $redirectUrl, 'server' => $server);
            $response = $this->makeRequestParent($request);
            $statusCode = $response->getStatusCode();

            $this->assertEquals('200', $statusCode, 'Request failed with status code: ' . $statusCode);

            //
            // Now, we are going to submit the data to bank
            // Which in this case is Razorpay bank
            //
            list($url, $method, $values) = $this->getFormDataFromResponse($response->getContent(), $redirectUrl);

            // See above note.
            $ix = strpos($url, '/v1/');
            $uri = substr($url, $ix + 3);

            $request = array(
                'method' => $method,
                'url' => $uri,
                'content' => $values,
                'server' => $server);

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
            list($url, $method, $values, $response) = $this->makeRequestAndGetFormData($redirectUrl, 'GET');

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
            list($url, $method, $values, $response) = $this->makeRequestAndGetFormData($url, $method, $headers, $values);

            if (isset($response->cookies['JSESSIONID']))
            {
                $cookie = $response->cookies['JSESSIONID']->value;
                $headers = array('Cookie' => 'JSESSIONID=' . $cookie);
            }

            // This is submitted at the .jsp url from data received from txnStage 2 url.
            $response = Requests::$method($url, $headers, $values);
            $content = $response->body;
        }

        list($url, $method, $values) = $this->getFormDataFromResponse($content, $url);

        if ($url === 'https://paynetzuat.atomtech.in/CitiWeb/cityBilling.jsp')
        {
            $values['CititoMall'] .= 'Y:'.'|323232|123123|';
            $values['submit'] = 'Simulate Transaction';

            list($url, $method, $values) = $this->makeRequestAndGetFormData($url, $method, $headers, $values);

            $response = Requests::$method($url, $headers, $values);

            $content = $response->body;
        }
        else
        {
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

            $url = ($mock) ? '/gateway/mockanb/payment/submit' : $atomBaseUrl . '/paynetz/atom';
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

    protected function isAtomFlowRequired($response, $callback = null)
    {
        $content = $response->getContent();

        //
        // if it's jsonp, then $response will be of type JsonResponse
        // otherwise of RedirectResponse
        //

        if (($callback !== null) and
            (get_class($response) === 'Illuminate\Http\JsonResponse'))
        {
            $content = $this->getJsonContentFromResponse($response, $callback);

            if (isset($content['redirectUrl']))
            {
                return array(true, $content['redirectUrl']);
            }
        }

        if ((json_decode($content) !== null) or
            (get_class($response) !== 'Illuminate\Http\RedirectResponse') or
            ($response->getStatusCode() !== 302))
        {
            return array(false, null);
        }

        return array(true, $response->getTargetUrl());
    }
}