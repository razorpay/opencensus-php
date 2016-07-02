<?php

namespace Models\Terminal;

use Constants\Mode;
use EE\Exception;
use EE\Error\ErrorCode;

class Selector
{

    protected static $filters = [
        Filters\TransactionFilter::class,
        Filters\MerchantFilter::class,
    ];

    protected static $sorters = [
        Sorters\NetbankingSorter::class,
        // Sorters\MerchantSorter::class,
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
            'mode'     => $this->mode,
        ];
    }

    public function getTerminals()
    {
        // Fetch Live terminals for both the current merchant and the shared Merchant
        $merchantTerminals = $this->repo->
            getLiveTerminalsForMerchantAndSharedMerchant($this->merchant->getId());

        // Fetch Shared Terminals
        $sharedTerminals = $this->repo->getAllSharedTerminals();

        $merchantTerminals = $merchantTerminals->merge($sharedTerminals);

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
            $sortedTerminals = (new $sorter)->sort($sortedTerminals, $this->input);
        }

        if ((empty($sortedTerminals)) and ($this->mode === Mode::TEST))
        {
            $terminal = $this->repo->find(Shared::SHARP_RAZORPAY_TERMINAL);
        }
        else
        {
            $terminal = $sortedTerminals[0];
        }

        if ($terminal === null)
        {
            throw new Exception\RuntimeException(
                'Terminal should not be null',
                ['payment' => $payment->toArrayAdmin()]);
        }

        $payment->terminal()->associate($terminal);

        $payment->setGateway($terminal->getGateway());

        return $terminal;

    }
}
