<?php

namespace RZP\Base;

use App;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use Watson\Rememberable\Query\Builder as RememberableQueryBuilder;

use RZP\Trace\TraceCode;

/**
 * Overriden rememberable package's Builder class, as we need to add
 * exception handling, in case redis throws an error
 */
class QueryBuilder extends RememberableQueryBuilder
{
     /**
     * Execute the query as a cached "select" statement.
     *
     * @param  array  $columns
     * @return array
     */
    public function getCached($columns = ['*'])
    {
        $trace = App::getFacadeRoot()['trace'];

        try
        {
            return parent::getCached($columns);
        }
        catch (\Throwable $e)
        {
            $trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::QUERY_CACHE_ERROR,
                $columns);

            return IlluminateQueryBuilder::get($columns);
        }
    }
}

