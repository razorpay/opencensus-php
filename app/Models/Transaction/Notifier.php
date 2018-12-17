<?php

namespace RZP\Models\Transaction;

use Mail;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity as E;

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

    public function __construct(Entity $txn)
    {
        parent::__construct();

        $this->txn      = $txn;
        $this->source   = $txn->source;
        $this->merchant = $txn->merchant;
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
        if ((method_exists($this->source, 'shouldNotifyTxnViaSms') === false) or
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
        if ((method_exists($this->source, 'shouldNotifyTxnViaEmail') === false) or
            ($this->source->shouldNotifyTxnViaEmail() === false))
        {
            return;
        }

        $mailableClass = 'RZP\\Mail\\Transaction\\' . studly_case($this->source->getEntity());

        $mailable = new $mailableClass(
            $this->txn->balance()->first()->getAttributes(),
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
            'account_number' => mask_except_last4($this->txn->balance()->first()->getAccountNumber()),
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
        ] + $with;
    }
}
