<?php

namespace Tests\Functional\Helpers\Payment;

use Requests;
use Symfony\Component\DomCrawler\Crawler;

trait PaymentHdfcTrait
{
    protected function runPaymentCallbackFlowHdfc($response, &$callback = null)
    {
        $tds = $this->is3dSecure($response, $callback);

        $content = $response->getContent();

        if ($callback and $tds)
        {
            $content = $this->getJsonContentFromResponse($response, $callback);
            $callback = null;
            $content = $this->createHtmlFormAfterJsonpRequest($content);
        }

        if ($tds)
        {
            //
            // Card has 3d-secure enabled
            // In which case, run card 3dsecure flow
            //

            $uri = $this->client->getRequest()->getUri();

            $response = $this->runDebitCardAuthFlow($content, $uri);
        }

        return $response;
    }

    protected function is3dSecure($response, $callback = null)
    {
        $content = $response->getContent();

        if ($callback === null)
        {
            $tds = ((json_decode($content) === null) and
                    (get_class($response) === 'Illuminate\Http\Response') and
                    ($response->headers->get('content-type') === 'text/html; charset=UTF-8') and
                    ($response->getStatusCode() === 200));
        }
        else
        {
            $tds = ((json_decode($content) === null) and
                    (get_class($response) === 'Illuminate\Http\JsonResponse') and
                    ($response->headers->get('content-type') === 'text/javascript; charset=UTF-8') and
                    ($response->getStatusCode() === 200));

            if ($tds)
            {
                $content = $this->getJsonContentFromResponse($response, $callback);

                $tds = ((isset($content['http_status_code'])) and
                        ($content['http_status_code'] === 200) and
                        (isset($content['data'])));
            }
        }

        return $tds;
    }

    protected function createHtmlFormAfterJsonpRequest($content)
    {
        $data = $content['data'];

        $text = '
            <!doctype html>
            <html lang="en">
                <body>
                <form name="form1" action="'.$data['url'].'" method="post">
                    <input type="text" name="PaReq" value="'.$data['PAReq'].'">
                    <br />
                    <input type="text" name="MD" value="'.$data['paymentid'].'">
                    <br />
                    <input type="text" name="TermUrl" value="'.$content['callbackUrl'].'">
                    <br />
                    <input type="submit" value="Submit" >
                </form>
                <br>
                Submit within 30 secs max!
                </body>
            </html>
            ';

        return $text;
    }

    protected function runDebitCardAuthFlow($content, $uri)
    {
        $crawler = new Crawler($content, $uri);

        $form = $this->dcPaymentSubmitToAcsUrl($crawler);

        return $this->submitPaymentCallbackForm($form);
    }

    protected function dcPaymentSubmitToAcsUrl($crawler)
    {
        //
        // get the form
        //
        try
        {
            $form = $crawler->selectButton('Submit')->form();
        }
        catch(Exception $e)
        {
            if (strpos($e->getMessage(), 'node list is empty') !== false)
            {
                $this->fail('Payment Timed out');
            }
            else
            {
                throw $e;
            }
        }

        //
        // second request
        // submit to acs url
        //

        list($uri, $method, $values) = $this->getDataFromForm($form);

        $gateway = $this->app['config']->get('gateway');

        if ($gateway['mock_hdfc'] === true)
        {
            $server = $this->ba->getCreds();

            $response = $this->call($method, $uri, $values, array(), $server);
            $content = $response->getContent();
        }
        else
        {
            try
            {
                $response = Requests::post($uri, array(), $values);
                $content = $response->body;
            }
            catch(\Requests_Exception $e)
            {
                echo '3d secure failed';
                throw $e;
            }
        }

        $form = $this->dcPaymentGetCallbackForm($content, $uri);

        return $form;
    }

    protected function dcPaymentGetCallbackForm($content, $uri)
    {
        //
        // crawl the repsonse to get callback form
        //

        $crawler = new Crawler($content, $uri);

        return $crawler->selectButton('Submit')->form();
    }

    protected function getDefaultHdfcEntityArray()
    {
        $hdfcPayment = array(
            'action'        =>  4,
            'enroll_result' =>  2,
            'status'        =>  'not_enrolled',
            'result'        =>  'APPROVED',
            'eci'           =>  '6',
            'auth'          =>  '999999',
            'ref'           =>  random_integer(12),
            'avr'           =>  'N',
            'postdate'      =>  (new Carbon('now', 'Asia/Kolkata'))->format('md'),
            'tranid'        =>  random_integer(15),
            'payid'         =>  -1,
            'amt'           =>  500);

        return $hdfcPayment;
    }
}
