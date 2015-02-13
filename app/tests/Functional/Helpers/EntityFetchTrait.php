<?php

namespace Tests\Functional\Helpers;

use EE\Exception\BaseException;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Functional\RequestResponseFlowTrait;

trait EntityFetchTrait
{
    protected function getLastEntity($entity)
    {
        $this->ba->appAuth();

        $input = array('count' => 1);

        $content = $this->getEntities($entity, $input);

        return $content['items'][0];
    }

    protected function getEntities($entity, array $input = array())
    {
        $this->ba->appAuth();

        $request = array(
            'method' => 'GET',
            'url' => '/admin/'.$entity,
            'content' => $input);

        $content = $this->makeRequestAndGetContent($request);

        $this->assertSame('collection', $content['entity']);

        return $content;
    }

    protected function getLastTransaction()
    {
        return $this->getLastEntity('transaction');
    }
}
