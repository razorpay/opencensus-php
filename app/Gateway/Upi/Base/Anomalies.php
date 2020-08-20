<?php

namespace RZP\Gateway\Upi\Base;

use Razorpay\Trace\Logger;

class Anomalies
{
    /**
     * @var Gateway
     */
    protected $context;

    protected $missing = [];

    protected $extra = [];

    protected $mismatch = [];

    protected $logic = [];

    protected $level = Logger::WARNING;

    public function __construct(Gateway $context)
    {
        $this->context = $context;
    }

    public function missing(string $field, $data = true)
    {
        $this->missing[$field] = $data;

        return $this;
    }

    public function extra(string $field, $data = true)
    {
        $this->extra[$field] = $data;

        return $this;
    }

    public function mismatch(string $field, $expected, $actual)
    {
        $this->mismatch[$field] = [
            'expected'      => $expected,
            'actual'        => $actual,
        ];

        return $this;
    }

    public function logic(string $message, array $data = [])
    {
        // Logic anomalies will bump up the level
        $this->level = Logger::CRITICAL;

        $this->logic[] = [
            'message'   => $message,
            'data'      => $data,
        ];

        return $this;
    }

    public function hasAnomalies(): bool
    {
        return ((count($this->missing) > 0) or
                (count($this->extra) > 0) or
                (count($this->mismatch) > 0) or
                (count($this->logic) > 0));
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function toArray()
    {
        return [
            'missing'   => $this->missing,
            'extra'     => $this->extra,
            'mismatch'  => $this->mismatch,
            'logic'     => $this->logic,
        ];
    }
}
