<?php

namespace RZP\Services\Pagination;

use Carbon\Carbon;

use RZP\Base;
use RZP\Constants\Timezone;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    const PAGINATION_PARAMETERS_FROM_REDIS = 'pagination_parameters_from_redis';

    protected static $paginationParametersFromRedisRules = [
        Entity::START_TIME                      => 'required|int',
        Entity::END_TIME                        => 'required|int',
        Entity::DURATION                        => 'required|int',
        Entity::LIMIT                           => 'required|int|min:100|max:10000',
        Entity::WHITELIST_MERCHANT_IDS          => 'sometimes|filled|array',
        Entity::WHITELIST_MERCHANT_IDS . '.*'   => 'required|alpha_num|size:14',
        Entity::BLACKLIST_MERCHANT_IDS          => 'sometimes|filled|array',
        Entity::BLACKLIST_MERCHANT_IDS . '.*'   => 'required|alpha_num|size:14',
        Entity::JOB_COMPLETED                   => 'sometimes|boolean'
    ];

    protected static $paginationParametersFromRedisValidators = [
        'paginationParameter'
    ];

    protected function validatePaginationParameter($input)
    {
        $startTime = Carbon::createFromTimestamp((int) $input[Entity::START_TIME], Timezone::IST);
        $endTime = Carbon::createFromTimestamp((int) $input[Entity::END_TIME], Timezone::IST);

        if ($endTime->greaterThanOrEqualTo($startTime) === false)
        {
            throw new BadRequestValidationFailureException(
                'Given start time is greater than or equal to end time.',
                null,
                [
                    'input' => $input,
                ]);
        }
    }
}
