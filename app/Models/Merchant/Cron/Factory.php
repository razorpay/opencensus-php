<?php


namespace RZP\Models\Merchant\Cron;

use RZP\Exception\BadRequestValidationFailureException;

class Factory
{
    public static function getCronProcessor(array $input)
    {
        $clazz = Constants::CONFIG[$input[Constants::CRON_NAME]] ?? null;

        if(empty($clazz) === true)
        {
            throw new BadRequestValidationFailureException("invalid cron");
        }

        return new $clazz($input);
    }
}
