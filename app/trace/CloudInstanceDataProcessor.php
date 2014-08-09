<?php

namespace Trace;

use Requests;

/**
 * Adds cloud instance data to trace
 */
class CloudInstanceDataProcessor
{
    protected $attributes = array(
        'ami-id',
        'availability-zone',
        'instance-id',
        'instance-type',
        'local-ipv4',
        'public-ipv4');

    /**
     * @param  array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        $data = $this->getInstanceData();

        $record['instance'] = $data;

        return $record;
    }

    public function getInstanceData()
    {
        $str = $this->getInstanceDataString();

        return $this->parseInstanceDataString($str);
    }

    public function getInstanceDataString()
    {
        $data = array();

        exec('ec2metadata 2> /dev/null', $output, $status);

        if ($status === 0)
            return $data;
    }

    public function parseInstanceDataString($str)
    {
        if ($str === null)
            return;

        $data = explode('\n', $str);

        $relevantData = array();

        foreach ($data as $row)
        {
            $pair = explode(': ', $row);

            if (in_array($pair[0], $this->attributes))
            {
                $relevantData[$pair[0]] = $pair[1];
            }
        }

        return $relevantData;
    }

}