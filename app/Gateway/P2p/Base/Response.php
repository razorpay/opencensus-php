<?php

namespace RZP\Gateway\P2p\Base;

use RZP\Models\P2p\Base\Libraries\ArrayBag;

class Response
{
    const MOCKED        = 'mocked';
    const SUCCESS       = 'success';
    const REQUEST       = 'request';
    const CALLBACK      = 'callback';
    const DATA          = 'data';
    const ERROR         = 'error';
    const CODE          = 'code';
    const GATEWAY_CODE  = 'gateway_code';
    const DESCRIPTION   = 'description';

    private $content;

    public function __construct(bool $mocked = false, bool $success = true)
    {
        $this->content = [
            self::MOCKED    => $mocked,
            self::SUCCESS   => $success,
            self::REQUEST   => null,
            self::CALLBACK  => null,
            self::DATA      => new ArrayBag(),
            self::ERROR     => new ArrayBag(),
        ];
    }

    public function setMock(bool $mock)
    {
        $this->content[self::MOCKED] = $mock;
    }

    public function isMocked(): bool
    {
        return $this->content[self::MOCKED];
    }

    public function setSuccess(bool $success)
    {
        $this->content[self::SUCCESS] = $success;
    }

    public function isSuccess(): bool
    {
        return $this->error()->isEmpty();
    }

    public function setData(array $data)
    {
        $this->content[self::DATA] = new ArrayBag($data);
    }

    public function data(): ArrayBag
    {
        return $this->content[self::DATA];
    }

    public function setError(string $code, string $description, string $gatewayCode = null)
    {
        $this->content[self::ERROR] = new ArrayBag([
            self::CODE          => $code,
            self::DESCRIPTION   => $description,
            self::GATEWAY_CODE  => $gatewayCode,
        ]);
    }

    public function error(): ArrayBag
    {
        return $this->content[self::ERROR];
    }

    public function setRequest(Request $request)
    {
        $this->content[self::REQUEST] = $request->finish();
    }

    public function hasRequest(): bool
    {
        return ($this->content[self::REQUEST] instanceof Request);
    }

    public function request()
    {
        return $this->content[self::REQUEST]->toArrayBag();
    }

    public function requestType()
    {
        return $this->content[self::REQUEST]->type();
    }

    public function requestCallback()
    {
        return $this->content[self::REQUEST]->callback();
    }

    private function next(): Next
    {
        return $this->content[self::NEXT];
    }
}
