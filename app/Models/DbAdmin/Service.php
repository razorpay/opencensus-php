<?php

namespace RZP\Models\DbAdmin;

use RZP\Models\Base;
use Illuminate\Support\Facades\DB;

class Service extends Base\Service
{
    /**
     * This function is used to run explain/show on a query.
     * The query with prefixes `explain select`, `show create table`,
     * `show indexes from` are only allowed.
     * @param array $input
     *
     * @return array
     */
    public function explainQuery(array $input): array
    {
        (new Validator)->validateInput('explainQuery', $input);

        $mode = $input['mode'];

        $query = $input['query'];

        // using slave connection
        \Database\DefaultConnection::setSlaveConnection($mode);

        $db = DB::getFacadeRoot();

        return $db->select($query);
    }
}
