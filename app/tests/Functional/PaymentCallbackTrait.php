<?php

namespace Tests\Functional;

use EE\Exception\BaseException;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Functional\RequestResponseFlowTrait;

trait PaymentCallbackTrait
{
    use RequestResponseFlowTrait
    {
        makeRequest as makeRequestParent;
    }

    protected function signPayment(array $payment, $secret = '')
    {
        $data = array(
            'amount'            => $payment['amount'],
            'currency'          => 'INR',
            'merchant_order_id' => $payment['notes']['merchant_order_id']);

        if ($secret === '')
        {
            $secret = $this->ba->getSecret();
        }

        $str = implode('|', $data);

        return hash_hmac('sha1', $str, $secret);
    }

    protected function assertSignatureMatches(array $content, $secret)
    {
        $this->assertArrayHasKey('signature', $content);

        $data = array(
            'amount' => $content['amount'],
            'currency' => $content['currency'],
            'merchant_order_id' => $content['merchant_order_id'],
            'razorpay_payment_id' => $content['razorpay_payment_id']);

        $str = implode('|', $data);

        return hash_hmac('sha1', $str, $secret);
    }

    protected function getPaymentJsonFromCallback($content)
    {
        $start = 'var data = ';
        $end = '// Callback data //';

        $data = getTextBetweenStrings($content, $start, $end);

        // Remove ';\n' at the end to get proper json string
        $l = strlen($data);
        $data = substr($data, 0, $l-2);

        return $data;
    }

    protected function submitPaymentCallbackForm($form)
    {
        //
        // third request
        // submit callback form
        //

        $uri = $form->getUri();

        // Extract the payment id from absolute url

        $id = $this->getIdFromUri($uri);

        $this->ba->publicAuth();

        $request['method'] = 'POST';
        $request['content'] = $form->getValues();

        $request['url'] = '/payments/'.$id.'/callback';

        $response = $this->makeRequestParent($request);

        $content = $response->getContent();

        $content = $this->getPaymentJsonFromCallback($content);

        $response->setContent($content);

        return $response;
    }

    protected function makeRequest($request, &$callback = null)
    {
        $this->checkAndSetUrl($request);

        $this->checkAndSetMethod($request);

        $response = $this->makeRequestParent($request);

        $response = $this->runPaymentCallbackFlow($response, $callback);

        return $response;
    }

    protected function getIdFromUri($uri)
    {
        // The url should be of format http://localhost/v1/payments/{id}/callback
        // We will simply extract the id from it.

        $id = getTextBetweenStrings($uri, '/payments/', '/callback');

        return $id;
    }

    protected function checkAndSetUrl(& $request)
    {
        if (isset($request['url']) === false)
        {
            $request['url'] = '/payments';
        }
    }

    protected function checkAndSetMethod(& $request)
    {
        if (isset($request['method']) === false)
        {
            $request['method'] = 'POST';
        }
    }

    protected function replaceDefualtValues(array & $content)
    {
        $data = $this->getDefaultPaymentArray();

        $this->replaceValuesRecursively($data, $content);

        $content = $data;
    }

    protected function getFormDataFromResponse($content, $url)
    {
        $crawler = new Crawler($content, $url);

        $form = $crawler->filter('form')->form();

        return $this->getDataFromForm($form);
    }

    protected function getDataFromForm($form)
    {
        $uri = $form->getUri();

        $method = $form->getMethod();
        $values = $form->getValues();

        return array($uri, $method, $values);
    }
}