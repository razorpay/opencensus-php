<?php

namespace RZP\Models\CardMandate\CardMandateNotification;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\CardMandate;

/**
 * @property Merchant\Entity    $merchant
 * @property Payment\Entity     $payment
 * @property CardMandate\Entity $cardMandate
 */
class Entity extends Base\PublicEntity
{
    const CARD_MANDATE_ID = 'card_mandate_id';
    const PAYMENT_ID      = 'payment_id';
    const NOTIFICATION_ID = 'notification_id';
    const REMINDER_ID     = 'reminder_id';
    const STATUS          = 'status';
    const NOTIFIED_AT     = 'notified_at';
    const VERIFIED_AT     = 'verified_at';

    protected $entity = 'card_mandate_notification';

    protected $generateIdOnCreate = true;

    protected $fillable = [
    ];

    protected $public = [
        self::ID,
        self::CARD_MANDATE_ID,
        self::STATUS,
        self::NOTIFICATION_ID,
        self::NOTIFIED_AT,
        self::VERIFIED_AT,
        self::CREATED_AT,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::CARD_MANDATE_ID,
        self::STATUS,
        self::NOTIFICATION_ID,
        self::REMINDER_ID,
        self::NOTIFIED_AT,
        self::VERIFIED_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $defaults = [
        self::STATUS => Status::CREATED,
    ];

    public function setStatus($status)
    {
        Status::checkStatus($status);

        $previousState = $this->getStatus();

        Status::checkStatusChange($previousState, $status);

        $this->setAttribute(self::STATUS, $status);
    }

    public function setNotificationId($id)
    {
        $this->setAttribute(self::NOTIFICATION_ID, $id);
    }

    public function setReminderId($id)
    {
        $this->setAttribute(self::REMINDER_ID, $id);
    }

    public function setNotifiedAt($timestamp)
    {
        $this->setAttribute(self::NOTIFIED_AT, $timestamp);
    }

    public function setVerifiedAt($timestamp)
    {
        $this->setAttribute(self::VERIFIED_AT, $timestamp);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getNotifiedAt()
    {
        return $this->getAttribute(self::NOTIFIED_AT);
    }

    public function getNotificationId()
    {
        return $this->getAttribute(self::NOTIFICATION_ID);
    }

    public function getRemindAt()
    {
        $notifiedAt = $this->getNotifiedAt();

        if ($notifiedAt === null){
            return null;
        }

        $time = Carbon::createFromTimestamp($notifiedAt);

        $time->addDay();

        return $time->timestamp;
    }

    // Relations
    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function cardMandate()
    {
        return $this->belongsTo(CardMandate\Entity::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment\Entity::class);
    }
}
