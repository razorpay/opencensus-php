<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use Carbon\Carbon;
use RZP\Models\QrCode;
use RZP\Constants\Timezone;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends QrCode\Validator
{
    const MIN_CLOSE_BY_DIFF = 120;

    protected static $createRules = [
        Entity::PROVIDER     => 'required|in:bharat_qr,upi_qr',
        Entity::NAME         => 'sometimes|string',
        Entity::FIXED_AMOUNT => 'required|boolean',
        Entity::AMOUNT       => 'required_id:fixed_amount,true|integer',
        Entity::USAGE_TYPE   => 'required|in:single_use,multiple_use',
        Entity::DESCRIPTION  => 'sometimes|string|nullable',
        Entity::NOTES        => 'filled|notes',
        Entity::CUSTOMER_ID  => 'filled|string|nullable',
        Entity::CLOSE_BY     => 'filled|epoch|custom',
    ];

    public function validateCloseBy(string $attribute, int $closeBy)
    {
        $now = Carbon::now(Timezone::IST);

        $minCloseBy = $now->copy()->addSeconds(self::MIN_CLOSE_BY_DIFF);

        if ($closeBy < $minCloseBy->getTimestamp())
        {
            $message = 'close_by should be at least ' . $minCloseBy->diffForHumans($now) . ' current time';

            throw new BadRequestValidationFailureException($message);
        }
    }
}
