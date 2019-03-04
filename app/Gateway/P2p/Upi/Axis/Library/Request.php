<?php

namespace RZP\Gateway\P2p\Upi\Axis\Library;

use Illuminate\Support\Collection;
use RZP\Base\JitValidator;

class Request
{
    protected $action;

    protected $map;

    protected $input;

    protected $payload;

    protected $signature;

    public function __construct(string $action, $map)
    {
        $this->action = $action;

        $this->map = $map[$action];
    }

    public function finish()
    {
        if (empty($this->map['validator']) === false)
        {
            (new JitValidator)->rules($this->map['validator'])->input($this->input->toArray())->validate();
        }

        if ($this->map['signature'] === true)
        {
            foreach ($this->map['mapper'] as $key)
            {
                $sign .= $this->input[$key];
            }

            $this->signature = $sign;
        }
    }

    public function merge(array $attributes)
    {
        if ($this->input === null)
        {
            $this->input = new Collection($attributes);
        }

        $this->input->merge($attributes);
    }

    public function toArray()
    {
        $payload  = [];

        if (empty($this->map['mapper']) === false)
        {
            foreach ($this->map['mapper'] as $key)
            {
                $payload[$key] = $this->input->get($key);
            }
        }

        if ($this->signature !== null)
        {
            $payload[Fields::MERCHANT_SIGNATURE] = $this->signature;
        }

        $request[Fields::ID] = $this->getSdkRequestId();

        $request[Fields::ACTION] = $this->action;

        $request[Fields::PAYLOAD] = $payload;

        return $request;
    }

    protected function getSdkRequestId()
    {
        return str_random();
    }
}
