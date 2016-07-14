<?php

namespace RZP\Reconciliator\Axis;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;
use RZP\Reconciliator\Messenger;

use RZP\Trace\TraceCode;
use RZP\Models\Payment\Service as PaymentService;
use RZP\Models\Payment\Status as PaymentStatus;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID  = ['merchant_trans_ref', 'merchant_tran_ref'];
    const COLUMN_CARD_TYPE   = 'card_type';
    const COLUMN_SERVICE_TAX = 'service_taxat145';
    const COLUMN_FEE         = 'commission';
    const COLUMN_CARD_TRIVIA = ['card', 'card_category'];
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
        $columnPaymentId = null;

        foreach(self::COLUMN_PAYMENT_ID as $cpi)
        {
            if (isset($row[$cpi]) === true)
            {
                $columnPaymentId = $cpi;
                break;
            }
        }

        if ($columnPaymentId === null)
        {
            return null;
        }

        $paymentId = $row[$columnPaymentId];
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
        // If the card type (debit/credit) is not present, we don't want
        // to store any of the other card details.
        if (isset($row[self::COLUMN_CARD_TYPE]) === false)
        {
            return null;
        }

        $columnCardType = strtolower($row[self::COLUMN_CARD_TYPE]);
        $columnCardTrivia = $this->getColumnCardTrivia($row);

        $cardType = $this->getCardType($columnCardType, $row);
        $cardTrivia = $this->getCardTrivia($columnCardTrivia, $row);

        return [
            BaseReconciliate::CARD_TYPE => $cardType,
            BaseReconciliate::CARD_TRIVIA => $cardTrivia,
        ];
    }

    protected function getColumnCardTrivia($row)
    {
        $columnCardTrivia = null;

        foreach (self::COLUMN_CARD_TRIVIA as $cct)
        {
            if (isset($row[$cct]) === true)
            {
                $columnCardTrivia = $cct;
                break;
            }
        }

        if ($columnCardTrivia === null)
        {
            return null;
        }

        return $row[$columnCardTrivia];
    }

    protected function getCardTrivia($cardTrivia, $row)
    {
        if (empty($cardTrivia) === true)
        {
            $this->app['trace']->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'message'           => 'Unable to get the card trivia. This is unexpected.',
                    'info_code'         => 'CARD_TRIVIA_ABSENT',
                    'recon_card_trivia' => $cardTrivia,
                    'row'               => $row,
                    'gateway'           => get_class()
                ]
            );

            $cardTrivia = null;
        }

        return $cardTrivia;
    }

    protected function getCardType($cardType, $row)
    {
        if (($cardType === 'c') or ($cardType === 'credit'))
        {
            $cardType = BaseReconciliate::CREDIT;
        }
        else if (($cardType === 'd') or ($cardType === 'debit'))
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
            $cardType = null;
        }

        return $cardType;
    }

    protected function forceAuthorizeFailed($row)
    {
        $paymentService = new PaymentService();

        $paymentId = $this->payment->getPublicId();

        $vpcTransactionNo = $this->axisMigsRepo
                                 ->findByRrn($row[self::RRN])
                                 ->getTransactionId();

        $input['vpc_TransactionNo'] = $vpcTransactionNo;

        $this->messenger->raiseReconAlert(
            [
                'trace_code'      => TraceCode::RECON_INFO_ALERT,
                'message'         => 'Payment status is still failed after verify. Doing force authorize now.',
                'payment_id'      => $this->payment->getId(),
                'gateway'         => get_called_class()
            ]);

        // If there's any issue during authorize, the function throws an exception.
        $response = $paymentService->forceAuthorizeFailed($paymentId, $input);

        $this->app['trace']->info(
            TraceCode::RECON_INFO,
            [
                'info_code' => 'FORCE_AUTHORIZATION_RESPONSE',
                'message'   => 'Response received from force authorization',
                'response'  => $response
            ]
        );

        if ((empty($response['status']) === false) and ($response['status'] === PaymentStatus::AUTHORIZED))
        {
            return true;
        }

        return false;
    }
}