<?php

namespace RZP\Reconciliator\CardFssBob\SubReconciliator;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Trace\TraceCode;
use RZP\Models\Bank\IFSC;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Card\Fss\Status;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Currency\Currency;
use RZP\Reconciliator\Base\InfoCode;
use RZP\Reconciliator\Base\SubReconciliator;
use RZP\Reconciliator\Base\SubReconciliator\Helper as Helper;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class PaymentReconciliate extends SubReconciliator\PaymentReconciliate
{
    const ONUS_INDICATOR = 'yes';

    public function getPaymentId(array $row)
    {
        $paymentId = $row[ReconciliationFields::MERCHANT_TRACK_ID] ?? null;

        return trim(str_replace("'", '', $paymentId));
    }

    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        $convertCurrency = $this->payment->getConvertCurrency();

        $paymentAmount = ($convertCurrency === true) ? $this->payment->getBaseAmount() : $this->payment->getAmount();

        if ($paymentAmount !== $this->getReconPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'info_code'         => InfoCode::AMOUNT_MISMATCH,
                    'payment_id'        => $this->payment->getId(),
                    'expected_amount'   => $paymentAmount,
                    'recon_amount'      => $this->getReconPaymentAmount($row),
                    'currency'          => $this->payment->getCurrency(),
                    'gateway'           => $this->gateway
                ]);

            return false;
        }

        return true;
    }

    protected function getReconPaymentAmount($row)
    {
        return Helper::getIntegerFormattedAmount($row[ReconciliationFields::TRANSACTION_AMOUNT] ?? null);
    }

    protected function validatePaymentCurrencyEqualsReconCurrency(array $row) : bool
    {
        $convertCurrency = $this->payment->getConvertCurrency();

        $expectedCurrency = ($convertCurrency === true) ? Currency::INR : $this->payment->getCurrency();

        $reconCurrency = $this->getReconCurrency($row);

        if ($expectedCurrency !== $reconCurrency)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'info_code'         => InfoCode::CURRENCY_MISMATCH,
                    'expected_currency' => $expectedCurrency,
                    'recon_currency'    => $reconCurrency,
                    'row'               => $row,
                    'gateway'           => $this->gateway
                ]);

            return false;
        }

        return true;
    }

    protected function getReconCurrency($row)
    {
        return $row[ReconciliationFields::TRANSACTION_CURRENCY_CODE] ?? null;
    }

    public function getReferenceNumber($row)
    {
        $rrn = $row[ReconciliationFields::RRN] ?? null;

        return trim(str_replace("'", '', $rrn ?? null));
    }

    /**
     * It is present as Retrieval Reference Number in recon file
     * It should be set as ref setReference Number In Gateway
     * in the gateway entity.
     * @param $row
     * @return string
     */
    public function getArn($row)
    {
        $onusIndicator = $this->getOnusIndicator($row);

        $rrn = $this->getReferenceNumber($row);

        if (empty($rrn) === true)
        {
            $this->reportMissingColumn($row, $row[ReconciliationFields::RRN]);
        }
        else if (strtolower($onusIndicator) === self::ONUS_INDICATOR)
        {
            // Only in case of ONUS transactions, we want to store RRN
            // In all the other cases, we want to store ARN only.
            // Currently, only ONUS transactions go through this gateways.

            return $rrn;
        }

        return null;
    }


    protected function getOnusIndicator($row)
    {
        return strtolower($row[ReconciliationFields::ONUS_INDICATOR]?? '');
    }

    protected function getGatewayPayment($paymentId)
    {
        $status = Status::$successStates;

        return $this->repo
                    ->card_fss
                    ->findByPaymentIdActionAndStatus(
                        $paymentId,
                        Action::AUTHORIZE,
                        $status);
    }

    protected function getGatewayPaymentDate($row)
    {
        return $row[ReconciliationFields::TRANSACTION_DATE] ?? null;
    }

    /**
     * Gets the card details from settlement file. Not updating trivia since it is inconsistent
     * @param $row
     * @return array
     */
    protected function getCardDetails($row)
    {
        return [
            BaseReconciliate::CARD_TYPE   => $this->getCardType($row),
            BaseReconciliate::CARD_LOCALE => $this->getCardLocale($row),
            BaseReconciliate::ISSUER      => $this->getIssuer($row),
        ];
    }

    /**
     * Returns if the card is debit or credit from Payment Method
     * @param array $row Card type would be  Credit Card, Debit Card
     * @return string|null if any card type is present
     */
    protected function getCardType($row)
    {
        $cardType = explode(' ', strtolower($row[ReconciliationFields::PAYMENT_METHOD] ?? null))[0];

        if (in_array($cardType, [BaseReconciliate::DEBIT, BaseReconciliate::CREDIT]) === false)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'info_code'       => InfoCode::UNKNOWN_CARD_TYPE,
                    'recon_card_type' => $cardType,
                    'row'             => $row,
                    'gateway'         => $this->gateway
                ]);

            return null;
        }

        return $cardType;
    }

    /**
     * Returns if the card is international or domestic.
     * @param array $row
     * @return string
     */
    protected function getCardLocale($row)
    {
        $cardLocale = strtolower($row[ReconciliationFields::DESTINATION] ?? null);

        if (in_array($cardLocale, [BaseReconciliate::DOMESTIC, BaseReconciliate::INTERNATIONAL]) === false)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'message'         => 'unable to figure out card locale',
                    'recon_card_type' => $cardLocale,
                    'row'             => $row,
                    'gateway'         => $this->gateway
                ]);

            return null;
        }

        return $cardLocale;
    }

    /**
     * Returns the issuer bank from Onus
     * if Onus is true is BoB
     * @param $row
     * @return string|null if not a ONUS Transaction.
     */
    protected function getIssuer($row)
    {
        $onusIndicator = $this->getOnusIndicator($row);

        if (strtolower($onusIndicator) === self::ONUS_INDICATOR)
        {
            return IFSC::BARB;
        }

        return null;
    }

    /**
     * Returns the service tax gst + csf tax. csf tax is usually zero
     * @param $row
     * @return integer
     */
    protected function getGatewayServiceTax($row)
    {
        if (isset($row[ReconciliationFields::GST]) === false)
        {
            $this->reportMissingColumn($row, ReconciliationFields::GST);
        }

        $csfTax = (isset($row[ReconciliationFields::MSF_AMOUNT]) === true) ? abs($row[ReconciliationFields::CSF_TAX]) : 0;

        $gstTax = abs($row[ReconciliationFields::GST]);

        $tax = $gstTax + $csfTax;

        return Helper::getIntegerFormattedAmount($tax);
    }

    /**
     * Returns the gateway Payment Fee. All apart from msf amount are zero from sample recon
     * @param $row
     * @return integer
     */
    protected function getGatewayFee($row)
    {
        if (isset($row[ReconciliationFields::MSF_AMOUNT]) === false)
        {
            $this->reportMissingColumn($row, ReconciliationFields::MSF_AMOUNT);
        }

        if (isset($row[ReconciliationFields::LATE_SETTLEMENT_FEE_AMOUNT]) === true)
        {
            $lateSettlementFee = abs(Helper::getIntegerFormattedAmount($row[ReconciliationFields::LATE_SETTLEMENT_FEE_AMOUNT]));
        }

        else
        {
            $lateSettlementFee = 0;
        }

        if (isset($row[ReconciliationFields::RRF_AMOUNT]) === true)
        {
            $rrfAmount = abs(Helper::getIntegerFormattedAmount($row[ReconciliationFields::RRF_AMOUNT]));
        }

        else
        {
            $rrfAmount = 0;
        }

        $msfAmount = abs(Helper::getIntegerFormattedAmount($row[ReconciliationFields::MSF_AMOUNT]));

        // This $tax is already in paisa
        $tax = $this->getGatewayServiceTax($row);

        $fee = $lateSettlementFee + $rrfAmount + $msfAmount + $tax;

        return $fee;
    }

    /**
     * Returns the card_fss trans id
     * @param $row
     * @return string|null
     */
    protected function getGatewayTransactionId(array $row)
    {
        return trim(str_replace("'", '', $row[ReconciliationFields::PG_TRANSACTION_ID] ?? null));
    }

    protected function getGatewaySettledAt(array $row)
    {
        if (empty($row[ReconciliationFields::PAYMENT_DATE]) === false)
        {
            return Carbon::createFromFormat('d-m-Y', $row[ReconciliationFields::PAYMENT_DATE], Timezone::IST)->timestamp;
        }
    }

    protected function getAuthCode($row)
    {
        if (empty($row[ReconciliationFields::AUTH_CODE]) === true)
        {
            $this->reportMissingColumn($row, ReconciliationFields::AUTH_CODE);

            return null;
        }

        return $row[ReconciliationFields::AUTH_CODE];
    }

    /**
     * In MIS file, we are not receiving ARN hence sstoring RRN in reference1 field of payment entity.
     * This is done because for reporting purposes, we need reference number in payment entity.
     * @param $rowDetails
     */
    protected function setPaymentAcquirerData($rowDetails)
    {
        if (empty($rowDetails[BaseReconciliate::REFERENCE_NUMBER]) === false)
        {
            $this->setPaymentReference1($rowDetails[BaseReconciliate::REFERENCE_NUMBER]);
        }

        if (empty($rowDetails[BaseReconciliate::AUTH_CODE]) === false)
        {
            $this->setPaymentReference2($rowDetails[BaseReconciliate::AUTH_CODE]);
        }
    }

    /**
     * The card_fss entity ref column should be updated with rrn
     * It should be set as ref setReferenceNumberInGateway
     * in the gateway entity.
     * @param string       $referenceNumber
     * @param PublicEntity $gatewayPayment CardFss Entity
     * */
    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayPayment)
    {
        $gatewayPayment->setRef($referenceNumber);
    }

    /**
     * Sets the given gateway payment date as postdate in card_fss.
     *
     * @param string       $gatewayPaymentDate
     * @param PublicEntity $gatewayPayment
     */
    protected function setGatewayPaymentDateInGateway(string $gatewayPaymentDate, PublicEntity $gatewayPayment)
    {
        $gatewayPayment->setPostDate($gatewayPaymentDate);
    }
}
