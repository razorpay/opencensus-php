<?php

namespace RZP\Models\Feature;

use Illuminate\Database\QueryException;
use RZP\Models\Base;
use RZP\Exception;

class Core extends Base\Core
{
	public function create($input)
	{
		try
        {
            $feature = (new Entity)->build($input);

            $this->repo->saveOrFail($feature);

            return $feature;
        } catch (QueryException $e)
        {
            throw new Exception\DbQueryException([
                'name'          => $feature->name,
                'entity_id'     => $feature->entity_id,
                'entity_type'   => $feature->entity_type
            ]);
        }
	}
}
