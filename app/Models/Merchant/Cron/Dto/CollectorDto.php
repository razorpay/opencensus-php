<?php


namespace RZP\Models\Merchant\Cron\Dto;


class CollectorDto
{
    protected $data;

    public function __construct($data = null)
    {
        $this->data = $data;
    }

    public static function create($data)
    {
        return new CollectorDto($data);
    }

    public function getData()
    {
        return $this->data;
    }
}
