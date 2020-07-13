<?php

namespace RZP\Base\Http;

use Http\Mock\Client;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @see \Http\Mock\Client.
 * This extension implements \Psr\Http\Client\ClientInterface, so we can have mock for psr-18 http clients.
 */
class Psr18ClientMock extends Client implements ClientInterface
{
    /**
     * {@inheritDoc}
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return parent::sendRequest($request);
    }
}
