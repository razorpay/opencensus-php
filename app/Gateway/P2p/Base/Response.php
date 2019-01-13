<?php

namespace RZP\Gateway\P2p\Base;

use RZP\Models\P2p\Base\Libraries\ArrayBag;

class Response
{
    const MOCKED        = 'mocked';
    const SUCCESS       = 'success';
    const DATA          = 'data';
    const ERROR         = 'error';
    const CODE          = 'code';
    const DESCRIPTION   = 'description';

    private $content;

    public function __construct(bool $mocked = false, bool $success = true)
    {
        $this->content = [
            self::MOCKED    => $mocked,
            self::SUCCESS   => $success,
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
        return $this->content[self::SUCCESS];
    }

    public function setData(array $data)
    {
        $this->content[self::DATA] = new ArrayBag($data);
    }

    public function data(): ArrayBag
    {
        return $this->content[self::DATA];
    }

    public function setError(string $code, string $description)
    {
        $this->content[self::ERROR] = new ArrayBag([
            self::CODE          => $code,
            self::DESCRIPTION   => $description,
        ]);
    }

    public function error(): ArrayBag
    {
        return $this->error();
    }
}
