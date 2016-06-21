<?php

namespace Models\Terminal;

class Selector
{

    protected static $filters = [

    ];

    protected static $sorters = [

    ];

    public function setup($payment, $mode)
    {
        $this->mode = $mode;

        $this->payment = $payment;

        $this->repo = (new Repository);

        $this->merchant = $payment->merchant;

    }

    public function getTerminals()
    {
        // Fetch Live terminals for both the current merchant and the shared Merchant
        $merchantTerminals = $this->repo->
            getLiveTerminalsForMerchantAndSharedMerchant($this->merchant->getId());

        return $merchantTerminals;
    }

    public function select($payment, $mode)
    {
        $this->setup($payment, $mode);

        $terminals = $this->getTerminals();


        foreach (self::$filters as $filter)
        {

        }


        foreach (self::$sorters as $sorter)
        {

        }

    }


}
