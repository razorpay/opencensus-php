<?php

namespace RZP\Reconciliator\Hitachi\SubReconciliator;

use Razorpay\Trace\Logger;
use RZP\Models\BharatQr;
use RZP\Trace\TraceCode;
use RZP\Gateway\Hitachi;
use RZP\Reconciliator\Base;
use RZP\Models\Card\Network;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Hitachi\ResponseFields;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    use Base\BharatQrTrait;

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
    const COLUMN_CURRENCY_CODE          = 'tran_currency_code';
    const COLUMN_RRN                    = 'retr_ref_nr';

    const BHARAT_QR_TERMINAL            = '38R00450';

    const DEFAULT_CURRENCY_CODE         = '356';

    const COLUMN_CARD_NUMBER            = 'pan';
    const COLUMN_STAN                   = 'stan';
    const COLUMN_RESPONSE_CODE          = 'response_code';
    const COLUMN_MERCHANT_ID            = 'merchant_id';
    const COLUMN_MERCHANT_NAME          = 'merchant_name';
    const COLUMN_PURCHASE_ID            = 'purchaseid';

    // This column indicates if we should create unexpected payment
    const UNEXPECTED_PAYMENT_RRN        = 'unexpected_payment_rrn';

    const CALL_BACK_FIELD_MAPPING = [
        ResponseFields::MASKED_CARD_NUMBER  => self::COLUMN_CARD_NUMBER,
        ResponseFields::AMOUNT              => self::COLUMN_PAYMENT_AMOUNT,
        ResponseFields::AUDIT_TRACE_NUMBER  => self::COLUMN_STAN,
        ResponseFields::RRN                 => self::COLUMN_RRN,
        ResponseFields::AUTHORIZATION_ID    => self::COLUMN_AUTH_CODE,
        ResponseFields::STATUS_CODE         => self::COLUMN_RESPONSE_CODE,
        ResponseFields::TERMINAL_ID         => self::COLUMN_TERMINAL_NUMBER,
        ResponseFields::MID                 => self::COLUMN_MERCHANT_ID,
        ResponseFields::MERCHANT_NAME       => self::COLUMN_MERCHANT_NAME,
        ResponseFields::PURCHASE_ID         => self::COLUMN_PURCHASE_ID,
    ];

    const CARD_NETWORK_MAPPING = [
        Network::VISA   => '260000',
        Network::MC     => '280000',
    ];

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

        return $this->getPaymentIdByTerminal($row);
    }

    /**
     * Gets the payment id. In case of bharat qr payments
     * information is present in rrn while in case of
     * normal payments this info is present in invoice number
     *
     * @param array $row
     * @return mixed|null
     */
    protected function getPaymentIdByTerminal(array $row)
    {
        if ($row[self::COLUMN_TERMINAL_NUMBER] === self::BHARAT_QR_TERMINAL)
        {
            $amount = (int) ($row[self::COLUMN_PAYMENT_AMOUNT] * 100);

            $bharatQr = $this->repo->bharat_qr->findByProviderReferenceIdAndAmount($row[self::COLUMN_RRN], $amount);

            if ($bharatQr !== null)
            {
                return $bharatQr->payment->getId();
            }
        }
        else
        {
            return $row[self::COLUMN_PAYMENT_ID];
        }

        $payment = null;

        try {
            $payment = $this->repo->payment->findOrFail($row[self::COLUMN_PAYMENT_ID]);
        }
        catch (\Throwable $e){
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::PAYMENT_NOT_FOUND_FOR_VERIFY,
                [
                    "payment_id" => $row[self::COLUMN_PAYMENT_ID]
                ]
            );
        }

        if($payment === null)
        {
            // create payment using recon row details
            $paymentId = $this->createUnexpectedPayment($row);
        }
        else
        {
            $paymentId = $payment->getId();
        }

        return $paymentId;
    }

    protected function createUnexpectedPayment(array $row)
    {
        if (empty($row[self::UNEXPECTED_PAYMENT_RRN]) === true)
        {
            //
            // We create unexpected payment only when this extra column is explicitly
            // set by FinOps team. This is to avoid un-intentional payment creation
            // in case someone upload an old MIS file.
            //

            $this->alertUnexpectedBharatQrPayment($row[self::COLUMN_RRN], $row);

            $this->setFailUnprocessedRow(false);

            return null;
        }

        // Generate callback data from recon row if possible
        $callbackData = $this->generateCallbackData($row);

        if ($callbackData === null)
        {
            return null;
        }

        $paymentId = null;

        $this->trace->info(
            TraceCode::RECON_INFO,
            [
                'info_code' => Base\InfoCode::RECON_UNEXPECTED_PAYMENT_CREATE_INITIATED,
                'rrn'       => $row[self::COLUMN_RRN],
                'gateway'   => $this->gateway
            ]
        );

        $input = [
            'content'   => [],
            'raw'       => $callbackData,
        ];

        $response = (new BharatQr\Service)->processPayment($input, 'hitachi');

        // Fetch and raise alert if payment still not created
        $amount = (int) ($row[self::COLUMN_PAYMENT_AMOUNT] * 100);

        $bharatQr = $this->repo->bharat_qr->findByProviderReferenceIdAndAmount(
                                                                                $row[self::COLUMN_RRN] ,
                                                                                $amount);

        if ($bharatQr === null)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'infoCode'      => Base\InfoCode::RECON_UNEXPECTED_PAYMENT_CREATION_FAILED,
                    'rrn'           => $row[self::COLUMN_RRN],
                    'response'      => $response,
                    'gateway'       => $this->gateway,
                ]);
        }
        else
        {
            $paymentId = $bharatQr->payment->getId();

            $this->trace->info(
                TraceCode::RECON_INFO,
                [
                    'infoCode'              => Base\InfoCode::RECON_UNEXPECTED_PAYMENT_CREATED,
                    'payment_id'            => $paymentId,
                    'rrn'                   => $row[self::COLUMN_RRN],
                    'gateway'               => $this->gateway,
                ]);
        }

        return $paymentId;
    }

    protected function generateCallbackData(array $row)
    {
        $callbackData = '';

        foreach (self::CALL_BACK_FIELD_MAPPING as $callbackField => $reconColumn)
        {
            if (empty($row[$reconColumn]) === true)
            {
                // Required data missing
                $this->trace->info(
                    TraceCode::RECON_INFO_ALERT,
                    [
                        'info_code' => Base\InfoCode::RECON_INSUFFICIENT_DATA_FOR_ENTITY_CREATION,
                        'message'   => 'Data missing to create Payment via Recon',
                        'rrn'       => $row[self::COLUMN_RRN],
                        'gateway'   => $this->gateway
                    ]
                );

                return null;
            }

            if ($reconColumn === self::COLUMN_PAYMENT_AMOUNT)
            {
                // convert to paisa and then append.

                $amountInPaisa = $this->getReconPaymentAmount($row);

                $callbackData .= $callbackField . '=' . $amountInPaisa . '&';

            }
            else
            {
                $callbackData .= $callbackField . '=' . $row[$reconColumn] . '&';
            }
        }

        //
        // We do not get card network and sender name in recon file row.
        // Need to add blank string values, else we get index not found error.
        //
        $cardNetwork = $this->getCardNetwork($row);

        $callbackData .= ResponseFields::CARD_NETWORK . '=' . $cardNetwork . '&';
        $callbackData .= ResponseFields::SENDER_NAME . '=' . '';

        return $callbackData;
    }

    // Derive card network from 6 digit bin.
    protected function getCardNetwork(array $row)
    {
        $maskedPan = $row[self::COLUMN_CARD_NUMBER];

        $cardBin = substr($maskedPan, 0, 6);

        $cardNetwork = Network::detectNetwork($cardBin);

        return self::CARD_NETWORK_MAPPING[$cardNetwork] ?? '';
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

    protected function getReferenceNumber($row)
    {
        return $row[self::COLUMN_RRN] ?? null;
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

    protected function getReconPaymentAmount(array $row)
    {
        if (isset($row[static::COLUMN_PAYMENT_AMOUNT]) === false)
        {
            return null;
        }

        $paymentAmount = Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::COLUMN_PAYMENT_AMOUNT]);

        return $paymentAmount;
    }

    protected function getReconCurrencyCode($row)
    {
        if (empty($row[self::COLUMN_CURRENCY_CODE]) === true)
        {
            $this->reportMissingColumn($row, self::COLUMN_CURRENCY_CODE);

            return null;
        }

        return $row[self::COLUMN_CURRENCY_CODE];
    }

    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        $convertCurrency = $this->payment->getConvertCurrency();

        $paymentAmount = ($convertCurrency === true) ? $this->payment->getBaseAmount() : $this->payment->getGatewayAmount();

        if ($paymentAmount !== $this->getReconPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'info_code'         => Base\InfoCode::AMOUNT_MISMATCH,
                    'payment_id'        => $this->payment->getId(),
                    'expected_amount'   => $paymentAmount,
                    'recon_amount'      => $this->getReconPaymentAmount($row),
                    'currency'          => $this->payment->getGatewayCurrency(),
                    'gateway'           => $this->gateway
                ]);

            return false;
        }
        return true;
    }

    protected function validatePaymentCurrencyEqualsReconCurrency(array $row) : bool
    {
        $convertCurrency = $this->payment->getConvertCurrency();

        $expectedCurrency = ($convertCurrency === true) ? self::DEFAULT_CURRENCY_CODE : Currency::getIsoCode($this->payment->getGatewayCurrency());

        $reconCurrency = $this->getReconCurrencyCode($row);

        if (($expectedCurrency !== $reconCurrency) and ( empty($reconCurrency) !== true))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'info_code'         => Base\InfoCode::CURRENCY_MISMATCH,
                    'payment_id'        => $this->payment->getId(),
                    'expected_currency' => $expectedCurrency,
                    'recon_currency'    => $reconCurrency,
                    'gateway'           => $this->gateway
                ]);

            return false;
        }

        return true;
    }

    /**
     * This returns the array of attributes to be saved while force authorizing the payment.
     *
     * @param $row
     * @return array
     */
    protected function getInputForForceAuthorize($row)
    {
        return [
            Hitachi\Entity::RRN                 => $this->getReferenceNumber($row),
            Hitachi\Entity::AUTH_ID             => $this->getAuthCode($row),
            Hitachi\Entity::MERCHANT_REFERENCE  => $this->payment->getId(),
        ];
    }
}
