<?php

namespace RZP\Models\Notification;

use Carbon\Carbon;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Exception\BadRequestException;

class Validator extends Base\Validator
{
    const MIN_PAYMENT_AFTER_DIFF = 25;

    protected static $createRules = [
        Entity::ORDER_ID                => 'required|string',
        Entity::TOKEN_ID                => 'required|string',
        Entity::MERCHANT_ID             => 'required|string',
        Entity::PAYMENT_AFTER           => 'sometimes|int',
    ];

    protected static $createValidators = [
        'payment_after',
    ];


    public function validatePaymentAfter($input)
    {
        $paymentAfter = $input[Entity::PAYMENT_AFTER];
        $currentTime = Carbon::now(Timezone::IST)->addHours(self::MIN_PAYMENT_AFTER_DIFF);

        if ($paymentAfter < $currentTime)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_REQUEST);
        }
    }

    /**
     * @param Entity $notification
     *
     * @throws BadRequestException
     */
    public function validateOrderNotification(Entity $notification)
    {
        //notification status should be delivered
        if($notification->getStatus() !== Status::DELIVERED)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_MANDATE_NOTIFICATION_NOT_SENT);
        }

        $currentTime = Carbon::now(Timezone::IST);

        $notificationDeliveredTime = Carbon::createFromTimestamp($notification->getDeliveredAt(), Timezone::IST);

        $paymentAfterTime = Carbon::createFromTimestamp($notification->getPaymentAfter(), Timezone::IST);

        //debit should not be attempted before 25 hours of notification delivered
        if($currentTime->diffInHours($notificationDeliveredTime) < 25)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MANDATE_PAYMENT_ATTEMPTED_BEFORE_MIN_GAP_OF_NOTIFICATION);
        }

        //debit should not be attempted before payment_after
        if($currentTime < $paymentAfterTime)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MANDATE_PROMISED_DEBIT_DATE_NOT_HONOURED);
        }
    }
}
