<?php

namespace Models\Terminal;

class Selector
{
    public function setup($payment, $mode)
    {
        $this->mode = $mode;

        $this->payment = $payment;

        $this->repo = (new Repository);

        $this->merchant = $payment->merchant;

    }

    public function getTerminals()
    {
        $merchantTerminals = $this->repo->getLiveTerminalsByMerchantId($this->merchant->getId());

        $sharedTerminals = $this->getSharedTerminalsOnCommonAccount();

        $allTerminals = [$merchantTerminals, $sharedTerminals];

        return $allTerminals;
    }

    public function select($payment, $mode)
    {
        $this->setup($payment, $mode);

        $terminals = $this->getTerminals();

    }




}
