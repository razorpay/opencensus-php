<?php

namespace Models\Base;

use EE\Exception;

class EloquentEx extends \Razorpay\Spine\Entity
{
    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @return \Models\Base\BuilderEx|static
     */
    public function newEloquentBuilder($query)
    {
        return new BuilderEx($query);
    }

    protected function throwException($operation, $attributes = null)
    {
        $e = $this->getExceptionDataArray($operation, $attributes);

        throw new Exception\DbQueryException($e);
    }
}
