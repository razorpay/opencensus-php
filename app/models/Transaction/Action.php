<?php

namespace Models\Transaction;

use Dashboard;
use Models\Gateway;
use Models\Merchant;
use Models\Terminal;
use Models\Transaction;
use Trace\Trace;

class Action
{
    const AUTHORIZE = 'authorize';
    const CALLBACK = 'callback';
    const CAPTURE = 'capture';
    const REFUND = 'refund';

    protected $merchant;

    protected $core;

    protected $trace;

    protected $txn;

    protected $repo;

    public function __construct(
        Merchant\Entity $merchant,
        Transaction\Core $core,
        Trace $trace)
    {
        $this->merchant = $merchant;

        $this->core = $core;

        $this->trace = $trace;

        $this->repo = new Transaction\Repository;

        $this->terminal = $this->getTerminal();
    }

    protected function trace($traceCode, $level = Trace::INFO)
    {
        $data = $this->txn->toArrayTraceRelevant();

        $this->trace->addRecord($level, $traceCode, $data);
    }

    protected function updateTransactionFailed($error, $traceCode)
    {
        $code = $error->getPublicErrorCode();

        $desc = $error->getDescription();

        $txn = $this->txn;

        $txn->setStatus(Transaction\Status::FAILED);

        $txn->setError($code, $desc);

        $txn->save();

        $this->traceTransactionFailed($error, $traceCode);
    }

    /**
     * Responsible for calling the gateway function
     *
     * @param  string $action refund/capture etc.
     * @param  array  $input  Relevant input for the corresponding
     *                        action
     *
     * @return array or null
     */
    protected function callGatewayFunction($action, array $input)
    {
        $input['terminal'] = $this->terminal->toArrayWithPassword();

        return Gateway::call($action, $input);
    }

    protected function getTerminal()
    {
        $repo = new Terminal\Repository;

        $terminal = $repo->getByMerchantId($this->merchant->getKey());

        if ($terminal === null)
        {
            throw new \LogicException(
                'No terminal found for merchant: ' . $this->merchant->getKey());
        }

        return $terminal;
    }

    protected function traceTransactionFailed($error, $traceCode)
    {
        $traceData = array_merge(
                        $txn->toArrayTraceRelevant(),
                        ['error' => $error->getAttributes()]);

        // Tracing
        $this->trace->error(
            $traceCode,
            $traceData);
    }

    protected function dashboardQueueRecord($txn)
    {
        $data = array_merge(
                    $txn->toArray(),
                    ['merchant_id' => $txn->getMerchantId()]);

        Dashboard\Transaction::getInstance()->queueRecord($data);
    }

    protected function retrieve($id)
    {
        $this->txn = $this->core->retrieveByIdAndMerchantId(
                                    $id, $this->merchant->getKey());

        return $this->txn;
    }
}
