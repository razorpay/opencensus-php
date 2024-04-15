<?php

namespace RZP\Trace;

use App;
use Monolog\LogRecord;
use Request;

class GatewayTraceProcessor
{
    protected $action;

    protected $input;

    public function setInputAction($input, $action)
    {
        $this->input = $input;

        $this->action = $action;
    }

    public function getInputAction()
    {
        return [$this->input, $this->action];
    }

    public function resetInputAction()
    {
        $this->input = $this->action = null;
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        if (isset($this->input) === false)
        {
            return $record;
        }

        $this->addGateway($record);

        $this->addPaymentId($record);

        $this->addTerminalId($record);

        $this->addRefundId($record);

        $this->addCardDetails($record);

        $this->addPsp($record);

        $this->addBank($record);

        $this->addMerchantId($record);

        $this->addAction($record);

        return $record;
    }

    protected function addGateway(LogRecord &$record)
    {
        if (isset($this->input['payment']['gateway']) === true)
        {
            $record['extra']['payment']['gateway'] = $this->input['payment']['gateway'];
        }
    }

    protected function addPaymentId(LogRecord &$record)
    {
        if (isset($this->input['payment']['id']) === true)
        {
            $record['extra']['payment']['payment_id'] = $this->input['payment']['id'];
        }
    }

    protected function addTerminalId(LogRecord &$record)
    {
        if (isset($this->input['terminal']['id']) === true)
        {
            $record['extra']['payment']['terminal_id'] = $this->input['terminal']['id'];
        }
    }

    protected function addRefundId(LogRecord &$record)
    {
        if (isset($this->input['refund']['id']) === true)
        {
            $record['extra']['payment']['refund_id'] = $this->input['refund']['id'];
        }
    }

    protected function addCardDetails(LogRecord &$record)
    {
        if (isset($this->input['card']['network']) === true)
        {
            $record['extra']['payment']['card']['network'] = $this->input['card']['network'];
            $record['extra']['payment']['card']['issuer'] = $this->input['card']['issuer'];
            $record['extra']['payment']['card']['iin'] = $this->input['card']['iin'] ?? null;
        }
    }

    protected function addPsp(LogRecord &$record)
    {
        if (isset($this->input['payment']['vpa']) === true)
        {
            $record['extra']['payment']['psp'] = explode('@', $this->input['payment']['vpa'])[1];
        }
    }

    protected function addBank(LogRecord &$record)
    {
        if (isset($this->input['payment']['bank']) === true)
        {
            $record['extra']['payment']['bank'] = $this->input['payment']['bank'];
        }
    }

    protected function addMerchantId(LogRecord &$record)
    {
        if (isset($this->input['merchant']['id']) === true)
        {
            $record['extra']['request']['merchant_id'] = $this->input['merchant']['id'];
        }
    }

    protected function addAction(LogRecord &$record)
    {
        if (isset($this->action) === true)
        {
            $record['extra']['payment']['action'] = $this->action;
        }
    }
}
