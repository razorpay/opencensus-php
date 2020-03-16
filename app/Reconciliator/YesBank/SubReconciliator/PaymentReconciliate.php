<?php

namespace RZP\Reconciliator\YesBank\SubReconciliator;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const COLUMN_PAYMENT_ID                = 'mtx_id';
    const COLUMN_CARD_TYPE                 = 'card_product';
    const COLUMN_CARD_COUNTRY              = 'card_country';
    const COLUMN_CARD_INTERCHANGE_TYPE     = 'card_scheme';
    const COLUMN_SERVICE_TAX               = ['bank_cgst', 'bank_sgst', 'bank_igst'];
    const COLUMN_FEE                       = 'inr_txn_commission';
    const COLUMN_GATEWAY_PAYMENT_ID        = 'ctx_id';
    const COLUMN_AUTH_CODE                 = 'auth_code';
    const COLUMN_ARN                       = 'arn';
    const COLUMN_DATE                      = 'transaction_date';
    const COLUMN_SETTLEMENT_DATE           = 'transaction_date';
    const COLUMN_SETTLEMENT_TIME           = 'transaction_time';
    const COLUMN_PAYMENT_AMOUNT            = 'orig_txn_amount';

    const POSSIBLE_DATE_FORMATS            = ['d-M-Y', 'd-M-Y H:i:s'];
    const INDIA_SHORTHANDS                 = ['IN', 'IND', 'in', 'ind'];
    const AUTH_CODE_LENGTH                 = 6;

    // Gateway Transaction Id & Gateway Payment Id not present in Recon file.

    protected function getPaymentId(array $row)
    {
        if (isset($row[self::COLUMN_PAYMENT_ID]) === true)
        {
            return trim($row[self::COLUMN_PAYMENT_ID]);
        }

        return null;
    }

    protected function getCardDetails($row)
    {
        // If the card type (debit/credit) is not present, we don't want
        // to store any of the other card details.
        if (empty($row[self::COLUMN_CARD_TYPE]) === true)
        {
            return [];
        }

        $columnCardType     = strtolower($row[self::COLUMN_CARD_TYPE]);
        $columnCardLocale   = $this->getColumnCardLocale($row);
        $columnCardTrivia   = $this->getColumnCardTrivia($row);

        $cardType           = $this->getCardType($columnCardType);
        $cardLocale         = $this->getCardLocale($columnCardLocale, $row);
        $cardTrivia         = $this->getCardTrivia($columnCardTrivia, $row);

        return [
            BaseReconciliate::CARD_TYPE   => $cardType,
            BaseReconciliate::CARD_LOCALE => $cardLocale,
            BaseReconciliate::CARD_TRIVIA => $cardTrivia,
        ];
    }

    protected function getColumnCardLocale($row)
    {
        if (isset($row[self::COLUMN_CARD_COUNTRY]) === true)
        {
            return strtolower($row[self::COLUMN_CARD_COUNTRY]);
        }

        return null;
    }

    protected function getColumnCardTrivia($row)
    {
        if (isset($row[self::COLUMN_CARD_INTERCHANGE_TYPE]) === true)
        {
            return strtolower($row[self::COLUMN_CARD_INTERCHANGE_TYPE]);
        }

        return null;
    }

    protected function getCardLocale($cardCountry, $row)
    {
        $cardLocale = null;

        if ((empty($cardCountry) === true) or ($cardCountry === 'xxx'))
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'message'           => 'Unable to get the card locale. This is unexpected.',
                    'info_code'         => 'CARD_LOCALE_ABSENT',
                    'recon_card_locale' => $cardCountry,
                    'row'               => $row,
                    'gateway'           => $this->gateway
                ]);
        }
        else if (in_array($cardCountry, self::INDIA_SHORTHANDS, true) === true)
        {
            $cardLocale = BaseReconciliate::DOMESTIC;
        }
        else
        {
            $cardLocale = BaseReconciliate::INTERNATIONAL;
        }

        return $cardLocale;
    }

    protected function getCardType($cardType)
    {
        if (($cardType === 'credit') or
            ($cardType === 'debit'))
        {
            return $cardType;
        }
        $this->trace->info(
            TraceCode::RECON_INFO_ALERT,
            [
                'trace_code'      => TraceCode::RECON_PARSE_ERROR,
                'message'         => 'Unable to figure out the card type.',
                'recon_card_type' => $cardType,
                'payment_id'      => $this->payment->getId(),
                'gateway'         => $this->gateway,
            ]);

        // It's as good as no card type present in the row.
        return null;
    }

    protected function getCardTrivia($cardTrivia, $row)
    {
        if (empty($cardTrivia) === true)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'message'           => 'Unable to get the card trivia. This is unexpected.',
                    'info_code'         => 'CARD_TRIVIA_ABSENT',
                    'recon_card_trivia' => $cardTrivia,
                    'row'               => $row,
                    'gateway'           => $this->gateway
                ]);

            $cardTrivia = null;
        }

        return $cardTrivia;
    }

    /*
     *   The gateway service tax is calculated in two ways.
     *   1. CGST = X, SGST = X and IGST = 0
     *   2. CGST = 0, SGST = 0 and IGST = 2X
     *
     *   For both cases Gateway Service Tax will be 2X
     *   Service Tax = CGST + SGST + IGST
     */
    protected function getGatewayServiceTax($row)
    {
        $columnServiceTax = 0;
        foreach(self::COLUMN_SERVICE_TAX as $cst)
        {
            // This should be isset only and not empty
            // because service tax can be 0 also.
            if (isset($row[$cst]) === true)
            {
                $columnServiceTax += $row[$cst];
            }
        }
        // Convert service tax into basic unit of currency. (ex: paise)
        $serviceTax = floatval($columnServiceTax) * 100;
        return round($serviceTax);
    }

    protected function getGatewayFee($row)
    {
        $fee = Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::COLUMN_FEE]);

        return $fee;
    }

    protected function getGatewaySettledAt(array $row)
    {
        if (empty($row[self::COLUMN_SETTLEMENT_DATE]) === true)
        {
            return null;
        }

        $columnSettledAtDate = strtolower($row[self::COLUMN_SETTLEMENT_DATE]);

        $columnSettledAt = $columnSettledAtDate;

        if (isset($row[self::COLUMN_SETTLEMENT_TIME]) === true)
        {
            $columnSettledAtTime = strtolower($row[self::COLUMN_SETTLEMENT_TIME]);

            $columnSettledAt = $columnSettledAtDate . ' ' . $columnSettledAtTime;
        }

        $gatewaySettledAt = null;

        foreach (self::POSSIBLE_DATE_FORMATS as $possibleDateFormat)
        {
            try
            {
                $gatewaySettledAt = Carbon::createFromFormat($possibleDateFormat, $columnSettledAt, Timezone::IST);
                $gatewaySettledAt = $gatewaySettledAt->getTimestamp();
            }
            catch (\Exception $ex)
            {
                continue;
            }
        }

        return $gatewaySettledAt;
    }

    protected function getGatewayAmount(array $row)
    {
        if (isset($row[self::COLUMN_PAYMENT_AMOUNT]) === true)
        {
            return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::COLUMN_PAYMENT_AMOUNT]);
        }

        $this->trace->info(
            TraceCode::RECON_INFO_ALERT,
            [
                'message'           => 'Unable to get the gateway amount. This is unexpected.',
                'info_code'         => 'GATEWAY_AMOUNT_ABSENT',
                'row'               => $row,
                'gateway'           => $this->gateway
            ]);
    }

    protected function getGatewayPaymentDate($row)
    {
        if (isset($row[self::COLUMN_DATE]) === true)
        {
            return trim($row[self::COLUMN_DATE]);
        }

        return null;
    }

    protected function getAuthCode($row)
    {
        if (isset($row[self::COLUMN_AUTH_CODE]) === true)
        {
            $authCode = trim($row[self::COLUMN_AUTH_CODE]);

            // Auth Code length is 6 digits, appending zeros in case it gets trimmed down because of file conversion.
            while (strlen($authCode) < self::AUTH_CODE_LENGTH)
            {
                $authCode = '0' . $authCode;
            }

            return $authCode;
        }
        return null;
    }

    protected function getArn($row)
    {
        if (isset($row[self::COLUMN_ARN]) === true)
        {
            return trim($row[self::COLUMN_ARN]);
        }

        return null;
    }

    protected function isInternationalPayment(array $row)
    {
        if (isset($row[self::COLUMN_CARD_COUNTRY]) === true)
        {
            if (in_array($row[self::COLUMN_CARD_COUNTRY], self::INDIA_SHORTHANDS, true) === true)
            {
                return false;
            }
            return true;
        }

        return true;
    }
}
