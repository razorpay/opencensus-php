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
    protected $terminalRepo;
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

    public function setup($payment, $mode)
    {
        $app = App::getFacadeRoot();

        $this->mode = $mode;

        $this->payment = $payment;

        $this->terminalRepo = $app['repo']->terminal;

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
        $merchantTerminals = $this->terminalRepo->getTerminalsForMerchantAndSharedMerchant(
                                            $this->merchant->getId());

        // Fetch Shared Terminals
        $sharedTerminals = $this->terminalRepo->getAllSharedTerminals();

        $merchantTerminals = $merchantTerminals->merge($sharedTerminals);

        return $merchantTerminals;
    }

    public function select($payment, $mode, $verbose = false)
    {
        // TODO: Is there a way to use a constructor instead?
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
            $this->traceTerminals($filteredTerminals, 'Terminals after ' . $filter, $verbose);
        }

        // Trace available terminals after filtration
        $this->traceTerminals($filteredTerminals, 'Terminals after filtration', $verbose);

        // Terminals next sorted
        $sortedTerminals = $filteredTerminals;

        foreach (self::$sorters as $sorter)
        {
            $sortedTerminals = (new $sorter)->sort($sortedTerminals, $this->input, $verbose);
            $this->traceTerminals($sortedTerminals, 'Terminals after ' . $sorter, $verbose);
        }

        // Trace available terminals after sorting
        $this->traceTerminals($sortedTerminals, 'Terminals after sorting', $verbose);

        $terminal = null;

        if (empty($sortedTerminals) === true)
        {
            if ($this->mode === Mode::TEST)
            {
                // TODO: SHARP_RAZORPAY_TERMINAL won't be present in the initial list of terminals?
                $terminal = $this->terminalRepo->find(Shared::SHARP_RAZORPAY_TERMINAL);
            }
            else
            {
                // TODO: Throw an exception for not being able to find any terminal for the payment.
            }
        }
        else
        {
            $terminal = $sortedTerminals[0];
        }

        // This is a hack and should be implemented in the correct manner later.
        $hdfcMaestroSharedTerminal = $this->getHdfcSharedTerminalIfMaestro($terminals);

        if ($hdfcMaestroSharedTerminal !== null)
        {
            $terminal = $hdfcMaestroSharedTerminal;
        }

        $this->checkForCustomExceptions($terminal);

        // When the terminal selector has to activated.
        // uncomment the following code
        $this->setTerminalForPayment($payment, $terminal);

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
     * @param Entity $terminal Chosen terminal
     * @throws Exception\BadRequestException
     */
    protected function checkForCustomExceptions(Entity $terminal)
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
