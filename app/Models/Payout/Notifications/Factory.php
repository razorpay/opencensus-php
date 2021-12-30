<?php

namespace RZP\Models\Payout\Notifications;

use RZP\Error\ErrorCode;
use RZP\Models\Payout\Entity;
use RZP\Exception\BadRequestException;

class Factory
{
    /**
     * NOTE : Ensure that the returning class implements the notify() method!
     *
     * @param $type
     * @param Entity $payout
     * @return AutoRejected|Failed
     * @throws BadRequestException
     */
    public static function getNotifier($type, Entity $payout)
    {
        if (Type::isValidType($type) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_PAYOUT_NOTIFICATION_TYPE,
                                          null,
                                          [
                                              'notification_type' => $type,
                                              'payout_id'         => $payout->getId()
                                          ]);
        }

        switch ($type)
        {
            case Type::PAYOUT_AUTO_REJECTED:
                return (new AutoRejected($payout));

            case Type::PAYOUT_FAILED:
                return (new Failed($payout));

            case Type::PAYOUT_PROCESSED_CONTACT_COMMUNICATION:
                return (new PayoutProcessedContactCommunication($payout));
        }
    }
}
