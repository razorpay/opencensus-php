<?php

namespace RZP\Models\Feature;

use RZP\Models\Base;
use RZP\Exception;

class Core extends Base\Core
{
	public function create($input)
	{
		$feature = (new Entity)->build($input);

		$this->repo->saveOrFail($feature);

		return $feature;
	}
}
