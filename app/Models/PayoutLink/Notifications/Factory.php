<?php

namespace RZP\Models\PayoutLink\Notifications;

use RZP\Error\ErrorCode;
use RZP\Models\PayoutLink\Entity;
use RZP\Exception\BadRequestException;

class Factory
{
    /**
     * NOTE : Ensure that the returning class implements the notify() method!
     *
     * @param $type
     * @param Entity $payoutLink
     * @return SendLink|Success|Failed
     * @throws BadRequestException
     */
    public static function getNotifier($type, Entity $payoutLink)
    {
        if (Type::isValidType($type) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_PAYOUT_LINK_NOTIFICATION_TYPE,
                                          null,
                                          [
                                              'notification_type' => $type,
                                              'payout_link_id'    => $payoutLink->getPublicId()
                                          ]);
        }

        switch ($type)
        {
            case Type::SEND_LINK:

                return (new SendLink($payoutLink));

            case Type::PAYOUT_LINK_PROCESSING_FAILED:

                return (new Failed($payoutLink));

            case Type::PAYOUT_LINK_PROCESSING_SUCCESSFUL:

                return (new Success($payoutLink));
        }
    }
}
