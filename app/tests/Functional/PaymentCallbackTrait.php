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

        $id = $this->getIdFromUri($uri);

        $auth = $this->auth;
        unset($auth['PHP_AUTH_PW']);

        $request['method'] = 'POST';
        $request['content'] = $form->getValues();
        $request['server'] = $auth;
        $request['url'] = '/payments/'.$id.'/callback';

        $response = $this->makeRequestParent($request);

        $content = $response->getContent();

        $content = $this->getPaymentJsonFromCallback($content);

        $response->setContent($content);

        return $response;
    }

    protected function makeRequest($request)
    {
        $this->checkAndSetUrl($request);

        $this->checkAndSetMethod($request);

        $response = $this->makeRequestParent($request);

        $response = $this->runPaymentCallbackFlow($response);

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

    protected function getDataFromForm($form)
    {
        $uri = $form->getUri();

        $method = $form->getMethod();
        $values = $form->getValues();

        return array($uri, $method, $values);
    }
}