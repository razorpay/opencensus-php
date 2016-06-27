<?php

namespace Reconciliator\Axis;

use Reconciliator\Base;
use Reconciliator\Base\Reconciliate as BaseReconciliate;
use Reconciliator\Messenger;

use Models\Payment\Service as PaymentService;
use Trace\TraceCode;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID  = 'merchant_trans_ref';
    const COLUMN_CARD_TYPE   = 'card_type';
    const COLUMN_SERVICE_TAX = 'service_tax145';
    const COLUMN_FEE         = 'commission';
    const RRN                = 'rrn_no';

    protected $messenger;
    protected $axisMigsRepo;

    public function __construct()
    {
        parent::__construct();

        $this->messenger = new Messenger();

        $this->axisMigsRepo = $this->repo->axis_migs;
    }

    protected function getPaymentId($row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID];
        return $paymentId;
    }

    protected function getGatewayServiceTax($row)
    {
        // Convert service tax into basic unit of currency. (ex: paise)
        $serviceTax = floatval($row[self::COLUMN_SERVICE_TAX]) * 100;

        return round($serviceTax);
    }

    protected function getGatewayFee($row)
    {
        // Convert fee into basic unit of currency. (ex: paise)
        $fee = floatval($row[self::COLUMN_FEE]) * 100;

        // Already in basic unit of currency. Hence, no conversion needed
        $serviceTax = $this->getGatewayServiceTax($row);

        // Axis reconciliation files have fee and service tax separately
        $fee += $serviceTax;

        return round($fee);
    }

    protected function getCardDetails($row)
    {
        if (isset($row[self::COLUMN_CARD_TYPE]) === false)
        {
            return null;
        }

        $cardType = strtolower($row[self::COLUMN_CARD_TYPE]);

        if ($cardType === 'c')
        {
            $cardType = BaseReconciliate::CREDIT;
        }
        else if ($cardType === 'd')
        {
            $cardType = BaseReconciliate::DEBIT;
        }
        else
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_PARSE_ERROR,
                    'message'         => 'Unable to figure out the card type.',
                    'recon_card_type' => $cardType,
                    'row'             => $row,
                    'gateway'         => get_class()
                ]);

            // It's as good as no card type present in the row.
            return null;
        }

        return [
            BaseReconciliate::CARD_TYPE => $cardType,
        ];
    }

    protected function forceAuthorizeFailed($row)
    {
        $paymentService = new PaymentService();

        $paymentId = $this->payment->getPublicId();

        $vpcTransactionNo = $this->axisMigsRepo
                                 ->findByRrn($row[self::RRN])
                                 ->getTransactionId();

        $input['vpc_TransactionNo'] = $vpcTransactionNo;

        // If there's any issue during authorize, the function throws an exception.
        $response = $paymentService->forceAuthorizeFailed($paymentId, $input);

        if ((empty($response['status']) === false) and ($response['status'] === 'authorized'))
        {
            return true;
        }

        return false;
    }
}