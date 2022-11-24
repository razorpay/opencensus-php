<?php

namespace RZP\Services\Mock;

use Mockery;
use Mailgun\Mailgun as MgClient;
use RZP\Services\Mailgun as BaseMailgun;
use Http\Adapter\Guzzle7\Client as GuzzleClient;

class Mailgun extends BaseMailgun
{
    public function getMailgunInstance()
    {
        $class = MgClient::class;

        $instance = Mockery::mock($class, ['', new GuzzleClient])->makePartial();

        $instance->shouldReceive('post')
                ->andReturn(null);

        $object = new \stdClass;

        $object->http_response_body = new \stdClass;

        $object->http_response_body->total_count = 0;

        $instance->shouldReceive('get')
                 ->andReturn($object);

        return $instance;
    }
}
