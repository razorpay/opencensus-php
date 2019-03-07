<?php

namespace RZP\Gateway\P2p\Base;

use RZP\Models\P2p\Base\Libraries\ArrayBag;

class Request
{
    const TYPE          = 'type';
    const ID            = 'id';
    const SDK           = 'sdk';
    const DESTINATION   = 'destination';
    const URL           = 'url';
    const ACTION        = 'action';
    const CONTENT       = 'content';
    const VALIDATE      = 'validate';

    private $type = null;
    private $callback = [];
    private $load = [];

    public function __construct(array $load = [])
    {
        $this->load = $load;
    }

    public function setId($id)
    {
        $this->load[self::ID] = $id;
        return $this;
    }

    public function setSdk($sdk)
    {
        $this->setType('sdk');
        $this->load[self::SDK] = $sdk;
        return $this;
    }

    public function setDestination($destination)
    {
        $this->setType('sms');
        $this->load[self::DESTINATION] = $destination;
        return $this;
    }

    public function setUrl($url)
    {
        $this->setType('post');
        $this->load[self::URL] = $url;
        return $this;
    }

    public function setAction($action)
    {
        $this->load[self::ACTION] = $action;
        return $this;
    }

    public function setContent($content)
    {
        $this->load[self::CONTENT] = $content;
        return $this;
    }

    public function setValidate($validate)
    {
        $this->load[self::VALIDATE] = $validate;
        return $this;
    }

    public function setType(string $type)
    {
        $this->type = $type;
    }

    public function setCallback(array $callback)
    {
        $this->callback = $callback;
    }

    public function finish()
    {
        //$this->validate();
    }

    public function type()
    {
        return $this->type;
    }

    public function callback()
    {
        return $this->callback;
    }

    public function toArrayBag(): ArrayBag
    {
        return (new ArrayBag(array_filter($this->load)));
    }
}
