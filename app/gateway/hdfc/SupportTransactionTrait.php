<?php

namespace Gateway\Hdfc;

use Gateway\Hdfc;
use Trace\Trace;
use Trace\TraceCode;
use EE\Exception;

trait SupportTransactionTrait
{
    /**
     * Forms the crux of doing support
     * transactions (capture and refund).
     *
     * @param  array    $input array containing txn
     *                         and card details
     * @param  string   $type  should be either 'capture'
     *                         or 'refund'
     * @return array
     */
    protected function supportTxn($input, $type)
    {
        $this->getModel($input['txn']['id']);

        //
        // Mark the type of support txn.
        // It will be either 'capture' or 'refund'
        //
        $this->setSupportTxnType($type);

        //
        // Fill the fields required for the txn
        //
        $this->createSupportTxnRequestFields($input);

        $this->trace(
            TRACE::DEBUG,
            TraceCode::GATEWAY_SUPPORT_REQUEST,
            $this->supportTxnRequest);

        $this->runRequestResponseFlow(
            $this->supportTxnRequest,
            $this->supportTxnResponse);

        $this->persistAfterSupportTxn('refund');

        if($this->error)
        {
            $this->throwException($this->supportTxnResponse['error']['code']);
        }
    }

    protected function setSupportTxnType($type)
    {
        Assert(($type === 'capture') or
               ($type === 'refund'));

        $this->supportTxnRequest['type'] = $type;

        $this->supportTxnResponse['type'] = $type;
    }

    /**
     * Collect all fields to be sent for
     * transaction refund/capture
     *
     * @param  array $input
     * Contains the 'txn' details
     */
    protected function createSupportTxnRequestFields($input)
    {
        $txn = $input['txn'];

        $card = $input['txn']['card'];

        $data = &$this->supportTxnRequest['data'];

        // Collect credentials
        list($data['id'], $data['password']) = static::getCredentials();

        $type = $this->supportTxnRequest['type'];

        $action = constant(__NAMESPACE__.'\Action::'.strtoupper($type));

        $data['action'] = $action;

        //
        // Convert amount from integer to decimal
        //
        $data['amt'] = $input['amount']/100;

        $data['member'] = $card['name'];

        $data['transid'] = $this->model->transactionid;

        $data['trackid'] = $this->id;
    }

    /**
     * Checks that trackid is in response is same as the
     * one in request sent
     *
     * @return void
     */
    protected function validateRefundResponse()
    {
        $trackid = $this->supportTxnResponse['data']['trackid'];

        if ($trackid !== $this->supportTxnRequest['data']['trackid'])
        {
            throw new Exception\InvalidArgumentException(
                'Gateway Exception: Track id do not match');
        }
    }

    protected function persistAfterSupportTxn($type = 'capture')
    {
        if ($this->error)
        {
            $this->model = $this->repo->persistAfterSupportTxnError(
                                $this->id,
                                $this->supportTxnRequest['data']['transid'],
                                $this->supportTxnResponse['error'],
                                $type);

            $this->trace(
                Trace::ERROR,
                TraceCode::GATEWAY_SUPPORT_ERROR,
                $this->supportTxnResponse);
        }
        else
        {
            $this->model = $this->repo->persistAfterSupportTxn(
                    $this->supportTxnRequest['data'],
                    $this->supportTxnResponse['data']);

            $this->trace(
                Trace::INFO,
                TraceCode::GATEWAY_SUPPORT_RESPONSE,
                $this->supportTxnResponse);
        }
    }
}