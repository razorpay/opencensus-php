<?php

namespace RZP\Models\QrCode;

use App;
use Carbon\Carbon;
use RZP\Base;
use RZP\Constants\Timezone;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    const MIN_CLOSE_BY_DIFF = 120;

    protected static $createRules = [
        Entity::AMOUNT              => 'sometimes|integer|nullable',
        Entity::PROVIDER            => 'required|in:bharat_qr,upi_qr',
        Entity::REFERENCE           => 'sometimes|string|custom',
        Entity::REQ_USAGE_TYPE      => 'sometimes|string|in:single_use,multiple_use',
        Entity::STATUS              => 'sometimes|string',
        Entity::DESCRIPTION         => 'sometimes|custom|nullable',
        Entity::NOTES               => 'sometimes|notes',
        Entity::CUSTOMER_ID         => 'sometimes|string|nullable',
        Entity::CLOSE_BY            => 'sometimes|epoch|custom',
        Entity::NAME                => 'sometimes|custom',
    ];

    // only used in tokenizing mpans of existing qr_strings
    protected static $editRules = [
        Entity::QR_STRING       => 'required|string',
        Entity::MPANS_TOKENIZED => 'required|in:1',
    ];

    protected static $tokenizeExistingQrStringMpansRules = [
        'count'   =>  'sometimes|integer',
    ];

    protected function validateReference($attribute, $value)
    {
        $app = App::getFacadeRoot();

        $mode = $app['rzp.mode'];

        //
        // reference should not be sent when merchant is
        // creating virtual account. In that case it will be
        // always be equal to id. In case of bharat qr make payment
        // if we are creating a virtual account , reference is set
        // equal to reference received from bank. That route is direct
        // in live while it is private in test mode.
        //
        if (($mode === Mode::LIVE) and
            ($app['basicauth']->isPrivateAuth() === true))
        {
            throw new Exception\BadRequestValidationFailureException(
                'reference is/are not required and should not be sent');
        }
    }

    public function validateDescription($attribute, $value)
    {
        $merchant = app('basicauth')->getMerchant();

        if ($merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::UPIQR_V1_HDFC) === true)
        {
            if (is_valid_utf8($value) === false)
            {
                $message = 'Only plain text characters are allowed';

                throw new BadRequestValidationFailureException($message);
            }
        }
    }

    public function validateCloseBy(string $attribute, int $closeBy)
    {
        $now = Carbon::now(Timezone::IST);

        $minCloseBy = $now->copy()->addSeconds(self::MIN_CLOSE_BY_DIFF);

        $merchant = app('basicauth')->getMerchant();

        if ($merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::UPIQR_V1_HDFC) === true)
        {
            if ($closeBy < $minCloseBy->getTimestamp())
            {
                $message = 'close_by should be at least ' . $minCloseBy->diffForHumans($now) . ' current time';

                throw new BadRequestValidationFailureException($message);
            }
        }
    }

    public function validateName($attribute, $value)
    {
        $merchant = app('basicauth')->getMerchant();

        if ($merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::UPIQR_V1_HDFC) === true)
        {
            if (is_valid_utf8($value) === false)
            {
                $message = 'Only plain text characters are allowed';

                throw new BadRequestValidationFailureException($message);
            }
        }
    }
}
