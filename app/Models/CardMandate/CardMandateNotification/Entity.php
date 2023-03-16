<?php

namespace RZP\Models\CardMandate\CardMandateNotification;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\CardMandate;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\CardMandate\MandateHubs;

/**
 * @property Merchant\Entity    $merchant
 * @property Payment\Entity     $payment
 * @property CardMandate\Entity $cardMandate
 */
class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const CARD_MANDATE_ID  = 'card_mandate_id';
    const PAYMENT_ID       = 'payment_id';
    const NOTIFICATION_ID  = 'notification_id';
    const REMINDER_ID      = 'reminder_id';
    const STATUS           = 'status';
    const NOTIFIED_AT      = 'notified_at';
    const VERIFIED_AT      = 'verified_at';
    const DEBIT_AT         = 'debit_at';
    const AFA_REQUIRED     = 'afa_required';
    const AFA_STATUS       = 'afa_status';
    const AFA_COMPLETED_AT = 'afa_completed_at';
    const CURRENCY         = 'currency';
    const AMOUNT           = 'amount';
    const NOTES            = 'notes';
    const PURPOSE          = 'purpose';

    protected $entity = 'card_mandate_notification';

    protected $generateIdOnCreate = true;

    protected static $sign = 'cardmn';

    protected $fillable = [
    ];

    protected $public = [
        self::ID,
        self::STATUS,
        self::NOTIFIED_AT,
        self::CREATED_AT,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::CARD_MANDATE_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::STATUS,
        self::NOTIFICATION_ID,
        self::REMINDER_ID,
        self::NOTIFIED_AT,
        self::VERIFIED_AT,
        self::AFA_REQUIRED,
        self::AFA_STATUS,
        self::AFA_COMPLETED_AT,
        self::PURPOSE,
        self::NOTES,
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

    public function setAfaRequired($isRequired)
    {
        $this->setAttribute(self::AFA_REQUIRED, $isRequired);
    }

    public function setPaymentId($paymentId)
    {
        $this->setAttribute(self::PAYMENT_ID, $paymentId);
    }

    public function setAmount($value)
    {
        $this->setAttribute(self::AMOUNT, $value);
    }

    public function setCurrency($value)
    {
        $this->setAttribute(self::CURRENCY, $value);
    }

    public function setPurpose($value)
    {
        $this->setAttribute(self::PURPOSE, $value);
    }

    public function setAfaStatus($status)
    {
        $this->setAttribute(self::AFA_STATUS, $status);
    }

    public function setAfaCompletedAt($completedAt)
    {
        $this->setAttribute(self::AFA_COMPLETED_AT, $completedAt);
    }

    public function setDebitAt($debitAt)
    {
        $this->setAttribute(self::DEBIT_AT, $debitAt);
    }

    public function setVerifiedAt($timestamp)
    {
        $this->setAttribute(self::VERIFIED_AT, $timestamp);
    }

    public function isAfaRequired()
    {
        return $this->getAttribute(self::AFA_REQUIRED);
    }

    public function getAfaStatus()
    {
        return $this->getAttribute(self::AFA_STATUS);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getNotifiedAt()
    {
        return $this->getAttribute(self::NOTIFIED_AT);
    }

    public function getNotificationId()
    {
        return $this->getAttribute(self::NOTIFICATION_ID);
    }

    public function getRemindAt($mandateHub = MandateHubs\MandateHubs::MANDATE_HQ)
    {
        $notifiedAt = $this->getNotifiedAt();

        if ($notifiedAt === null)
        {
            $notifiedAt = Carbon::now()->unix();
        }

        $time = Carbon::createFromTimestamp($notifiedAt);

        $app = \App::getFacadeRoot();

        if ($app['rzp.mode'] === Mode::TEST)
        {
            $time->addMinutes(5);
        }
        else
        {
            if ($mandateHub === MandateHubs\MandateHubs::BILLDESK_SIHUB or $mandateHub === MandateHubs\MandateHubs::MANDATE_HQ)
            {
                $time->addDay()->addHours(12)->addMinutes(5);
            }
            else {
                $time->addDay()->addMinutes(5);
            }
        }

        return $time->timestamp;
    }

    public function getReminderId()
    {
        return $this->getAttribute(self::REMINDER_ID);
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
