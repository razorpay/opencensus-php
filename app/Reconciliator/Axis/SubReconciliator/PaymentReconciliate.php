<?php

namespace RZP\Reconciliator\Axis;

use Carbon\Carbon;

use RZP\Exception\ReconciliationException;
use RZP\Models\Bank\IFSC;
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
    const COLUMN_PAYMENT_ID    = ['merchant_trans_ref', 'merchant_tran_ref'];
    const COLUMN_CARD_TYPE     = 'card_type';
    const COLUMN_SERVICE_TAX   = ['service_taxat145', 'service_taxat1450', 'service_taxat135',
                                  'service_taxat1350', 'service_taxat1500'];
    const COLUMN_FEE           = 'commission';
    const COLUMN_CARD_TRIVIA   = ['card', 'network', 'card_category'];
    const COLUMN_ORDER_ID      = 'order_id';
    const COLUMN_RRN           = 'rrn_no';
    const COLUMN_CARD_LOCALE   = 'lofo';
    const COLUMN_ISSUER        = 'transaction_category';
    const COLUMN_SETTLED_AT    = 'settlement_date';

    const POSSIBLE_DATE_FORMATS = [
        'd-M-y',
        'Y-m-d h:i:s'
    ];

    protected $axisMigsRepo;

    public function __construct()
    {
        parent::__construct();

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
        $columnServiceTax = null;

        foreach(self::COLUMN_SERVICE_TAX as $cst)
        {
            if (isset($row[$cst]) === true)
            {
                $columnServiceTax = $cst;
                break;
            }
        }

        if ($columnServiceTax === null)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_FAILURE,
                    'message'         => 'Unable to get the service tax!',
                    'row'             => $row,
                    'gateway'         => get_class()
                ]);

            throw new ReconciliationException('Unable to get the service tax for Axis from the recon file.');
        }

        // Convert service tax into basic unit of currency. (ex: paise)
        $serviceTax = floatval($row[$columnServiceTax]) * 100;

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
        if (empty($row[self::COLUMN_CARD_TYPE]) === true)
        {
            return null;
        }

        $columnCardType = strtolower($row[self::COLUMN_CARD_TYPE]);
        $columnCardTrivia = $this->getColumnCardTrivia($row);
        $columnCardLocale = $this->getColumnCardLocale($row);

        $cardType = $this->getCardType($columnCardType, $row);
        $cardLocale = $this->getCardLocale($columnCardLocale, $row);
        $cardTrivia = $this->getCardTrivia($columnCardTrivia, $row);
        $issuer = $this->getIssuer($row);

        return [
            BaseReconciliate::CARD_TYPE   => $cardType,
            BaseReconciliate::CARD_TRIVIA => $cardTrivia,
            BaseReconciliate::CARD_LOCALE => $cardLocale,
            BaseReconciliate::ISSUER      => $issuer,
        ];
    }

    protected function getIssuer($row)
    {
        if (empty($row[self::COLUMN_ISSUER]) === true)
        {
            return null;
        }

        $columnIssuer = strtolower($row[self::COLUMN_ISSUER]);

        if ($columnIssuer === 'onus')
        {
            return IFSC::UTIB;
        }

        return null;
    }

    protected function getColumnCardLocale($row)
    {
        $columnCardLocale = null;

        if (isset($row[self::COLUMN_CARD_LOCALE]) === true)
        {
            $columnCardLocale = strtolower($row[self::COLUMN_CARD_LOCALE]);
        }

        return $columnCardLocale;
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

        // $vpcTransactionNo = $this->axisMigsRepo
        //                          ->findByRrn($row[self::COLUMN_RRN])
        //                          ->getTransactionId();

        $input['vpc_TransactionNo'] = $row[self::COLUMN_ORDER_ID];

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

    protected function getCardLocale($cardLocale, $row)
    {
        if (empty($cardLocale) === true)
        {
            $this->app['trace']->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'message'           => 'Unable to get the card locale. This is unexpected.',
                    'info_code'         => 'CARD_LOCALE_ABSENT',
                    'recon_card_trivia' => $cardLocale,
                    'row'               => $row,
                    'gateway'           => get_class()
                ]
            );

            return null;
        }

        if (($cardLocale === 'l') or ($cardLocale === 'local'))
        {
            $cardType = BaseReconciliate::DOMESTIC;
        }
        else if (($cardLocale === 'f') or ($cardLocale === 'foreign'))
        {
            $cardType = BaseReconciliate::INTERNATIONAL;
        }
        else
        {
            $this->app['trace']->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'message'           => 'Unable to get the card locale. This is unexpected.',
                    'info_code'         => 'CARD_LOCALE_ABSENT',
                    'recon_card_trivia' => $cardLocale,
                    'row'               => $row,
                    'gateway'           => get_class()
                ]
            );

            // It's as good as no card locale present in the row.
            return null;
        }

        return $cardType;
    }

    protected function getGatewaySettledAt($row)
    {
        if (empty($row[self::COLUMN_SETTLED_AT]) === true)
        {
            return null;
        }

        $columnSettledAt = strtolower($row[self::COLUMN_SETTLED_AT]);

        $gatewaySettledAt = null;

        foreach (self::POSSIBLE_DATE_FORMATS as $possibleDateFormat)
        {
            try
            {
                $gatewaySettledAt = Carbon::createFromFormat($possibleDateFormat, $columnSettledAt, 'Asia/Kolkata');
                $gatewaySettledAt = $gatewaySettledAt->timestamp;
            }
            catch (\Exception $ex)
            {
                continue;
            }
        }

        return $gatewaySettledAt;
    }
}