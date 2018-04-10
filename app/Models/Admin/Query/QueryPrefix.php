<?php

namespace RZP\Models\Admin\Query;

class QueryPrefix
{
    const EXPLAIN           = 'explain';
    const SHOW_CREATE_TABLE = 'show create table';
    const SHOW_INDEXES_FROM = 'show indexes from';

    // Allowed Query Prefixes
    const ALLOWED_QUERY_PREFIXES = [
        self::EXPLAIN,
        self::SHOW_CREATE_TABLE,
        self::SHOW_INDEXES_FROM,
    ];
}
