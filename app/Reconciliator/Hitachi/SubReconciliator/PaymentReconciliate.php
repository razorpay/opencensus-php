<?php 

namespace RZP\Reconciliator\Hitachi;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID             = 'invoice_number';
    const COLUMN_CARD_TYPE              = 'credit_debit';
    const COLUMN_ARN                    = 'arn';
    const COLUMN_AUTH_CODE              = 'auth_id';
    const COLUMN_TERMINAL_NUMBER        = 'terminal_id';
    const COLUMN_FEE                    = 'fee_amount';
    const COLUMN_PAYMENT_AMOUNT         = 'amount';
    const COLUMN_CARD_COUNTRY           = 'cardcountry';
    const COLUMN_CARD_INTERCHANGE_TYPE  = 'interchange_type';
    const COLUMN_ISSETTLED              = 'issettled';

    const BHARAT_QR_TERMINAL            = '38R00450';

    protected function getPaymentId(array $row)
    {
        // Unsettled rows should be skipped while processing.
        if ($row[self::COLUMN_ISSETTLED] !== 'S')
        {
            $this->trace->error(
                TraceCode::RECON_ALERT,
                [
                    'message'   => 'Unsettled row found. Skipping',
                    'row'       => $row,
                    'gateway'   => $this->gateway
                ]);
            
            $this->setFailUnprocessedRow(false);
            
            return null;
        }

        return $this->validatePaymentId($row);
    }

    /**
     * Receiving random bharat qr reference number in invoice number,
     * which is not our payment id. Skipping such rows based on id pattern and terminal check.
     *
     * @param array $row
     * @return mixed|null
     */
    protected function validatePaymentId(array $row)
    {
        if ((UniqueIdEntity::verifyUniqueId($row[self::COLUMN_PAYMENT_ID], false) === false)
            and ($row['terminal_id'] === self::BHARAT_QR_TERMINAL))
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'message'       => 'Payment ID being sent in the file is not as expected.',
                    'info_code'     => 'PAYMENT_ABSENT',
                    'row'           => $row,
                    'payment_id'    => $row[self::COLUMN_PAYMENT_ID],
                    'gateway'       => $this->gateway
                ]);

            $this->setFailUnprocessedRow(false);

            return null;
        }

        return $row[self::COLUMN_PAYMENT_ID];
    }

    protected function getGatewayFee($row)
    {
        $columnFee = $row[self::COLUMN_FEE];

        // Convert fee into basic unit of currency (ex: paise)
        $fee = floatval($columnFee) * 100;

        return intval(number_format($fee, 2, '.', ''));
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

        $cardType           = $this->getCardType($columnCardType, $row);
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
        if (empty($row[self::COLUMN_CARD_COUNTRY]) === true)
        {
            return null;
        }

        return strtolower($row[self::COLUMN_CARD_COUNTRY]);
    }
    
    protected function getColumnCardTrivia($row)
    {
        if (empty($row[self::COLUMN_CARD_INTERCHANGE_TYPE]) === true)
        {
            return null;
        }

        return strtolower($row[self::COLUMN_CARD_INTERCHANGE_TYPE]);
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

    protected function getCardType($cardType, $row)
    {
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
                    'gateway'         => $this->gateway
                ]);

            // It's as good as no card type present in the row.
            $cardType = null;
        }

        return $cardType;
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
        else if (in_array($cardCountry, ['ind', 'in'], true) === true)
        {
            $cardLocale = BaseReconciliate::DOMESTIC;
        }
        else
        {
            $cardLocale = BaseReconciliate::INTERNATIONAL;
        }

        return $cardLocale;
    }

    protected function getArn($row)
    {
        if (empty($row[self::COLUMN_ARN]) === true)
        {
            $this->reportMissingColumn($row, self::COLUMN_ARN);

            return null;
        }

        return $row[self::COLUMN_ARN];
    }

    protected function getAuthCode($row)
    {
        if (empty($row[self::COLUMN_AUTH_CODE]) === true)
        {
            $this->reportMissingColumn($row, self::COLUMN_AUTH_CODE);

            return null;
        }

        return $row[self::COLUMN_AUTH_CODE];
    }

}
