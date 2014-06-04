<?php

namespace Exceptions;

class DbQueryException extends ServerErrorException
{
	protected $fields = array(
		'operation',
		'table',
		'model',
		'attributes');

	protected $data = array();

    public function __construct(array $data, Exception $previous = null)
    {
    	$this->set($data);

		$message = $this->createMessage();

    	$message = 'Failed '. $this->data['operation'].' operation on '.$table.' table with attributes '.
    			   implode_assoc_array($attributes);

        parent::__construct($message, Status::DB_ERROR, $previous);
    }

    public function set(array $array)
    {
    	foreach ($array as $key => $value)
    	{
    		if (in_array($key, $this->fields))
    		{
    			$this->data['key'] = $value;
    		}
    	}
    }

    public function createMessage()
    {
    	$msg = 'Failed db';
    	$data = $this->data;

    	if (isset($data['operation']))
    	{
    		$msg .= ' ' . $data['operation'];
    	}

    	$msg .= ' operation';

    	if (isset($data['table']))
    	{
    		$msg .= ' on ' . $table . ' table';
    	}

    	if (isset($data['model']))
    	{
    		$msg .= ' on ' . $model . ' model';
    	}

    	if (isset($data['attributes']))
    	{
    		$msg .= ' with attributes ' . implode_assoc_array($data['attributes']);
    	}

    	return $msg;
    }
}