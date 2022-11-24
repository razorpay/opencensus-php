<?php

namespace RZP\Models\Transaction;

use Mail;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Webhook\Event;

class Notifier extends Base\Core
{
    /**
     * @var Entity
     */
    protected $txn;

    /**
     * @var \RZP\Models\BankTransfer\Entity|
     *      \RZP\Models\Payout\Entity
     */
    protected $source;

    /**
     * @var \RZP\Models\Merchant\Entity
     */
    protected $merchant;

    /**
     * Same transaction can be notified basis multiple events(of source) multiple times.
     * E.g. when a source payout of a transaction is created, processed & reversed.
     *
     * @var string|null
     */
    protected $event;

    public function __construct(Entity $txn, string $event = Event::TRANSACTION_CREATED)
    {
        parent::__construct();

        $this->txn      = $txn;
        $this->source   = $txn->source;
        $this->merchant = $txn->merchant;
        $this->event    = $event;
    }

    /**
     * Notifies transaction creation event.
     */
    public function notify()
    {
        $this->notifyViaSms();
        $this->notifyViaEmail();
    }

    protected function notifyViaSms()
    {
        // Note: Ensure template file for sms exists in raven. Refer getSmsRequestPayload() method.

        if ($this->source === null or (method_exists($this->source, 'shouldNotifyTxnViaSms') === false) or
            ($this->source->shouldNotifyTxnViaSms() === false))
        {
            return;
        }

        try
        {
            $payload = $this->getSmsRequestPayload();

            $this->trace->info(TraceCode::TRANSACTION_CREATED_NOTIFY_VIA_SMS, $this->getTracePayload($payload));

            $this->app->sns->publish(json_encode($payload));
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);
        }
    }

    protected function notifyViaEmail()
    {
        if ($this->source === null or (method_exists($this->source, 'shouldNotifyTxnViaEmail') === false) or
            ($this->source->shouldNotifyTxnViaEmail() === false))
        {
            return;
        }

        $mailableClass = 'RZP\\Mail\\Transaction\\' . studly_case($this->source->getEntity());

        // Using getAttributes() on entity(s) here to avoid getting repeating relations(e.g. merchant exists in all)
        // and surpassing SQS message content length limit.
        $mailable = new $mailableClass(
            $this->event,
            $this->txn->accountBalance->getAttributes(),
            $this->txn->getAttributes(),
            $this->source->getAttributes(),
            $this->txn->merchant->getAttributes());

        $this->trace->info(TraceCode::TRANSACTION_CREATED_NOTIFY_VIA_EMAIL, $this->getTracePayload());

        Mail::queue($mailable);
    }

    protected function getSmsRequestPayload(): array
    {
        $receiver = $this->merchant['merchant_detail']['contact_mobile'];
        $source   = "api.{$this->mode}.transaction";
        $template = 'sms.transaction.' . $this->source->getEntity();
        $params   =  [
            'account_number' => mask_except_last4($this->txn->accountBalance->getAccountNumber()),
            'amount'         => amount_format_IN($this->txn->getAmount()),
            'created_at'     => epoch_format($this->txn->getCreatedAt()),
            'balance'        => amount_format_IN($this->txn->getBalance()),
        ];

        return compact(
            'receiver',
            'source',
            'template',
            'params');
    }

    protected function getTracePayload(array $with = []): array
    {
        return [
            'transaction_id' => $this->txn->getId(),
            'source_id'      => $this->source->getId(),
            'source_type'    => $this->source->getEntity(),
            'event'          => $this->event,
        ] + $with;
    }
}
