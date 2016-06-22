<?php

namespace Models\Terminal;

class Selector
{

    protected static $filters = [
        Filters\TransactionFilter::class,
    ];

    protected static $sorters = [

    ];

    public function setup($payment, $mode)
    {
        $this->mode = $mode;

        $this->payment = $payment;

        $this->repo = (new Repository);

        $this->merchant = $payment->merchant;

        $this->input = [
            'payment'  => $this->payment,
            'merchant' => $this->merchant,

        ];
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

        // Terminals first filtered
        // Terminals that result in failure due to gateway are recorded and
        // removed in the next attempt
        $filteredTerminals = $terminals;

        foreach (self::$filters as $filter)
        {
            $filteredTerminals = (new $filter)->filter($filteredTerminals, $this->input);
        }
        // Terminals next sorted
        $sortedTerminals = $filteredTerminals;

        foreach (self::$sorters as $sorter)
        {
            $sortedTerminals = $sorter->sort($input, $sortedTerminals);
        }

    }


}
