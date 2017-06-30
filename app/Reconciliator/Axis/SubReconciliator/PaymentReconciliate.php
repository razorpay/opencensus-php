<?php

namespace RZP\Reconciliator\Axis;

use Carbon\Carbon;

use RZP\Exception\ReconciliationException;
use RZP\Models\Bank\IFSC;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

use RZP\Trace\TraceCode;
use RZP\Models\Payment\Service as PaymentService;
use RZP\Models\Payment\Status as PaymentStatus;
use RZP\Gateway\Cybersource;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID    = ['merchant_trans_ref', 'merchant_tran_ref'];
    const COLUMN_CARD_TYPE     = 'card_type';
    const COLUMN_SERVICE_TAX   = ['service_tax', 'service_taxat145', 'service_taxat1450',
                                  'service_taxat135', 'service_taxat1350', 'service_taxat1500'];
    const COLUMN_FEE           = 'commission';
    const COLUMN_CARD_TRIVIA   = ['card', 'network', 'card_category'];
    const COLUMN_ORDER_ID      = 'order_id';
    const COLUMN_CARD_LOCALE   = 'lofo';
    const COLUMN_ISSUER        = 'transaction_category';
    const COLUMN_SETTLED_AT    = 'settlement_date';
    const COLUMN_MSG_TYPE      = 'msg_type';
    const COLUMN_MID           = 'mid';

    const PREAUTH              = 'PREAUTH';
    const CYBS                 = 'CYBS';

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
        $paymentId = $this->getPaymentIdForMigs($row);

        //
        // Calling the Cybersource one after Migs one because
        // Cybersource one involves a repo call.
        //
        if ($paymentId === null)
        {
            $paymentId = $this->getPaymentIdForCybersource($row);
        }

        return $paymentId;
    }

    protected function getPaymentIdForMigs(array $row)
    {
        $paymentId = null;

        foreach (self::COLUMN_PAYMENT_ID as $cpi)
        {
            if (empty($row[$cpi]) === false)
            {
                $paymentId = $row[$cpi];

                break;
            }
        }

        //
        // For Cybersource payments via Axis, we don't get a payment ID
        // in the self::COLUMN_PAYMENT_ID. We get some reference number.
        // This usually means that it's a Cybersource payment and we have
        // to get payment_id in a different way.
        //
        if (UniqueIdEntity::verifyUniqueId($paymentId, false) === false)
        {
            return null;
        }

        return $paymentId;
    }

    protected function getPaymentIdForCybersource(array $row)
    {
        $paymentId = $msgType = $mid = null;

        if (isset($row[self::COLUMN_MSG_TYPE]) === true)
        {
            $msgType = $row[self::COLUMN_MSG_TYPE];
        }

        if (isset($row[self::COLUMN_MID]) === true)
        {
            $mid = $row[self::COLUMN_MID];
        }

        if ((stripos($msgType, self::PREAUTH) === true) or
            (ends_with($mid, self::CYBS) === true))
        {
            $orderId = $row[self::COLUMN_ORDER_ID];

            $gatewayPayment = $this->repo->cybersouce->findSuccessfulTxnByActionAndRef(
                                                            Cybersource\Action::CAPTURE, $orderId);

            if ($gatewayPayment !== null)
            {
                $paymentId = $gatewayPayment->getPaymentId();
            }
        }

        return $paymentId;
    }

    protected function getGatewayServiceTax($row)
    {
        $columnServiceTax = null;

        foreach(self::COLUMN_SERVICE_TAX as $cst)
        {
            //
            // This should be isset only and not empty
            // because service tax can be 0 also.
            //
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

        if (empty($row[self::COLUMN_CARD_LOCALE]) === false)
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
            if (empty($row[$cct]) === false)
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

        if ((empty($response['status']) === false) and
            ($response['status'] === PaymentStatus::AUTHORIZED))
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
