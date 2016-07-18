<?php

namespace RZP\Models\Terminal;

use App;
use RZP\Constants\Mode;

use RZP\Trace;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

class Selector
{
    protected static $filters = [
        Filters\TransactionFilter::class,
        Filters\MerchantFilter::class,
    ];

    protected static $sorters = [
        Sorters\CardSorter::class,
        Sorters\NetbankingSorter::class,
        Sorters\MerchantSorter::class,
    ];

    public function setup($payment, $mode)
    {
        $app = \App::getFacadeRoot();

        $this->mode = $mode;

        $this->payment = $payment;

        $this->repo = $app['repo']->terminal;

        $this->trace = $app['trace'];

        $this->merchant = $payment->merchant;

        $this->input = [
            'payment'  => $this->payment,
            'merchant' => $this->merchant,
            'mode'     => $this->mode,
        ];
    }

    public function getTerminals()
    {
        // Fetch terminals for both the current merchant and the shared Merchant
        $merchantTerminals = $this->repo->getTerminalsForMerchantAndSharedMerchant(
                                            $this->merchant->getId());

        // Fetch Shared Terminals
        $sharedTerminals = $this->repo->getAllSharedTerminals();

        $merchantTerminals = $merchantTerminals->merge($sharedTerminals);

        return $merchantTerminals;
    }

    public function select($payment, $mode, $verbose = false)
    {
        $this->setup($payment, $mode);

        $terminals = $this->getTerminals();

        // Trace available terminals before selection
        $this->traceTerminals($terminals, 'Terminals fetched from db', $verbose);

        // Terminals first filtered
        // Terminals that result in failure due to gateway are recorded and
        // removed in the next attempt
        $filteredTerminals = $terminals;

        foreach (self::$filters as $filter)
        {
            $filteredTerminals = (new $filter)->filter($filteredTerminals, $this->input, $verbose);
            $this->traceTerminals($filteredTerminals, 'Terminals after '.$filter, $verbose);
        }

        // Trace available terminals after filtration
        $this->traceTerminals($filteredTerminals, 'Terminals after filtration', $verbose);

        // Terminals next sorted
        $sortedTerminals = $filteredTerminals;

        foreach (self::$sorters as $sorter)
        {
            $sortedTerminals = (new $sorter)->sort($sortedTerminals, $this->input, $verbose);
            $this->traceTerminals($sortedTerminals, 'Terminals after '.$sorter, $verbose);
        }

        // Trace available terminals after filtration
        $this->traceTerminals($sortedTerminals, 'Terminals after sorting', $verbose);

        if ((empty($sortedTerminals)) and ($this->mode === Mode::TEST))
        {
            $terminal = $this->repo->find(Shared::SHARP_RAZORPAY_TERMINAL);
        }
        else
        {
            $terminal = $sortedTerminals[0];
        }

        $this->checkForCustomExceptions($terminal);

        // When the terminal selector has to activated.
        // uncomment the following code
        // $this->setTerminalForPayment($payment, $terminal);

        return $terminal;
    }

    protected function setTerminalForPayment($payment, $terminal = null)
    {
        if ($terminal === null)
        {
            throw new Exception\RuntimeException(
                'Terminal should not be null',
                ['payment' => $payment->toArrayAdmin()]);
        }

        $payment->terminal()->associate($terminal);

        $payment->setGateway($terminal->getGateway());
    }

    protected function traceTerminals($terminals, $msg, $verbose = false)
    {
        if (($verbose) and
            ($terminals))
        {
            $terminalIds = [];

            foreach ($terminals as $terminal)
            {
                $terminalIds[] = $terminal->getId();
            }

            $traceData = ['count' => count($terminals), 'terminals' => $terminalIds, 'msg' => $msg];

            $this->trace->info(TraceCode::TERMINAL_SELECTION, $traceData);
        }
    }

    /**
     * Custom exceptions that are to be only thrown if no terminal is available,
     * in live mode on cards.
     *
     * @param terminal $terminal Chosen terminal
     * @return void throw custom exception
     */
    protected function checkForCustomExceptions($terminal)
    {
        if (($terminal === null) and
            ($this->input['mode'] === Mode::LIVE) and
            ($this->input['method'] === Method::CARD))
        {
            $network = $this->input['payment']->card->getNetworkCode();
            // Check for partially supported networks on live
            $networks = Gateway::$partiallySupportedCardNetworks;

            if (in_array($network, $networks))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED);
            }
        }

    }
}
