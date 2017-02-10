<?php

namespace RZP\Models\Base;

use App;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

trait EsQuery
{
    protected $index;

    protected $type;

    protected $source         = false;

    protected $from           = 0;

    protected $size           = 10;

    protected $query          = [];

    protected $searchHitsOnly = false;

    public function buildQuery(
        string $index,
        string $type,
        array $query,
        array $params)
    {
        $this->setProperties($index, $type, $query, $params);

        $this->doBuildQuery($params);
    }

    public function getEsRequestParams()
    {
        return [
            'index' => $this->index,
            'type'  => $this->type,
            'body'  => [
                '_source' => $this->source,
                'from'    => $this->from,
                'size'    => $this->size,
                'query'   => $this->query,
            ],
        ];
    }

    public function setProperties(
        string $index,
        string $type,
        array $query,
        array & $params)
    {
        $this->from           = ($params['skip']) ?? 0;
        $this->size           = ($params['count']) ?? 10;
        $this->searchHitsOnly = boolval(($params['search_hits']) ?? false);

        unset($params['skip']);
        unset($params['count']);
        unset($params['search_hits']);

        $this->index  = $index;
        $this->type   = $type;
        $this->query  = $query;
        $this->source = $this->searchHitsOnly;
    }

    public function doBuildQuery(array $params)
    {
        foreach ($params as $field => $value)
        {
            $f = 'buildQueryFor' . studly_case($field);

            if (method_exists($this, $f))
            {
                $this->$f($value);
            }
            else
            {
                $this->buildQueryForFieldDefaultImpl($field, $value);
            }
        }
    }

    public function addMust(array $clause)
    {
        $this->query['bool']['must'][] = $clause;

        return $this;
    }

    public function addFilter(array $filter)
    {
        $this->query['bool']['filter']['bool']['must'][] = $filter;

        return $this;
    }

    public function buildQueryForFieldDefaultImpl(string $field, string $value)
    {
        $clause = [
            'term' => [
                $field => [
                    'value' => $value,
                    'boost' => 2,
                ],
            ],
        ];

        $this->addMust($clause);
    }

    public function buildQueryForQ(string $value)
    {
        $clause = [
            'multi_match' => [
                'query'  => $value,
                'type'   => 'best_fields',
                'fields' => $this->queryFields,
                'boost'  => 1,
            ],
        ];

        $this->addMust($clause);
    }

    public function  buildQueryForNotes(string $value)
    {
        $clause = [
            'multi_match' => [
                'query'  => $value,
                'type'   => 'best_fields',
                'fields' => 'notes.*',
                'boost'  => 2,
            ],
        ];

        $this->addMust($clause);
    }

    public function buildQueryForMerchantId(string $value)
    {
        $filter = [
            'term' => [
                'merchant_id' => [
                    'value' => $value,
                ],
            ],
        ];

        $this->addFilter($filter);
    }
}
