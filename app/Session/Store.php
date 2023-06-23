<?php

namespace RZP\Session;

use SessionHandlerInterface;

class Store extends \Illuminate\Session\Store
{
    protected SessionHandlerInterface $fallbackHandler;

    public function __construct($name, SessionHandlerInterface $handler, SessionHandlerInterface $fallbackHandler, $id = null, $serialization = 'php')
    {
        parent::__construct($name, $handler, $id, $serialization);
        $this->fallbackHandler = $fallbackHandler;
    }

    protected function readFromHandler()
    {
        $id = $this->getId();
        $data = $this->handler->read($id);

        if (empty($data)) {
            $data = $this->fallbackHandler->read($id);
        }

        if (!empty($data)) {
            if ($this->serialization === 'json') {
                $data = json_decode($this->prepareForUnserialize($data), true);
            } else {
                $data = @unserialize($this->prepareForUnserialize($data));
            }

            if ($data !== false && is_array($data)) {
                return $data;
            }
        }

        return [];
    }
}
