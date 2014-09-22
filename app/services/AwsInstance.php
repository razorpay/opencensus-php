<?php

namespace Services;

class AwsInstance
{
    protected $attributes = array(
        'ami-id',
        'availability-zone',
        'instance-id',
        'instance-type',
        'local-ipv4',
        'public-ipv4');

    protected $data = array();

    protected $relevantData = array();

    protected $generated = false;

    protected $cloud;

    public function __construct()
    {
        $this->cloud = \Config::get('app.cloud');

        $this->env = \App::environment();
    }

    public function getInstanceId()
    {
        if ($this->cloud === false)
            return null;

        return $this->getFullInstanceData()['instance-id'];
    }

    public function getInstanceData()
    {
        if ($this->generated === true)
        {
            return $this->relevantData;
        }

        $data = $this->getFullInstanceData();

        return $this->getRelevantInstanceData($data);
    }

    public function getFullInstanceData()
    {
        if ($this->generated === true)
        {
            return $this->data;
        }

        if (($this->cloud === true) and
            ($this->env === 'testing'))
        {
            return $this->generateRandomInstanceData();
        }

        exec('ec2metadata 2> /dev/null', $data, $status);

        if ($status === 0)
            return null;

        foreach ($data as $row)
        {
            $pair = explode(': ', $row);

            $this->data[$pair[0]] = $pair[1];
        }

        $this->generated = true;

        return $this->data;
    }

    protected function getRelevantInstanceData($data)
    {
        if ($data === null)
            return;

        foreach ($this->attributes as $attribute)
        {
            if (array_key_exists($attribute, $data))
            {
                $this->relevantData[$attribute] = $data[$attribute];
            }
        }

        return $this->relevantData;
    }

    protected function generateRandomInstanceData()
    {
        foreach ($this->attributes as $attribute)
        {
            $this->data[$attribute] = 'random testing';
        }

        return $this->data;
    }
}