<?php

namespace RZP\Base\Database;

use Closure;
use InvalidArgumentException;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;

use RZP\Base\BuilderEx;

/**
 * This class is overriden and following methods are added only to implement
 * the joinSub feature available in laravel 5.6
 * TODO: Remove this once upgraded to 5.6
 */
class QueryBuilder extends IlluminateQueryBuilder
{
    /**
     * Add a subquery join clause to the query.
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|string $query
     * @param  string  $as
     * @param  string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * @param  bool    $where
     * @return \Illuminate\Database\Query\Builder|static
     *
     * @throws \InvalidArgumentException
     */
    public function joinSub($query, $as, $first, $operator = null, $second = null, $type = 'inner', $where = false)
    {
        list($query, $bindings) = $this->createSub($query);

        $expression = '('.$query.') as '.$this->grammar->wrap($as);

        $this->addBinding($bindings, 'join');

        return $this->join(new Expression($expression), $first, $operator, $second, $type, $where);
    }

    /**
     * Creates a subquery and parse it.
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|string $query
     * @return array
     */
    protected function createSub($query)
    {
        //
        // If the given query is a Closure, we will execute it while passing in a new
        // query instance to the Closure. This will give the developer a chance to
        // format and work with the query before we cast it to a raw SQL string.
        //
        if ($query instanceof Closure)
        {
            $callback = $query;

            $callback($query = $this->forSubQuery());
        }

        return $this->parseSub($query);
    }

    /**
     * Parse the subquery into SQL and bindings.
     *
     * @param  mixed  $query
     * @return array
     */
    protected function parseSub($query)
    {
        if ($query instanceof BuilderEx)
        {
            return [$query->toSql(), $query->getBindings()];
        }
        elseif (is_string($query))
        {
            return [$query, []];
        }
        else
        {
            throw new InvalidArgumentException;
        }
    }
}
