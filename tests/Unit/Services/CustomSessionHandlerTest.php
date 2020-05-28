<?php

namespace RZP\Tests\Unit\Services;

use Redis;
use Session;
use Cache;

use RZP\Tests\TestCase;

class CustomSessionHandlerTest extends TestCase
{
    protected $app = null;

    public function setUp()
    {
        parent::setUp();

        $this->app = $this->createApplication();
    }

    public function testCustomSessionHandlerReadMiss()
    {
        Cache::shouldReceive('driver')
            ->once()
            ->andReturnUsing(function()
            {
                return $this->app['cache'];
            });

        Cache::shouldReceive('get')
            ->once()
            ->andReturnUsing(function()
            {
                return null;
            });

        Cache::shouldReceive('get')
            ->once()
            ->andReturnUsing(function()
            {
                return 'test1';
            });

        Cache::shouldReceive('put')
            ->times(2)
            ->andReturnUsing(function()
            {
                return true;
            });


        $app = $this->createApplication();

        $this->app['session']->getHandler()->read('test');
    }

    public function testCustomSessionHandlerReadHit()
    {
        Cache::shouldReceive('driver')
            ->once()
            ->andReturnUsing(function()
            {
                return $this->app['cache'];
            });

        Cache::shouldReceive('get')
            ->once()
            ->andReturnUsing(function()
            {
                return 'test';
            });

        $app = $this->createApplication();

        $this->app['session']->getHandler()->read('test');
    }

    public function testCustomSessionHandlerWrite()
    {
        Cache::shouldReceive('driver')
            ->once()
            ->andReturnUsing(function()
            {
                return $this->app['cache'];
            });

        Cache::shouldReceive('put')
            ->times(2)
            ->andReturnUsing(function()
            {
                return 'test';
            });

        $app = $this->createApplication();

        $this->app['session']->getHandler()->write('test', 'test');
    }

    public function testCustomSessionHandlerWriteError()
    {
        Cache::shouldReceive('driver')
            ->once()
            ->andReturnUsing(function()
            {
                return $this->app['cache'];
            });


        Cache::shouldReceive('put')
            ->once()
            ->andReturnUsing(function()
            {
                \Predis\Response\ServerException('Internal Error');
            });

        Cache::shouldReceive('forget')
            ->times(2)
            ->andReturnUsing(function()
            {
                return 'null';
            });


        $app = $this->createApplication();

        $this->app['session']->getHandler()->write('test', 'test');
    }

    public function testCustomSessionHandlerWriteDestroy()
    {
        Cache::shouldReceive('driver')
            ->once()
            ->andReturnUsing(function()
            {
                return $this->app['cache'];
            });


        Cache::shouldReceive('forget')
            ->times(2)
            ->andReturnUsing(function()
            {
                return 'null';
            });

        $app = $this->createApplication();

        $this->app['session']->getHandler()->destroy('test');
    }

    public function testCustomSessionHandlerWriteExceptionCheck()
    {
        Cache::shouldReceive('driver')
            ->once()
            ->andReturnUsing(function()
            {
                return $this->app['cache'];
            });

        Cache::shouldReceive('get')
            ->times(1)
            ->andReturnUsing(function()
            {
                \Predis\Response\ServerException('Internal Error');
            });

        Cache::shouldReceive('put')
            ->times(1)
            ->andReturnUsing(function()
            {
                \Predis\Response\ServerException('Internal Error');
            });

        Cache::shouldReceive('forget')
            ->times(2)
            ->andReturnUsing(function()
            {
                \Predis\Response\ServerException('Internal Error');
            });

        $app = $this->createApplication();

        $handler = $this->app['session']->getHandler();
        $handler->read('test');
        $handler->write('test', 'test');
        $handler->destroy('test');
    }

}
