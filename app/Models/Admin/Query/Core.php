<?php

namespace RZP\Models\Admin\Query;

use RZP\Models\Base;
use RZP\Error\ErrorCode;
use Illuminate\Support\Facades\DB;
use RZP\Exception\BadRequestException;
use Illuminate\Database\QueryException;

class Core extends Base\Core
{
    /**
     * This function is used to run explain/show on a query.
     * The query with prefixes `explain`, `show create table`,
     * `show indexes from` are only allowed.
     * @param array $input
     *
     * @return array
     *
     * @throws BadRequestException
     */
    public function dbMetaDataQuery(array $input): array
    {
        (new Validator)->validateInput('dbMetaDataQuery', $input);

        $query = $input['query'];

        $db = DB::getFacadeRoot();

        try
        {
            $result = $db->select($query);
        }
        catch (QueryException $e)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_QUERY,
                null,
                [
                    'input' => $input,
                ]);
        }

        return $result;
    }
}
