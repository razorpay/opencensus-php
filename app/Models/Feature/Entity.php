<?php

namespace RZP\Models\Feature;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
	const NAME 						= 'name';
	const TOGGLEABLE_ID				= 'toggleable_id';
	const TOGGLEABLE_TYPE			= 'toggleable_type';

	protected $table = \RZP\Constants\Table::FEATURE;

	//protected $generateIdOnCreate = true;

	protected $entity = 'feature';

	public $incrementing = true;

	protected $primaryKey = 'id';

	protected $fillable = [
		self::ID,
		self::NAME,
		self::TOGGLEABLE_ID,
		self::TOGGLEABLE_TYPE
	];

	protected $public = [
		self::ID,
		self::NAME,
		self::TOGGLEABLE_ID,
		self::TOGGLEABLE_TYPE
	];

	protected $visible = [
		self::ID,
		self::NAME,
		self::TOGGLEABLE_ID,
		self::TOGGLEABLE_TYPE
	];

	public function toggleable()
	{
		return $this->morphTo();
	}

}
