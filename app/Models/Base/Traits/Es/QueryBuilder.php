<?php

namespace RZP\Models\Base\Traits\Es;

use RZP\Base\Common;

/**
 * Trait used in Es/Repository class for forming es queries.
 * New methods(or overriding existing one's) can be done in the corresponding
 * entities' EsRepository class.
 *
 */
trait QueryBuilder
{
    /**
     * Extracts ES query meta attributes from passed parameters.
     *
     * @param array $params
     *
     * @return array
     */
    public function extractQueryMetaFromParams(array & $params)
    {
        $from   = ($params[self::SKIP]) ?? 0;
        $size   = ($params[self::COUNT]) ?? 10;
        $source = boolval(($params[self::SEARCH_HITS]) ?? false);

        unset($params[self::SKIP], $params[self::COUNT], $params[self::SEARCH_HITS]);

        return [$from, $size, $source];
    }

    /**
     * Default query construct for given field and value. We use match query
     * with a boost of 2 as we're matching against a particular field.
     *
     * @param array  $query
     * @param string $field
     * @param string $value
     */
    public function buildQueryForFieldDefaultImpl(
        array & $query,
        string $field,
        string $value)
    {
        //
        // In match query we want at least 75% of terms to match given doc's field.
        // This ensures we avoid false results. The same is done in multi_match
        // query as well.
        //
        // Refs:
        // - https://www.elastic.co/guide/en/elasticsearch/reference/5.5/query-dsl-match-query.html
        // - https://www.elastic.co/guide/en/elasticsearch/reference/5.5/query-dsl-minimum-should-match.html
        //

        $clause = [
            'match' => [
                $field => [
                    'query'                => strtolower($value),
                    'boost'                => 2,
                    'minimum_should_match' => '75%',
                ],
            ],
        ];

        $this->addMust($query, $clause);
    }

    /**
     * Builds query for 'q' param. Ref Base\EsRepository class.
     *
     * @param array $query
     * @param string $value
     */
    public function buildQueryForQ(array & $query, string $value)
    {
        //
        // - Boost given for 'q' is 1 to lower it's contribution when there are more
        //   matches by exact fields(eg. receipt, description etc) when used in
        //   combination with other
        // - It's a multi match query as given query is run against a set of fields
        //   (defined in $queryFields). Also we use type 'best_fields' (default).
        //   Ref: https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-multi-match-query.html
        //

        $clause = [
            'multi_match' => [
                'query'                => $value,
                'type'                 => 'best_fields',
                'fields'               => $this->queryFields,
                'boost'                => 1,
                'minimum_should_match' => '75%',
            ],
        ];

        $this->addMust($query, $clause);
    }

    public function  buildQueryForNotes(array & $query, string $value)
    {
        //
        // - Notes search is again on an specific object (unlike 'q') and so
        //   we give boost of 2.
        // - The query construct is same as above (for 'q') but the fields here
        //   are all keys of notes object (denoted as notes.*).
        //

        $clause = [
            'multi_match' => [
                'query'                => $value,
                'type'                 => 'best_fields',
                'fields'               => 'notes.*',
                'boost'                => 2,
                'minimum_should_match' => '75%',
            ],
        ];

        $this->addMust($query, $clause);
    }

    public function buildQueryForMerchantId(array & $query, string $value)
    {
        // In few cases we would want to add the clause as filter. Eg. in this case
        // we must use filter to filter out all results for a given merchant id
        // on top of which other queries/search are run. Filter queries are cached
        // so it's fast too.
        //
        // Also notice that here we're using 'term' query. Ie. because we don't
        // want to do any analysis when searching for merchant_id unlike other
        // fields.

        $filter = [
            'term' => [
                'merchant_id' => [
                    'value' => $value,
                ],
            ],
        ];

        $this->addFilter($query, $filter);
    }

    /**
     * Builds query for 'to' and 'from'. Handling these both in same instead of
     * buildQueryForTo() and buildQueryForFrom() like methods. Reason for that
     * is this way there is one range clause with lte and gte both in it.
     * Otherwise there would have been two different range queries and it's not
     * optimal.
     *
     * @param array $query
     * @param array $params
     */
    public function buildQueryForFromAndToIfApplies(array & $query, array & $params)
    {
        $clause['gte'] = $params[self::FROM] ?? null;
        $clause['lte'] = $params[self::TO] ?? null;

        $clause = array_filter($clause);

        if (empty($clause))
        {
            return;
        }

        $filter = ['range' => [Common::CREATED_AT => $clause]];

        $this->addFilter($query, $filter);

        unset($params[self::FROM], $params[self::TO]);
    }

    /**
     * Returns sort parameter value for ES request.
     * By default the sorting is on score followed by created_at of the document.
     *
     * @return array
     */
    public function getSortParameter()
    {
        return [
            '_score' => [
                'order' => 'desc',
            ],
            Common::CREATED_AT => [
                'order' => 'desc',
            ],
        ];
    }

    // Helper methods

    public function getExistsQueryForField(string $field)
    {
        return ['exists' => ['field' => $field]];
    }

    public function addNotNullFilterForField(array & $query, string $field)
    {
        $this->addFilter($query, $this->getExistsQueryForField($field));
    }

    public function addNullFilterForField(array & $query, string $field)
    {
        $this->addNegativeFilter($query, $this->getExistsQueryForField($field));
    }

    public function addShould(array & $query, array $clause)
    {
        $query['bool']['should'][] = $clause;
    }

    public function addMust(array & $query, array $clause)
    {
        $query['bool']['must'][] = $clause;
    }

    public function addFilter(array & $query, array $filter)
    {
        $query['bool']['filter']['bool']['must'][] = $filter;
    }

    public function addNegativeFilter(array & $query, array $filter)
    {
        $query['bool']['filter']['bool']['must_not'][] = $filter;
    }
}
