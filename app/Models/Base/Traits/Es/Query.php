<?php

namespace RZP\Models\Base\Traits\Es;

trait Query
{
    protected $index;

    protected $type;

    protected $source = false;

    protected $from   = 0;

    protected $size   = 10;

    protected $query  = [];

    /**
     * Builds query on given index and type with given incomplete query(optional)
     * and an array of params.
     *
     * @param string $index
     * @param string $type
     * @param array  $query
     * @param array  $params
     *
     * @return null
     */
    public function buildQuery(
        string $index,
        string $type,
        array $query,
        array $params)
    {
        $this->setProperties($index, $type, $query, $params);

        $this->doBuildQuery($params);
    }

    /**
     * Gets param for making ES search call using the PHP client.
     *
     * @return array
     */
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

    /**
     * Sets class members.
     * Mostly called from this class itself.
     *
     * @param string $index
     * @param string $type
     * @param array  $query
     * @param array  $params
     *
     * @return null
     */
    public function setProperties(
        string $index,
        string $type,
        array $query,
        array & $params)
    {
        $this->index  = $index;
        $this->type   = $type;
        $this->query  = $query;

        $this->from   = ($params['skip']) ?? 0;
        $this->size   = ($params['count']) ?? 10;
        $this->source = boolval(($params['search_hits']) ?? false);

        unset($params['skip']);
        unset($params['count']);
        unset($params['search_hits']);
    }

    /**
     * Actually builds the query by calling builder for every fields in params.
     * In fall back case, usage default impl.
     *
     * @param array $params
     *
     * @return null
     */
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

    // ------------------------------------------------------------------------
    // Query helper methods
    //

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

    // ------------------------------------------------------------------------


    // ------------------------------------------------------------------------
    //
    // Implementation of query builder for common fields. Of course, these can
    // be overridden in their own repo class.
    //

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

    // ------------------------------------------------------------------------
}
