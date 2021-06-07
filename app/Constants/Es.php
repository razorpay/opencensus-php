<?php

namespace RZP\Constants;

/**
 * Keeps ES related constants.
 */
final class Es
{
    // ---------- Various query constants ----------

    const LT                   = 'lt';
    const GT                   = 'gt';
    const LTE                  = 'lte';
    const GTE                  = 'gte';
    const _ID                  = '_id';
    const ASC                  = 'asc';
    const HITS                 = 'hits';
    const DESC                 = 'desc';
    const MUST                 = 'must';
    const TERM                 = 'term';
    const TYPE                 = 'type';
    const QUERY                = 'query';
    const BOOST                = 'boost';
    const SCORE                = '_score';
    const BOOLQ                = 'bool';
    const MATCH                = 'match';
    const TERMS                = 'terms';
    const FIELD                = 'field';
    const VALUE                = 'value';
    const RANGE                = 'range';
    const ORDER                = 'order';
    const _SCORE               = '_score';
    const SCROLL               = 'scroll';
    const FILTER               = 'filter';
    const FIELDS               = 'fields';
    const EXISTS               = 'exists';
    const SHOULD               = 'should';
    const LENIENT              = 'lenient';
    const _SOURCE              = '_source';
    const WILDCARD             = 'wildcard';
    const MUST_NOT             = 'must_not';
    const SCROLL_ID            = 'scroll_id';
    const _SCROLL_ID           = '_scroll_id';
    const MULTI_MATCH          = 'multi_match';
    const BEST_FIELDS          = 'best_fields';
    const MATCH_PHRASE_PREFIX  = 'match_phrase_prefix';
    const MINIMUM_SHOULD_MATCH = 'minimum_should_match';
}
