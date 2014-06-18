<?php


namespace Gateway\HdfcGateway;

use Trace\Trace;
use Trace\TraceEvent;

trait HdfcGatewaySupportTxn
{
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
            TraceEvent::GATEWAY_SUPPORT_REQUEST,
            $this->supportTxnRequest);

        $this->runRequestResponseFlow(
            $this->supportTxnRequest,
            $this->supportTxnResponse);


        $error = null;

        $status = ! ($this->error);

        if($this->error)
        {
            $error = HdfcGatewayErrorHandler::parseErrorInString(
                        $this->supportTxnResponse['error']['result']);

            if ($error === false)
                    $error = HdfcGatewayErrorHandler::unknownError();
        }

        $this->persistAfterSupportTxn('refund');

        return array($status, $error);
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

        $action = constant(__NAMESPACE__.'\HdfcGatewayAction::'.strtoupper($type));

        $data['action'] = $action;

        // Convert amount from integer to decimal
        $data['amt'] = $txn['amount']/100;

        $data['currencycode'] = self::INR_CODE;

        $data['member'] = $card['name'];

        $data['transid'] = $this->model->transactionid;

        $data['trackid'] = $this->id;

        // Set udf fields
        $data['udf1'] = $data['udf2'] = $data['udf3'] = $data['udf4'] = $data['udf5'] = '';
    }

    protected function validateRefundResponse()
    {
        $trackid = $this->supportTxnResponse['data']['trackid'];

        if ($trackid !== $this->supportTxnRequest['data']['trackid'])
        {
            throw new InvalidArgumentException('Gateway Exception: Track id do not match');
        }
    }

    protected function persistAfterSupportTxn($type = 'capture')
    {
        if ($this->error)
        {
            $this->model = HdfcGatewayDal::persistAfterSupportTxnError(
                            $this->id,
                            $this->supportTxnRequest['data']['transid'],
                            $this->supportTxnResponse['error'],
                            $type);

            $this->trace(
                Trace::ERROR,
                TraceEvent::GATEWAY_SUPPORT_ERROR,
                $this->supportTxnResponse);
        }
        else
        {
            $this->model = HdfcGatewayDal::persistAfterSupportTxn(
                    $this->supportTxnRequest['data'],
                    $this->supportTxnResponse['data']);

            $this->trace(
                Trace::INFO,
                TraceEvent::GATEWAY_SUPPORT_RESPONSE,
                $this->supportTxnResponse);
        }
    }
}