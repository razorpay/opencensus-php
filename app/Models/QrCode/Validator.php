<?php

namespace RZP\Models\QrCode;

use App;
use RZP\Base;
use RZP\Exception;
use RZP\Constants\Mode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::AMOUNT    => 'sometimes|integer|nullable',
        Entity::PROVIDER  => 'required|in:bharat_qr,upi_qr',
        Entity::REFERENCE => 'sometimes|string|custom',
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
}
