<?php

namespace RZP\Tests\Functional\Helpers;

use RZP\Exception\BaseException;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use RZP\Tests\Functional\RequestResponseFlowTrait;

trait EntityFetchTrait
{
    protected function getLastEntity($entity, $admin = false, $mode = 'test')
    {
        $input = array('count' => 1);

        $content = $this->getEntities($entity, $input, $admin, $mode);

        if ($content['count'])
            return $content['items'][0];

        return null;
    }

    protected function getEntities(string $entity, array $input = array(), $admin = false, $mode = 'test')
    {
        $proxyAuth = 'proxyAuth' . camel_case($mode);

        $this->ba->$proxyAuth();

        $url = '/'.$entity.'s';

        if ($admin)
        {
            $this->ba->adminAuth($mode);

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

    protected function getEntityById($entity, $id, $admin = false, $mode = 'test')
    {
        $proxyAuth = 'proxyAuth' . camel_case($mode);

        $this->ba->$proxyAuth();

        $url = '/'.$entity.'s/'.$id;

        if ($admin)
        {
            $this->ba->adminAuth($mode);

            $url = '/admin/'.$entity.'/'.$id;
        }

        $request = array(
            'url' => $url,
            'method' => 'GET');

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function getLastTransaction($admin = false)
    {
        return $this->getLastEntity('transaction', $admin);
    }

    protected function getLastPayment($admin = false)
    {
        return $this->getLastEntity('payment', $admin);
    }

    protected function getPublicEntity($entity, array $input = array())
    {
        $this->ba->privateAuth();

        $url = '/'. $entity . 's';

        $request = array(
            'url' => $url,
            'method' => 'GET',
            'content' => $input);

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    public function getNodalAccountBalance()
    {
        return $this->getEntityById('balance', '10NodalAccount', true);
    }
}
