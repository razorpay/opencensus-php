<?php

namespace RZP\Reconciliator\Bob;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Trace\TraceCode;
use RZP\Models\Bank\IFSC;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Base\PublicEntity;
use RZP\Gateway\Card\Fss\Status;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    public function getPaymentId(array $row)
    {
        return $row[ReconcilationFields::MERCHANT_TRACK_ID];
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
                    'info_code'         => Base\InfoCode::AMOUNT_MISMATCH,
                    'message'           => 'Payment amount mismatch',
                    'expected_amount'   => $paymentAmount,
                    'currency'          => $this->payment->getCurrency(),
                    'row'               => $row,
                    'gateway'           => $this->gateway
                ]);

            return false;
        }
        return true;
    }

    protected function getReconPaymentAmount($row)
    {
        return Base\Helper::getIntegerFormattedAmount($row[ReconcilationFields::TRANSACTION_AMOUNT]);
    }

    protected function validatePaymentCurrencyEqualsReconCurrency(array $row) : bool
    {
        $expectedCurrency = $this->payment->getCurrency();

        $reconCurrency = $this->getReconCurrencyCode($row);

        if ($expectedCurrency !== $reconCurrency)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'message'           => Base\InfoCode::CURRENCY_MISMATCH,
                    'expected_currency' => $expectedCurrency,
                    'recon_currency'    => $reconCurrency,
                    'row'               => $row,
                    'gateway'           => $this->gateway
                ]);

            return false;
        }

        return true;
    }

    protected function getReconCurrencyCode($row)
    {
        return $row[ReconcilationFields::TRANSACTION_CURRENCY_CODE];
    }

    /**
     * It is present as Retrieval Reference Number in recon file
     * It should be set as ref setReference Number In Gateway
     * in the gateway entity.
     * @param $row
     * @return string
     */
    public function getReferenceNumber($row)
    {
        return $row[ReconcilationFields::RRN] ?? null;
    }

    /**
     * Since we need to update rrn we would need gatewayPayment
     * @param $row
     * @return string
     */
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
        return $row[ReconcilationFields::TRANSACTION_DATE];
    }

    protected function getCardDetails($row)
    {
        return [
            BaseReconciliate::CARD_TYPE  => $this->getCardType($row),
            BaseReconciliate::CARD_LOCALE => $this->getCardLocale($row),
            BaseReconciliate::ISSUER      => $this->getIssuer($row),
            BaseReconciliate::CARD_TRIVIA => $this->getCardTrivia($row),
        ];
    }

    /**
     * Returns if the card is debit or credit from Payment Method
     * @param array $row Card type would be  Credit Card, Debit Card
     * @return strings|null if any card type is present
     */
    protected function getCardType($row)
    {
        $cardType = explode(' ', strtolower($row[ReconcilationFields::PAYMENT_METHOD]))[0];

        if (in_array($cardType, [BaseReconciliate::DEBIT, BaseReconciliate::CREDIT]) === false)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_PARSE_ERROR,
                    'message'         => Base\InfoCode::CARD_TYPE_ABSENT,
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
      $cardLocale = strtolower($row[ReconcilationFields::DESTINATION]);

        if (in_array($cardLocale, [BaseReconciliate::DOMESTIC, BaseReconciliate::INTERNATIONAL]) === false)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_PARSE_ERROR,
                    'message'         => Base\InfoCode::CARD_LOCALE_MISSING,
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
        $onusIndicator = $row[ReconcilationFields::ONUS_INDICATOR];

        if ($onusIndicator === 'YES')
        {
            return IFSC::BARB;
        }

        return null;
    }

    /**
     * Returns the interchange type eg Visa Traditional
     * @param $row
     * @return string|null
     */
    protected function getCardTrivia($row)
    {
        return $row[ReconcilationFields::INTERCHANGE_CATEGORY] ?? null;
    }

    /**
     * Returns the service tax gst + csf tax. csf tax is usually zero
     * @param $row
     * @return integer
     */
    protected function getGatewayServiceTax($row)
    {
        $tax = abs($row[ReconcilationFields::GST]) + abs($row[ReconcilationFields::CSF_TAX]);

        return Base\Helper::getIntegerFormattedAmount($tax);
    }

    protected function getGatewayFee($row)
    {
        $lateSettelementFee = $row[ReconcilationFields::LATE_SETTLEMENT_FEE_AMOUNT];

        $rrfAmount = $row[ReconcilationFields::RRF_AMOUNT];

        $msfAmount = abs($row[ReconcilationFields::MSF_AMOUNT]);

        $tax = $this->getGatewayServiceTax($row);

        $fee = $lateSettelementFee + $rrfAmount + $msfAmount + $tax;

        return Base\Helper::getIntegerFormattedAmount($fee);
    }

    /**
     * Returns the card_fss trans id
     * @param $row
     * @return string|null
     */
    protected function getGatewayTransactionId(array $row)
    {
        return $row[ReconcilationFields::PG_TRANSACTION_ID] ?? null;
    }

    protected function getGatewaySettledAt(array $row)
    {
        if(empty($row[ReconcilationFields::PAYMENT_DATE]) === false)
        {
            return Carbon::createFromFormat('d-m-Y', $row[ReconcilationFields::PAYMENT_DATE], Timezone::IST)->timestamp;
        }
    }

    protected function getAuthCode($row)
    {
        if (empty($row[ReconcilationFields::AUTH_CODE]) === true)
        {
            $this->reportMissingColumn($row, ReconcilationFields::AUTH_CODE);

            return null;
        }

        return $row[ReconcilationFields::AUTH_CODE];
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
