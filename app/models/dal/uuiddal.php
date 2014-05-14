<?php

namespace Models\DAL;

use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

class UuidDAL extends DAL
{

	/**
	 * Indicates if the IDs are uuid.
	 *
	 * @var bool
	 */
	public $uuid = true;

	public $incrementing = false;

	protected static function boot()
	{
		parent::boot();

		/*static::creating(function($model)
        {
            $model->{$model->getKeyName()} = (string)Uuid::uuid1();
        });*/
	}

	public static function generateUuid()
	{
		return str_replace("-", "", Uuid::uuid1());
	}
}