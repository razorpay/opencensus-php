<?php

namespace RZP\Services;

use Trace;
use RZP\Trace\TraceCode;

class AwsInstance
{
    protected $attributes = array(
        'ami-id',
        'availability-zone',
        'instance-id',
        'instance-type',
        'local-ipv4',
        'public-ipv4');

    protected $data = null;

    protected $cloud;

    protected $instanceDataFile;

    public function __construct()
    {
        $app = \App::getFacadeRoot();

        $this->cloud = $app['config']->get('app.cloud');

        $this->instanceDataFile = $app['config']->get('trace.instance_data_file');

        $this->env = $app->environment();
    }

    public function getInstanceId()
    {
        return $this->getInstanceData()['instance-id'];
    }

    public function getInstanceData()
    {
        if ($this->data !== null)
        {
            return $this->data;
        }

        $data = $this->getInstanceDataFromStorage();

        if ($data !== null)
        {
            $this->data = $data;
            return $this->data;
        }

        if ($this->cloud === false)
        {
            $data = $this->generateRandomInstanceData();
        }
        else
        {
            $data = $this->getInstanceDataFromShell();
        }

        $this->saveInstanceDataToStorage($data);

        $this->data = $data;

        return $this->data;
    }

    protected function getInstanceDataFromShell()
    {
        exec('ec2metadata 2> /dev/null', $data, $status);

        if ($status !== 0)
        {
            Trace::critical(TraceCode::AWS_INSTANCE_DATA_RECORD_FAILURE);

            return null;
        }

        $instanceData = [];

        foreach ($data as $row)
        {
            $pair = explode(': ', $row);

            $key = $pair[0];

            if ((in_array($key, $this->attributes, true)) and
                (isset($pair[1])))
            {
                $instanceData[$key] = $pair[1];
            }
        }

        return $instanceData;
    }

    protected function saveInstanceDataToStorage($data)
    {
        $jsonData = json_encode($data);

        $res = file_put_contents($this->instanceDataFile, $jsonData);

        if ($res === false)
        {
            Trace::error(TraceCode::AWS_INSTANCE_DATA_WRITE_FAILURE);
        }
    }

    protected function getInstanceDataFromStorage()
    {
        if (file_exists($this->instanceDataFile) === false)
        {
            return;
        }

        $jsonData = file_get_contents($this->instanceDataFile);

        if ($jsonData === false)
        {
            Trace::error(TraceCode::AWS_INSTANCE_DATA_READ_FAILURE);

            return;
        }

        return json_decode($jsonData, true);
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
