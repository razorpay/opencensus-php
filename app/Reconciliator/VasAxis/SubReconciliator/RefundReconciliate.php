<?php

namespace RZP\Reconciliator\VasAxis\SubReconciliator;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Action;
use RZP\Models\Base\PublicEntity;
use Razorpay\Spine\Exception\DbQueryException;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const COLUMN_RRN                  = 'rrn';
    const COLUMN_TXN_METHOD           = 'ti';
    const COLUMN_REFUND_AMOUNT        = 'gross_amt';
    const COLUMN_GATEWAY_AMOUNT       = 'net_amt';
    const COLUMN_SETTLED_AT           = 'process_date';
    const COLUMN_GATEWAY_UTR          = 'utr';

    // Different methods
    const BHARAT_QR                   = 'Bharat QR';

    protected function getRefundId(array $row)
    {
        return $this->getRefundIdByMethod($row);
    }

    /**
     * In case of bharat qr refunds
     * we use rrn to get worldLine entity to fetch refund_id
     *
     * @param array $row
     * @return mixed|null
     */
    protected function getRefundIdByMethod(array $row)
    {
        $refundId = null;

        if ($row[self::COLUMN_TXN_METHOD] === self::BHARAT_QR)
        {
            if (empty($row[self::COLUMN_RRN]) === true)
            {
                return null;
            }

            $worldLine = $this->repo->worldline->findByReferenceNumberAndAction($row[self::COLUMN_RRN], Action::REFUND);

            if (empty($worldLine) === false)
            {
                $refundId = $worldLine->getRefundId();

                $this->gatewayRefund = $worldLine;
            }
            else
            {
                $this->trace->info(
                    TraceCode::RECON_INFO_ALERT,
                    [
                        'info_code'              => Base\InfoCode::UNEXPECTED_REFUND,
                        'refund_reference_id'    => $row[self::COLUMN_RRN],
                        'gateway'                => $this->gateway,
                        'batch_id'               => $this->batchId,
                    ]);
            }
        }

        return $refundId;
    }

    protected function getGatewayRefund(string $refundId)
    {
        //
        // we have already set the $gatewayRefund
        // in getRefundIdByMethod(), simply return it.
        //
        return $this->gatewayRefund;
    }

    //
    // We want to save rrn in refund reference_1
    // so set the rrn as arn in $rowDetails
    //
    protected function getArn(array $row)
    {
        return $row[self::COLUMN_RRN] ?? null;
    }

    protected function getGatewayUtr($row)
    {
        return $row[self::COLUMN_GATEWAY_UTR] ?? null;
    }

    protected function getGatewaySettledAt(array $row)
    {
        $gatewaySettledAtTimestamp = null;

        $settledAt = $row[self::COLUMN_SETTLED_AT] ?? null;

        try
        {
            $gatewaySettledAtTimestamp = Carbon::parse($settledAt)->setTimezone(Timezone::IST)->getTimestamp();
        }
        catch (\Exception $ex)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'     => Base\InfoCode::INCORRECT_DATE_FORMAT,
                    'settled_at'    => $settledAt,
                    'refund_id'     => $this->refund->getId(),
                    'gateway'       => $this->gateway,
                ]);
        }

        return $gatewaySettledAtTimestamp;
    }

    protected function getGatewayAmount(array $row)
    {
        $gatewayAmt =  $row[self::COLUMN_REFUND_AMOUNT] ?? null;

        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($gatewayAmt);
    }

    /**
     * Saves gateway UTR in gateway entity
     *
     * @param array $rowDetails
     * @param PublicEntity $gatewayRefund
     */
    protected function persistGatewayUtr(array $rowDetails, PublicEntity $gatewayRefund)
    {
        if (empty($rowDetails[Base\Reconciliate::GATEWAY_UTR]) === false)
        {
            $this->setGatewayUtrInGateway($rowDetails[Base\Reconciliate::GATEWAY_UTR], $gatewayRefund);
        }
    }

    /**
     * Save gateway UTR, Raise alert if existing UTR
     * not matching with MIS data
     *
     * @param string $reconGatewayUtr
     * @param PublicEntity $gatewayRefund
     */
    protected function setGatewayUtrInGateway(string $reconGatewayUtr, PublicEntity $gatewayRefund)
    {
        $dbReferenceNumber = trim($gatewayRefund->getGatewayUtr());

        if ((empty($dbReferenceNumber) === false) and
            ($dbReferenceNumber !== $reconGatewayUtr))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => $infoCode,
                    'message'                   => 'Gateway UTR in db is not same as in recon',
                    'refund_id'                 => $this->refund->getId(),
                    'amount'                    => $this->refund->getAmount(),
                    'payment_id'                => $this->refund->payment->getId(),
                    'db_reference_number'       => $dbReferenceNumber,
                    'recon_reference_number'    => $reconGatewayUtr,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $gatewayRefund->setGatewayUtr($reconGatewayUtr);
    }
}
