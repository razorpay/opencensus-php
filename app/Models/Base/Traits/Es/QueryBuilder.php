<?php

namespace RZP\Models\Base\Traits\Es;

/**
 * Trait used in Es/Repository class for forming es queries.
 * New methods(or overriding existing one's) can be done in the corresponding
 * entities' EsRepository class.
 *
 */
trait QueryBuilder
{
    /**
     * Default query construct for given field and value. We use term query with
     * a boost of 2. We give boost 2 for all term queries as it'll be exact match.
     *
     * @param array  $query
     * @param string $field
     * @param string $value
     */
    public function buildQueryForFieldDefaultImpl(array & $query, string $field, string $value)
    {
        $clause = [
            'term' => [
                $field => [
                    'value' => $value,
                    'boost' => 2,
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
        // - Boost given for 'q' is 1 to lower it's contribution when there are more
        //   exact matches when used in combination with other term queries.
        // - It's a multi match query as given query is run against a set of fields
        //   (defined in $queryFields). Also we use type 'best_fields' (default).
        //   Ref: https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-multi-match-query.html

        $clause = [
            'multi_match' => [
                'query'  => $value,
                'type'   => 'best_fields',
                'fields' => $this->queryFields,
                'boost'  => 1,
            ],
        ];

        $this->addMust($query, $clause);
    }

    public function  buildQueryForNotes(array & $query, string $value)
    {
        // - Notes search is again on an specific object (unlike 'q') and so
        //   we give boost of 2.
        // - The query construct is same as above (for 'q') but the fields here
        //   are all keys of notes object (denoted as notes.*).

        $clause = [
            'multi_match' => [
                'query'  => $value,
                'type'   => 'best_fields',
                'fields' => 'notes.*',
                'boost'  => 2,
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

        $filter = [
            'term' => [
                'merchant_id' => [
                    'value' => $value,
                ],
            ],
        ];

        $this->addFilter($query, $filter);
    }

    // Helper methods

    public function addMust(array & $query, array $clause)
    {
        $query['bool']['must'][] = $clause;
    }

    public function addFilter(array & $query, array $filter)
    {
        $query['bool']['filter']['bool']['must'][] = $filter;
    }
}
