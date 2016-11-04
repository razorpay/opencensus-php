<?php

namespace RZP\Models\Feature;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
	const NAME             = 'name';
	const ENTITY_ID        = 'entity_id';
	const ENTITY_TYPE      = 'entity_type';

	protected $table = \RZP\Constants\Table::FEATURE;

	protected $entity = 'feature';

	public $incrementing = true;

	protected $primaryKey = 'id';

	protected $fillable = [
		self::NAME,
		self::ENTITY_ID,
		self::ENTITY_TYPE
	];

	protected $public = [
		self::ID,
		self::NAME,
		self::ENTITY_ID,
		self::ENTITY_TYPE
	];

	protected $visible = [
		self::ID,
		self::NAME,
		self::ENTITY_ID,
		self::ENTITY_TYPE
	];

	/**
	 * Creates a polymorphic relation woth entities
	 * implementing a morphMany association on the
	 * 'entity' key
	 */
	public function entity()
	{
		return $this->morphTo();
	}

}
