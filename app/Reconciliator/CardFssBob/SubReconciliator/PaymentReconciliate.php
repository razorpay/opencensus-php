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
use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\SubReconciliator;
use RZP\Reconciliator\Base\SubReconciliator\Helper as Helper;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class PaymentReconciliate extends SubReconciliator\PaymentReconciliate
{
    const ONUS_INDICATOR = 'yes';

    public function getPaymentId(array $row)
    {

     $columnPaymentId = array_first(ReconciliationFields::MERCHANT_TRACK_ID, function ($col) use ($row)
        {
            return (empty($row[$col]) === false);
        });

        $paymentId = $row[$columnPaymentId] ?? null;


      // checking the transaction status from  mis if it is a failed transaction then skipping further processing for tranaction and marking recon failed

       $columnTransactionGatewayStatus = array_first(ReconciliationFields::APPROVED_INDICATOR, function ($col) use ($row)
        {
            return (isset($row[$col]) === true);
        });

        $gatewayStatus = $row[$columnTransactionGatewayStatus] ?? null;

        if (strtolower($gatewayStatus)  !== 'approved')
        {

            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'  => Base\InfoCode::MIS_FILE_PAYMENT_FAILED ,
                    'payment_id' => $paymentId,
                    'gateway'    => $this->gateway
                ]);

            $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::MIS_FILE_PAYMENT_FAILED);

            return null;
        }

        return trim(str_replace("'", '', $paymentId));
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
                    'info_code'         => InfoCode::AMOUNT_MISMATCH,
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

    protected function getReconPaymentAmount(array $row)
    {
        $columnAmount = array_first(ReconciliationFields::TRANSACTION_AMOUNT, function ($col) use ($row)
        {
            return (isset($row[$col]) === true);
        });

        return Helper::getIntegerFormattedAmount($row[$columnAmount] ?? null);
    }

    protected function validatePaymentCurrencyEqualsReconCurrency(array $row) : bool
    {
        $convertCurrency = $this->payment->getConvertCurrency();

        $expectedCurrency = ($convertCurrency === true) ? Currency::INR : $this->payment->getGatewayCurrency();

        $reconCurrency = $this->getReconCurrency($row);

        if (($expectedCurrency !== $reconCurrency) and ( empty($reconCurrency) !== true))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'info_code'         => InfoCode::CURRENCY_MISMATCH,
                    'expected_currency' => $expectedCurrency,
                    'recon_currency'    => $reconCurrency,
                    'gateway'           => $this->gateway
                ]);

            return false;
        }

        return true;
    }

    protected function getReconCurrency($row)
    {
        $columnReconCurrency = array_first(ReconciliationFields::TRANSACTION_CURRENCY_CODE, function ($col) use ($row)
        {
            return (empty($row[$col]) === false);
        });

        return trim($row[$columnReconCurrency] ?? null);
    }

    public function getReferenceNumber($row)
    {
        $columnRrn = array_first(ReconciliationFields::RRN, function ($col) use ($row)
        {
            return (empty($row[$col]) === false);
        });

        $rrn = $row[$columnRrn] ?? null;

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

        // Not reporting missing rrn value in MIS, because it
        // is quite frequent, and doesn't hamper recon flow.
        if ((empty($rrn) === false)                            and
            (strtolower($onusIndicator) === self::ONUS_INDICATOR))
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
        $columnOnusIndicator = array_first(ReconciliationFields::ONUS_INDICATOR, function ($col) use ($row)
        {
            return (empty($row[$col]) === false);
        });

        return strtolower($row[$columnOnusIndicator] ?? '');
    }

    public function getGatewayPayment($paymentId)
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
        $columnTranDate = array_first(ReconciliationFields::TRANSACTION_DATE, function ($col) use ($row)
        {
            return (empty($row[$col]) === false);
        });

        return $row[$columnTranDate] ?? null;
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
        $columnPaymentMethod = array_first(ReconciliationFields::PAYMENT_METHOD, function ($col) use ($row)
        {
            return (empty($row[$col]) === false);
        });

        $cardType = explode(' ', strtolower($row[$columnPaymentMethod] ?? null))[0];

        if (in_array($cardType, [BaseReconciliate::DEBIT, BaseReconciliate::CREDIT]) === false)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'info_code'       => InfoCode::UNKNOWN_CARD_TYPE,
                    'recon_card_type' => $cardType,
                    'payment_id'      => $this->payment->getId(),
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
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'  => InfoCode::UNEXPECTED_CARD_LOCALE,
                    'payment_id' => $this->payment->getId(),
                    'gateway'    => $this->gateway,
                    'message'    => 'unable to figure out card locale',
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

        $columnMsfAmount = array_first(ReconciliationFields::MSF_AMOUNT, function ($col) use ($row)
        {
            return (isset($row[$col]) === true);
        });

        $row[ReconciliationFields::CSF_TAX] = ($row[ReconciliationFields::CSF_TAX] === '') ? 0 : $row[ReconciliationFields::CSF_TAX];

        $csfTax = (isset($row[$columnMsfAmount]) === true) ? abs($row[ReconciliationFields::CSF_TAX] ?? 0) : 0;

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
        $columnMsfAmount = array_first(ReconciliationFields::MSF_AMOUNT, function ($col) use ($row)
        {
            return (isset($row[$col]) === true);
        });

        if ($columnMsfAmount === null)
        {
            $this->reportMissingColumn($row, implode(',', ReconciliationFields::MSF_AMOUNT));
        }

        $columnLateSettlementFee = array_first(ReconciliationFields::LATE_SETTLEMENT_FEE_AMOUNT, function ($col) use ($row)
        {
            return (isset($row[$col]) === true);
        });

        if ($columnLateSettlementFee !== null)
        {
            $lateSettlementFee = Helper::getIntegerFormattedAmount($row[$columnLateSettlementFee]);
        }
        else
        {
            $lateSettlementFee = 0;
        }

        $columnRrfAmount = array_first(ReconciliationFields::RRF_AMOUNT, function ($col) use ($row)
        {
            return (isset($row[$col]) === true);
        });

        if ($columnRrfAmount !== null)
        {
            $rrfAmount = Helper::getIntegerFormattedAmount($row[$columnRrfAmount]);
        }
        else
        {
            $rrfAmount = 0;
        }

        $msfAmount = Helper::getIntegerFormattedAmount($row[$columnMsfAmount]);

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
        $columnPgTranId = array_first(ReconciliationFields::PG_TRANSACTION_ID, function ($col) use ($row)
        {
            return (empty($row[$col]) === false);
        });

        return trim(str_replace("'", '', $row[$columnPgTranId] ?? null));
    }

    protected function getGatewaySettledAt(array $row)
    {
        $columnGatewaySettledDate = array_first(ReconciliationFields::GATEWAY_SETTLED_DATE, function ($col) use ($row)
        {
            return (empty($row[$col]) === false);
        });

        if (empty($row[$columnGatewaySettledDate]) === true)
        {
            return null;
        }

        $gatewaySettledAtTimestamp = null;

        $settledAt = $row[$columnGatewaySettledDate];

        try
        {
            $gatewaySettledAtTimestamp = Carbon::parse($settledAt)->setTimezone(Timezone::IST)->getTimestamp();
        }
        catch (\Exception $ex)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'         => InfoCode::INCORRECT_DATE_FORMAT,
                    'settled_at'        => $settledAt,
                    'payment_id'        => $this->payment->getId(),
                    'gateway'           => $this->gateway,
                ]);
        }

        return $gatewaySettledAtTimestamp;
    }

    protected function getAuthCode($row)
    {
        $columnAuthCode = array_first(ReconciliationFields::AUTH_CODE, function ($col) use ($row)
        {
            return (empty($row[$col]) === false);
        });

        if ($columnAuthCode === null)
        {
            $this->reportMissingColumn($row, implode(',', ReconciliationFields::AUTH_CODE));

            return null;
        }

        return $row[$columnAuthCode];
    }

    /**
     * In MIS file, we are not receiving ARN hence storing RRN in reference1 field of payment entity.
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
