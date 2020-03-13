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
    const TIME          = 'time';
    const ACTION        = 'action';
    const CONTENT       = 'content';
    const VALIDATE      = 'validate';

    private $type = null;
    private $validate = [];
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

    public function setRedirect(string $time = null)
    {
        $this->setType('redirect');
        $this->load[self::TIME] = $time;
        return $this;
    }

    public function setPoll(string $time = null, string $url = null)
    {
        $this->setType('poll');
        $this->load[self::TIME] = $time;
        $this->load[self::URL] = $url;
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
        $this->validate = $validate;
        return $this;
    }

    public function setType(string $type)
    {
        $this->type = $type;
        return $this;
    }

    public function setCallback(array $callback)
    {
        $this->callback = $callback;
        return $this;
    }

    public function finish()
    {
        //$this->validate();

        return $this;
    }

    public function type()
    {
        return $this->type;
    }

    public function callback()
    {
        return $this->callback;
    }

    public function validate()
    {
        return $this->validate;
    }

    public function toArrayBag(): ArrayBag
    {
        return (new ArrayBag(array_filter($this->load)));
    }
}
