<?php

namespace RZP\Models\Reminders;

use App;
use RZP\Error\ErrorCode;
use RZP\Services\Reminders;
use RZP\Exception\BadRequestException;

abstract class ReminderProcessor
{
    protected $app;

    protected $repo;

    protected $trace;

    protected $auth;

    /**
     * @var Reminders
     */

    protected $reminders;

    protected $mode;

    const PAYMENT_LINK              = 'payment_link';
    const NEGATIVE_BALANCE          = 'negative_balance';
    const TERMINAL_CREATED_WEBHOOK  = 'terminal_created_webhook';
    const UPI_AUTO_RECURRING        = 'upi_auto_recurring';
    const CARD_AUTO_RECURRING       = 'card_auto_recurring';
    const SETTLEMENTS               = 'settlements';
    const QR_CODE                   = 'qr_code';
    const COD_PAYMENT_PENDING       = 'cod_payment_pending';
    const CAPTURE_POS_PAYMENT       = 'capture_pos_payment';

    const REMINDERS_API_NAMESPACE_PROCESSORS = [
        self::PAYMENT_LINK              => 'InvoiceReminderProcessor',
        self::NEGATIVE_BALANCE          => 'NegativeBalanceReminderProcessor',
        self::TERMINAL_CREATED_WEBHOOK  => 'TerminalCreatedWebhookReminderProcessor',
        self::UPI_AUTO_RECURRING        => 'UpiAutoRecurringReminderProcessor',
        self::CARD_AUTO_RECURRING       => 'CardAutoRecurringReminderProcessor',
        self::SETTLEMENTS               => 'SettlementReminderProcessor',
        self::QR_CODE                   => 'QrCodeReminderProcessor',
        self::COD_PAYMENT_PENDING       => 'CoDPaymentPendingProcessor',
        self::CAPTURE_POS_PAYMENT       => 'CapturePosPaymentProcessor',
    ];

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        if (isset($this->app['rzp.mode']))
        {
            $this->mode = $this->app['rzp.mode'];
        }

        $this->repo = $this->app['repo'];

        $this->trace = $this->app['trace'];

        $this->reminders = $this->app['reminders'];

        $this->auth = $this->app['basicauth'];
    }

    abstract function process(string $entity, string $namespace, string $id, array $data);

    public function handleInvalidReminder()
    {
        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_REMINDER_NOT_APPLICABLE, null,
            [
                'error_code' => ErrorCode::BAD_REQUEST_REMINDER_NOT_APPLICABLE
            ]);
    }

    public static function getReminderProcessorName(string $namespace) : string
    {
        //if namespace is not provided fallback to Payment_link namespace.
        if (empty($namespace) === true)
        {
            return __NAMESPACE__ . '\\' . self::REMINDERS_API_NAMESPACE_PROCESSORS[self::PAYMENT_LINK];
        }

        return __NAMESPACE__ . '\\' . self::REMINDERS_API_NAMESPACE_PROCESSORS[$namespace];
    }
}
