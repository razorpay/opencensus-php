<?php

namespace RZP\Models\Admin\Query;

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
    public function dbQuery(array $input): array
    {
        (new Validator)->validateInput('dbQuery', $input);

        $query = $input['query'];

        $mode = $this->app['rzp.mode'];

        // using slave connection
        \Database\DefaultConnection::setSlaveConnection($mode);

        $db = DB::getFacadeRoot();

        return $db->select($query);
    }
}
