<?php

namespace Models\DAL;

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

		static::creating(function($model)
        {
            $model->{$model->getKeyName()} = (string)$model->generateUuid();
        });
	}

	public function generateUuid()
	{
		return \Uuid::uuid1();
	}
}
