<?php


namespace RZP\Models\Merchant\Cron;

use RZP\Exception\BadRequestValidationFailureException;

class Factory
{
    public static function getCronProcessor(array $input)
    {
        $cronName = $input[Constants::CRON_NAME];

        $clazz = Constants::CONFIG[$cronName] ?? null;

        if(empty($clazz) === true)
        {
            throw new BadRequestValidationFailureException("invalid cron");
        }

        return new $clazz($input);
    }
}
