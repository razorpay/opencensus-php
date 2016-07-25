<?php

namespace RZP\Models\Terminal;

use App;
use RZP\Constants\Mode;
use RZP\Models\Card\Network;
use RZP\Models\Payment;

use RZP\Trace;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

class Selector
{
    protected $mode;
    protected $payment;
    protected $repo;
    protected $trace;
    protected $merchant;
    protected $input;

    protected static $filters = [
        Filters\TransactionFilter::class,
        Filters\MerchantFilter::class,
    ];

    protected static $sorters = [
        Sorters\CardSorter::class,
        Sorters\NetbankingSorter::class,
        Sorters\MerchantSorter::class,
    ];

    public function __construct($payment, $mode)
    {
        $app = App::getFacadeRoot();

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

    public function select($options = [], $verbose = false)
    {
        $terminals = $this->getTerminals();

        $this->traceTerminals($terminals, 'Terminals fetched from db', $verbose);

        //
        // Initially, the terminals are run through a filter class, which removes
        // the terminals which do not match the filters. For further iterations, the
        // filtered list of terminals is used to further filter upon using the other
        // filter classes.
        //
        $filteredTerminals = $terminals;

        foreach (self::$filters as $filter)
        {
            $filteredTerminals = (new $filter)->filter($filteredTerminals, $this->input, $verbose);
            $this->traceTerminals($filteredTerminals, 'Terminals after ' . $filter, $verbose);
        }

        $this->traceTerminals($filteredTerminals, 'Terminals after filtration', $verbose);

        //
        // Sorting is done on the final list of filtered terminals.
        // The sorting is run for each of the sorting classes.
        //
        $sortedTerminals = $filteredTerminals;

        foreach (self::$sorters as $sorter)
        {
            $sortedTerminals = (new $sorter)->sort($sortedTerminals, $this->input, $verbose);
            $this->traceTerminals($sortedTerminals, 'Terminals after ' . $sorter, $verbose);
        }

        $this->traceTerminals($sortedTerminals, 'Terminals after sorting', $verbose);

        $terminal = null;

        if ((empty($sortedTerminals) === true) or ($sortedTerminals->count() === 0))
        {
            if ($this->mode === Mode::TEST)
            {
                // The current list of terminals which were retrieved earlier does
                // not contain the sharp terminal and hence, making a call to DB.
                $terminal = $this->repo->find(Shared::SHARP_RAZORPAY_TERMINAL);
            }
            else
            {
                throw new Exception\RuntimeException(
                    'No terminal found.',
                    ['payment' => $this->payment->toArrayAdmin()]);
            }
        }
        else
        {
            $terminal = $sortedTerminals->first();
        }

        if (isset($options['chance']))
        {
            $terminal = (new Binning)->select($terminal, $options['chance'], $this->input, $terminals);
        }

        $this->setTerminalForPayment($this->payment, $terminal);

        return $terminal;
    }

    protected function getHdfcSharedTerminalIfMaestro($terminals)
    {
        $method = $this->payment->getMethod();

        if ($method !== Payment\Method::CARD)
        {
            return null;
        }

        $cardNetwork = $this->payment->card->getNetworkCode();

        if ($cardNetwork !== Network::MAES)
        {
            return null;
        }

        $sharedHdfcTerminal = $terminals->find(Shared::HDFC_RAZORPAY_TERMINAL);

        return $sharedHdfcTerminal;
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
     * @param $terminal
     * @throws Exception\BadRequestException
     */
    protected function checkForCustomExceptions($terminal)
    {
        if (($terminal === null) and
            ($this->mode === Mode::LIVE) and
            ($this->payment->getMethod() === Payment\Method::CARD))
        {
            $network = $this->payment->card->getNetworkCode();
            // Check for partially supported networks on live
            $partiallySupportedCardNetworks = Payment\Gateway::$partiallySupportedCardNetworks;

            if (in_array($network, $partiallySupportedCardNetworks))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED);
            }
            // TODO: What if it's not in that list and terminal is null? Shouldn't we throw an exception?
            // What if terminal is null and method is something else?
        }
    }
}
