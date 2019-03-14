<?php

namespace RZP\Gateway\P2p\Base;

use RZP\Gateway\Base;
use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Models\P2p\Base\Libraries\Context;

class Gateway extends Base\Gateway
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var ArrayBag
     */
    protected $input;

    public function setContext(Context $context)
    {
        $this->context = $context;
    }

    public function getContextDevice()
    {
        return $this->context->getDevice()->toArrayBag();
    }

    public function getContextDeviceToken()
    {
        return $this->context->getDeviceToken()->toArrayBag();
    }

    public function setActionAndInput(string $action, ArrayBag $input)
    {
        $this->action = $action;

        $this->input = $input;
    }

    protected function getRepository()
    {
        // There is no repository required in P2P UPI
    }

    protected function makeResponse(): Response
    {
        $mock       = $this->shouldMockResponse();
        $success    = $mock ? $this->shouldMockSuccessResponse() : true;

        return (new Response($mock, $success));
    }

    protected function shouldMockResponse(): bool
    {
        return false;
    }

    protected function shouldMockSuccessResponse(): bool
    {
        return true;
    }

    protected function response(): Response
    {
        $action = $this->action;

        $response = $this->makeResponse();

        $this->$action($response);

        // Here we can add logic to check for synchronisation

        return $response;
    }
}
