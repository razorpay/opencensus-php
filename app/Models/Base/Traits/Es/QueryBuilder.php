<?php

namespace RZP\Models\Base\Traits\Es;

use RZP\Base\Common;
use RZP\Constants\Es;

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
            Es::MATCH => [
                $field => [

                    //
                    // Some fields have 'standard' search analyzer but some fields
                    // are 'keyword' type and there there is no search analysis.
                    // 'strtolower' is done just to be on safe side. Eg. someone
                    // sends 'status' as 'PENDING' instead of 'pending'.
                    //
                    //

                    Es::QUERY                => strtolower($value),
                    Es::BOOST                => 2,
                    Es::MINIMUM_SHOULD_MATCH => '75%',
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
            Es::MULTI_MATCH => [
                Es::QUERY                => $value,
                Es::TYPE                 => Es::BEST_FIELDS,
                Es::FIELDS               => $this->queryFields,
                Es::BOOST                => 1,
                Es::MINIMUM_SHOULD_MATCH => '75%',
                Es::LENIENT              => true,
            ],
        ];

        $this->addMust($query, $clause);
    }

    public function getQueryForWildcard(string $field, string $value)
    {
        $clause = [
            Es::WILDCARD => [
                $field => [
                    Es::VALUE => $value,
                ],
            ],
        ];

        return $clause;
    }

    public function buildQueryForNotes(array & $query, string $value)
    {
        // Refer- config/es_mappings.php on how notes is indexed.
        $clause = [
            Es::MATCH => [
                'notes.value' => [
                    Es::QUERY => $value,
                ],
            ],
        ];

        $this->addMust($query, $clause);
    }

    public function buildQueryForMerchantId(array & $query, string $value)
    {
        //
        // In few cases we would want to add the clause as filter. Eg. in this case
        // we must use filter to filter out all results for a given merchant id
        // on top of which other queries/search are run. Filter queries are cached
        // so it's fast too.
        //
        // Also notice that here we're using 'term' query. Ie. because we don't
        // want to do any analysis when searching for merchant_id unlike other
        // fields.
        //

        $this->addTermFilter($query, Common::MERCHANT_ID, $value);
    }

    public function buildQueryForBalanceId(array & $query, string $value)
    {
        $this->addTermFilter($query, Common::BALANCE_ID, $value);
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
        $clause[Es::GTE] = $params[self::FROM] ?? null;
        $clause[Es::LTE] = $params[self::TO] ?? null;

        $clause = array_filter($clause);

        if (empty($clause))
        {
            return;
        }

        $filter = [Es::RANGE => [$this->getFromAndToQueryAttribute() => $clause]];

        $this->addFilter($query, $filter);

        unset($params[self::FROM], $params[self::TO]);
    }

    public function getFromAndToQueryAttribute() : string
    {
        return Common::CREATED_AT;
    }

    /**
     * Returns sort parameter value for ES request.
     * By default the sorting is on score followed by created_at of the document.
     *
     * @return array
     */
    public function getSortParameter(): array
    {
        return [
            Es::_SCORE => [
                Es::ORDER => Es::DESC,
            ],
            Common::CREATED_AT => [
                Es::ORDER => Es::DESC,
            ],
        ];
    }

    // Helper methods

    public function getTermQuery(string $field, string $value): array
    {
        return [Es::TERM => [$field => [Es::VALUE => $value]]];
    }

    public function getExistsQueryForField(string $field): array
    {
        return [Es::EXISTS => [Es::FIELD => $field]];
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
        $query[Es::BOOLQ][Es::SHOULD][] = $clause;
    }

    public function addMust(array & $query, array $clause)
    {
        $query[Es::BOOLQ][Es::MUST][] = $clause;
    }

    public function addMustNot(array & $query, array $clause)
    {
        $query[Es::BOOLQ][Es::MUST_NOT][] = $clause;
    }

    public function addFilter(array & $query, array $filter)
    {
        $query[Es::BOOLQ][Es::FILTER][Es::BOOLQ][Es::MUST][] = $filter;
    }

    public function addNegativeFilter(array & $query, array $filter)
    {
        $query[Es::BOOLQ][Es::FILTER][Es::BOOLQ][Es::MUST_NOT][] = $filter;
    }

    public function addTermFilter(array & $query, string $field, $value)
    {
        $filter = [Es::TERM => [$field => [Es::VALUE => $value]]];

        $this->addFilter($query, $filter);
    }

    public function addNegativeTermFilter(array & $query, string $field, $value)
    {
        $filter = [Es::TERM => [$field => [Es::VALUE => $value]]];

        $this->addNegativeFilter($query, $filter);
    }

    public function addTermsFilter(array & $query, string $field, array $value)
    {
        $filter = [Es::TERMS => [$field => $value]];

        $this->addFilter($query, $filter);
    }

    public function addNegativeTermsFilter(array & $query, string $field, array $value)
    {
        $filter = [Es::TERMS => [$field => $value]];

        $this->addNegativeFilter($query, $filter);
    }

    public function addMatchPhrasePrefix(array & $query, string $field, string $value)
    {
        $filter = [Es::MATCH_PHRASE_PREFIX => [$field => $value]];

        $this->addFilter($query, $filter);
    }
}
