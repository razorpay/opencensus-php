<?php

namespace Tests\Functional\Helpers;

use EE\Exception\BaseException;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Functional\RequestResponseFlowTrait;

trait EntityFetchTrait
{
    protected function getLastEntity($entity, $admin = false)
    {
        $this->ba->appAuth();

        $input = array('count' => 1);

        $content = $this->getEntities($entity, $input, $admin);

        return $content['items'][0];
    }

    protected function getEntities($entity, array $input = array(), $admin = false)
    {
        $this->ba->proxyAuth();

        $url = '/'.$entity.'s';

        if ($admin)
        {
            $this->ba->appAuth();

            $url = '/admin/'.$entity;
        }

        $request = array(
            'url' => $url,
            'method' => 'GET',
            'content' => $input);

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('entity', $content);
        $this->assertSame('collection', $content['entity']);

        return $content;
    }

    protected function getLastTransaction($admin = false)
    {
        return $this->getLastEntity('transaction', $admin);
    }
}
