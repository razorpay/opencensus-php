<?php

namespace RZP\Services\Kafka\Consumers\Base;

class Config
{
    protected $default_config = [
        //'default_custom1' => 'default_value1',
        //'default_custom2' => 'default_value2',
        //'default_custom3' => 'default_value3',
    ];


    protected $custom_config = [];

    /**
     * @return array
     */
    public function getDefaultConfig(): array
    {
        return $this->default_config;
    }

    /**
     * @return array
     */
    public function getCustomConfig(): array
    {
        return $this->custom_config;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return array_merge($this->default_config, $this->custom_config);
    }
}
