<?php

namespace Models\Transaction;

use EE\Exception\BadRequestException;

use Models\Base;
use Models\Transaction;

use Trace\Trace;
use Trace\TraceCode;

class Service extends Base\Service
{
    protected $txn;
    protected $mode;
    protected $trace;
    protected $merchant;

    public function __construct($merchant = null, $mode)
    {
        parent::__construct();

        $this->core = new Transaction\Core();
        $this->mode = $mode;
        $this->trace = Trace::getInstance();
        $this->merchant = $merchant;
    }

    /**
     * Processes a transaction.
     */
    public function process(array $input)
    {
        $data = $this->getActionInstance(Transaction\Action::AUTHORIZE)
                     ->process($input);

        //
        // The returned value could be either Transaction
        // model or an array containing callback data.
        // We convert txn model to array
        // if it's a txn model
        //
        if ($data instanceof Transaction\Entity)
            $data = $data->toArrayPublic();

        return $data;
    }

    /**
     * Refunds a transaction
     *
     * @param  string   $id
     *
     * @return Transaction\Entity
     */
    public function refund($id)
    {
        $txn = $this->getActionInstance(Transaction\Action::REFUND)
                     ->process($id);

        return $txn->toArrayPublic();
    }

    /**
     * Captures a transaction
     *
     * @param  string   $id
     *
     * @return Transaction\Entity
     */
    public function capture($id, $input)
    {
        $txn = $this->getActionInstance(Transaction\Action::CAPTURE)
                    ->process($id, $input);

        return $txn->toArrayPublic();
    }

    /**
     * After card enroll, bank redirects to us
     * and we send it to gateway for further
     * processing (auth).
     * Returning from this function implies
     * 'auth' is successful.
     *
     * @param  array  $input Contains fields provided
     *                       by bank
     *
     * @return Transaciton\Entity
     */
    public function bankAcsCallback($id, array $input)
    {
        $txn = $this->getActionInstance(Transaction\Action::AUTHORIZE)
                    ->callback($id, $input);

        return $txn->toArrayPublic();
    }

    public function retrieveMultiple(array $input)
    {
        $txns = (new Transaction\Repository)->fetch($input);

        $count = count($txns);

        $collection = $txns->transform(function($txn)
        {
            return $txn->toArrayPublic();
        });

        $txns = $collection->all();

        return array('count' => $count, 'data' => $txns);
    }

    public function retrieveByIdAndMerchantId($id, $merchantId)
    {
        $txn = $this->core->retrieveByIdAndMerchantId($id, $merchantId);

        return $txn->toArrayPublic();
    }

    public function reconcile($gateway, $input)
    {
        foreach ($input as $row)
        {
            $entry = (new Gateway\Manager)->mprTranslate($row, $ledger_id);

            $this->reconcileEntry($entry);
        }
    }

    public function reconcileEntry($input)
    {

    }

    protected function getActionInstance($action)
    {
        $bindings = array(
            'merchant'  => $this->merchant,
            'core'      => $this->core,
            'trace'     => $this->trace,
            'mode'      => $this->mode);

        $class = 'Models\Transaction\\'.ucfirst($action);

        return new $class($this->merchant, $this->core, $this->trace, $this->mode);
    }
}
