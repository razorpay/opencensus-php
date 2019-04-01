<?php

namespace RZP\Gateway\P2p\Base\Mock;

class Server
{
    protected $mockRequest;

    public function setMockRequest($request)
    {
        $this->mockRequest = $request;

        return $request;
    }

    public function request(& $content, $action = '')
    {
        return $content;
    }

    public function content(& $content, $action = '')
    {
        return $content;
    }
}
