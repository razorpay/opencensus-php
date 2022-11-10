<?php

namespace RZP\Reconciliator\Airtel\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Method;
use RZP\Models\Base\PublicEntity;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const COLUMN_PAYMENT_ID         = 'partner_txn_id';
    const COLUMN_PAYMENT_AMOUNT     = 'original_input_amt';
    const COLUMN_GATEWAY_PAYMENT_ID = 'transaction_id';

    protected function getPaymentId(array $row)
    {
        return $row[self::COLUMN_PAYMENT_ID];
    }

    public function getGatewayPayment($paymentId)
    {
        if ($this->payment->getMethod() === Method::WALLET)
        {
            $gatewayPayment = $this->repo->wallet->fetchWalletByPaymentId($paymentId);
        }
        else
        {
            $gatewayPayment = $this->repo->netbanking->findByPaymentIdAndAction($paymentId, 'authorize');
        }

        return $gatewayPayment;
    }

    protected function getGatewayTransactionId(array $row)
    {
        return $row[self::COLUMN_GATEWAY_PAYMENT_ID];
    }

    protected function setGatewayTransactionId(string $gatewayPaymentId, PublicEntity $gatewayPayment)
    {
        $func = 'BankPaymentId';

        if ($this->payment->getMethod() === Method::WALLET)
        {
            $func = 'GatewayPaymentId';
        }

        $getFunc = 'get'.$func;
        $setFunc = 'set'.$func;

        $dbGatewayTransactionId = trim($gatewayPayment->$getFunc());

        if ((empty($dbGatewayTransactionId) === false) and
            ($dbGatewayTransactionId !== $gatewayPaymentId))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => $infoCode,
                    'message'                   => 'Reference number in db is not same as in recon',
                    'payment_id'                => $this->payment->getId(),
                    'amount'                    => $this->payment->getBaseAmount(),
                    'db_reference_number'       => $dbGatewayTransactionId,
                    'recon_reference_number'    => $gatewayPaymentId,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $gatewayPayment->$setFunc($gatewayPaymentId);
    }

    protected function getInputForForceAuthorize($row): array
    {
        return [
            'gateway_payment_id' => $this->getGatewayTransactionId($row),
            'acquirer'           => [
                'reference1' => $this->getGatewayTransactionId($row),
            ]
        ];
    }
}
