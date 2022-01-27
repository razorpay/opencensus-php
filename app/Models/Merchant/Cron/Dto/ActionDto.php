<?php


namespace RZP\Models\Merchant\Cron\Dto;


class ActionDto
{
    protected $status;

    public function __construct(string $status)
    {
        $this->status = $status;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
