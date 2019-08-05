<?php

namespace RZP\Trace;

use App;
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

    public function __invoke(array $record)
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

    protected function addGateway(& $record)
    {
        if (isset($this->input['payment']['gateway']) === true)
        {
            $record['payment']['gateway'] = $this->input['payment']['gateway'];
        }
    }

    protected function addPaymentId(& $record)
    {
        if (isset($this->input['payment']['id']) === true)
        {
            $record['payment']['payment_id'] = $this->input['payment']['id'];
        }
    }

    protected function addTerminalId(& $record)
    {
        if (isset($this->input['terminal']['id']) === true)
        {
            $record['payment']['terminal_id'] = $this->input['terminal']['id'];
        }
    }

    protected function addRefundId(& $record)
    {
        if (isset($this->input['refund']['id']) === true)
        {
            $record['payment']['refund_id'] = $this->input['refund']['id'];
        }
    }

    protected function addCardDetails(& $record)
    {
        if (isset($this->input['card']['network']) === true)
        {
            $record['payment']['card']['network'] = $this->input['card']['network'];
            $record['payment']['card']['issuer'] = $this->input['card']['issuer'];
            $record['payment']['card']['iin'] = $this->input['card']['iin'] ?? null;
        }
    }

    protected function addPsp(& $record)
    {
        if (isset($this->input['payment']['vpa']) === true)
        {
            $record['payment']['psp'] = explode('@', $this->input['payment']['vpa'])[1];
        }
    }

    protected function addBank(& $record)
    {
        if (isset($this->input['payment']['bank']) === true)
        {
            $record['payment']['bank'] = $this->input['payment']['bank'];
        }
    }

    protected function addMerchantId(& $record)
    {
        if (isset($this->input['merchant']['id']) === true)
        {
            $record['request']['merchant_id'] = $this->input['merchant']['id'];
        }
    }

    protected function addAction(& $record)
    {
        if (isset($this->action) === true)
        {
            $record['payment']['action'] = $this->action;
        }
    }
}
