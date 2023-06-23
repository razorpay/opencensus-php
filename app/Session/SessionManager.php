<?php

namespace RZP\Session;

use Illuminate\Session\SessionManager as BaseSessionManager;
use RZP\Session\Store;
use RZP\Trace\TraceCode;

class SessionManager extends BaseSessionManager
{
    protected function buildSession($handler)
    {
        $fallbackHandler = $this->createCacheHandler('redis');

        $fallbackHandler->getCache()->getStore()->setConnection(
            'session_redis'
        );
        return $this->config->get('session.encrypt')
            ? $this->buildEncryptedSession($handler)
            : new Store(
                $this->config->get('session.cookie'),
                $handler,
                $fallbackHandler,
                $id = null,
                $this->config->get('session.serialization', 'php')
            );
    }
}
