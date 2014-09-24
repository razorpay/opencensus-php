<?php

namespace Models\Payment;

use Dashboard;
use EE\Exception;
use EE\Error\ErrorCode;
use Models\Gateway;
use Models\Merchant;
use Models\Terminal;
use Models\Payment;
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

    protected $mode;

    protected $repo;

    public function __construct(
        Merchant\Entity $merchant,
        Payment\Core $core,
        Trace $trace,
        $mode)
    {
        $this->merchant = $merchant;

        $this->core = $core;

        $this->trace = $trace;

        $this->mode = $mode;

        $this->checkMerchantPermissions();

        $this->repo = new Payment\Repository;

        $this->terminal = $this->getTerminal();
    }

    public static function create($action, $bindings)
    {
        $class = __NAMESPACE__ . '\\' . ucfirst($action);

        if ($action === self::REFUND)
        {
            $class .= '\Process';
        }

        return new $class(
            $bindings['merchant'],
            $bindings['core'],
            $bindings['trace'],
            $bindings['mode']);
    }

    protected function checkMerchantPermissions()
    {
        $merchant = $this->merchant;

        $mode = $this->mode;

        if ($mode === 'test')
            return;

        if ($merchant->isActivated() === false)
        {
            throw new Exception\LogicException(
                'A non-activated merchant is making live request. Blasphemy!');
        }

        if ($merchant->isLive() === false)
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_LIVE_ACTION_DENIED);
        }
    }

    protected function trace($traceCode, $level = Trace::INFO)
    {
        $data = $this->txn->toArrayTraceRelevant();

        $this->trace->addRecord($level, $traceCode, $data);
    }

    protected function updatePaymentFailed($error, $traceCode)
    {
        $code = $error->getPublicErrorCode();

        $desc = $error->getDescription();

        $txn = $this->txn;

        $txn->setStatus(Payment\Status::FAILED);

        $txn->setError($code, $desc);

        $txn->save();

        $this->tracePaymentFailed($error, $traceCode);
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
        $terminal = $this->terminal->toArrayWithPassword();

        return Gateway::call($action, $input, $this->mode, $terminal);
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

    protected function tracePaymentFailed($error, $traceCode)
    {
        $traceData = array_merge(
                        $this->txn->toArrayTraceRelevant(),
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

        Dashboard\Payment::getInstance()->queueRecord($data);
    }

    protected function retrieve($id)
    {
        $this->txn = $this->core->retrieveByIdAndMerchantId(
                                    $id, $this->merchant->getKey());

        return $this->txn;
    }
}
