<?php

namespace RZP\Reconciliator\VasAxis\SubReconciliator;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Action;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Gateway\Terminal\Constants;
use Razorpay\Spine\Exception\DbQueryException;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_TERMINAL_NUMBER        = 'term_id';
    const COLUMN_TXN_DATE               = 'tran_date';
    const COLUMN_CARD_TYPE              = 'card_type';
    const COLUMN_TXN_METHOD             = 'ti';
    const COLUMN_CARD_NUMBER            = 'card_no';
    const COLUMN_AUTH_CODE              = 'approve_code';
    const COLUMN_RRN                    = 'rrn';
    const COLUMN_PAYMENT_AMOUNT         = 'gross_amt';
    const COLUMN_GATEWAY_AMOUNT         = 'net_amt';
    const COLUMN_FEE                    = 'mdr';
    const COLUMN_GST                    = 'gst';
    const COLUMN_MERCHANT_ID            = 'mid';
    const COLUMN_SETTLED_AT             = 'process_date';
    const COLUMN_GATEWAY_UTR            = 'utr';

    // Different methods
    const BHARAT_QR                     = 'Bharat QR';
    const UPI                           = 'UPI';
    const POS                           = 'POS';

    /**
     * This is to bypass UPI payment rows in MIS file.
     * UPI payment have 'UPI' in the card type column.
     */
    const INVALID_CARD_TYPES = [
        self::UPI,
    ];

    const CARD_TYPE_MAP = [
        'C' =>  Base\Reconciliate::CREDIT,
        'D' =>  Base\Reconciliate::DEBIT,
    ];

    const CARD_TRIVIA_MAP = [
        'M' => Constants::MASTERCARD,
        'V' => Constants::VISA,
        'R' => Constants::RUPAY,
    ];

    protected function getPaymentId(array $row)
    {
        return $this->getPaymentIdByMethod($row);
    }

    /**
     * In case of bharat qr payments
     * we use rrn to get worldLine entity to fetch payment_id
     *
     * @param array $row
     * @return mixed|null
     */
    protected function getPaymentIdByMethod(array $row)
    {
        $paymentId = null;

        if ($row[self::COLUMN_TXN_METHOD] === self::BHARAT_QR)
        {
            if (empty($row[self::COLUMN_RRN]) === true)
            {
                return null;
            }

            try
            {
                $worldLine = $this->repo->worldline->findByReferenceNumberAndAction($row[self::COLUMN_RRN], Action::AUTHORIZE);

                $paymentId = $worldLine->getPaymentId();

                $this->gatewayPayment = $worldLine;
            }
            catch (DbQueryException $ex)
            {
                $this->messenger->raiseReconAlert(
                    [
                        'info_code'              => Base\InfoCode::UNEXPECTED_PAYMENT,
                        'payment_reference_id'   => $row[self::COLUMN_RRN],
                        'gateway'                => $this->gateway,
                        'batch_id'               => $this->batch->getId(),
                    ]
                );
            }
        }

        return $paymentId;
    }

    public function getGatewayPayment($paymentId)
    {
        //
        // we have already set the $gatewayPayment
        // in getPaymentIdByMethod(), simply return it.
        //
        return $this->gatewayPayment;
    }

    protected function getGatewayFee($row)
    {
        $columnFee = $row[self::COLUMN_FEE] ?? null;

        $fee =  Base\SubReconciliator\Helper::getIntegerFormattedAmount($columnFee);

        $serviceTax = $this->getGatewayServiceTax($row);

        $fee += $serviceTax;

        return $fee;
    }

    protected function getGatewayServiceTax($row)
    {
        $columnGst = $row[self::COLUMN_GST] ?? null;

        $serviceTax = Base\SubReconciliator\Helper::getIntegerFormattedAmount($columnGst);

        return $serviceTax;
    }

    //
    // We want to save rrn in payment reference_1
    // so set the rrn as arn in $rowDetails
    //
    protected function getArn($row)
    {
        return $row[self::COLUMN_RRN] ?? null;
    }

    protected function getAuthCode($row)
    {
        return $row[self::COLUMN_AUTH_CODE] ?? null;
    }

    protected function getGatewayUtr($row)
    {
        return $row[self::COLUMN_GATEWAY_UTR] ?? null;
    }

    protected function getGatewaySettledAt(array $row)
    {
        $gatewaySettledAtTimestamp = null;

        $settledAt = $row[self::COLUMN_SETTLED_AT] ?? null;

        try
        {
            $gatewaySettledAtTimestamp = Carbon::parse($settledAt)->setTimezone(Timezone::IST)->getTimestamp();
        }
        catch (\Exception $ex)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'     => Base\InfoCode::INCORRECT_DATE_FORMAT,
                    'settled_at'    => $settledAt,
                    'payment_id'    => $this->payment->getId(),
                    'gateway'       => $this->gateway,
                ]);
        }

        return $gatewaySettledAtTimestamp;
    }

    protected function getGatewayAmount(array $row)
    {
        $gatewayAmt =  $row[self::COLUMN_PAYMENT_AMOUNT] ?? null;

        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($gatewayAmt);
    }

    protected function getCardDetails($row)
    {
        // If the card type (debit/credit) is not present, we don't want
        // to store any of the other card details.
        if ((empty($row[self::COLUMN_CARD_TYPE]) === true) or
            (in_array($row[self::COLUMN_CARD_TYPE], self::INVALID_CARD_TYPES, true) === true))
        {
            return [];
        }

        $columnCardType     = strtoupper($row[self::COLUMN_CARD_TYPE]);

        $cardType           = $this->getCardType($columnCardType);
        $cardTrivia         = $this->getCardTrivia($columnCardType);

        return [
            Base\Reconciliate::CARD_TYPE   => $cardType,
            Base\Reconciliate::CARD_TRIVIA => $cardTrivia,
        ];
    }

    protected function getCardType($columnCardType)
    {
        $cardTypeLastChar = $columnCardType[1] ?? null;

        $cardType = self::CARD_TYPE_MAP[$cardTypeLastChar] ?? null;

        if ($cardType === null)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'trace_code'      => TraceCode::RECON_PARSE_ERROR,
                    'message'         => 'Unable to figure out the card type.',
                    'recon_card_type' => $columnCardType,
                    'payment_id'      => $this->payment->getId(),
                    'gateway'         => $this->gateway,
                ]);
        }

        return $cardType;
    }

    protected function getCardTrivia($columnCardType)
    {
        // Decide based on first character
        $cardTypeFirstChar = $columnCardType[0];

        $cardTrivia = self::CARD_TRIVIA_MAP[$cardTypeFirstChar] ?? null;

        if ($cardTrivia === null)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'trace_code'        => TraceCode::RECON_PARSE_ERROR,
                    'message'           => 'Unable to get the card trivia. This is unexpected.',
                    'info_code'         => 'CARD_TRIVIA_ABSENT',
                    'recon_card_trivia' => $columnCardType,
                    'payment_id'        => $this->payment->getId(),
                    'gateway'           => $this->gateway,
                ]);
        }

        return $cardTrivia;
    }

    /**
     * Saves gateway UTR , as well as Auth code in the gateway entity.
     * (Not adding separate method to set auth code)
     *
     * @param array $rowDetails
     * @param PublicEntity $gatewayPayment
     */
    protected function persistGatewayUtr(array $rowDetails, PublicEntity $gatewayPayment)
    {
        if (empty($rowDetails[Base\Reconciliate::GATEWAY_UTR]) === false)
        {
            $this->setGatewayUtrInGateway($rowDetails[Base\Reconciliate::GATEWAY_UTR], $gatewayPayment);
        }

        if (empty($rowDetails[Base\Reconciliate::AUTH_CODE]) === false)
        {
            $this->setAuthCodeInGateway($rowDetails[Base\Reconciliate::AUTH_CODE], $gatewayPayment);
        }
    }

    /**
     * Save gateway UTR, Raise alert if existing UTR
     * not matching with MIS data
     *
     * @param string $reconGatewayUtr
     * @param PublicEntity $gatewayPayment
     */
    protected function setGatewayUtrInGateway(string $reconGatewayUtr, PublicEntity $gatewayPayment)
    {
        $dbReferenceNumber = trim($gatewayPayment->getGatewayUtr());

        if ((empty($dbReferenceNumber) === false) and
            ($dbReferenceNumber !== $reconGatewayUtr))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => $infoCode,
                    'message'                   => 'Gateway UTR in db is not same as in recon',
                    'payment_id'                => $this->payment->getId(),
                    'amount'                    => $this->payment->getAmount(),
                    'db_reference_number'       => $dbReferenceNumber,
                    'recon_reference_number'    => $reconGatewayUtr,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $gatewayPayment->setGatewayUtr($reconGatewayUtr);
    }

    /**
     * Save auth code, Raise alert if existing auth code
     * not matching with MIS data
     *
     * @param string $reconAuthCode
     * @param PublicEntity $gatewayPayment
     */
    protected function setAuthCodeInGateway(string $reconAuthCode, PublicEntity $gatewayPayment)
    {
        $dbReferenceNumber = trim($gatewayPayment->getAuthCode());

        if ((empty($dbReferenceNumber) === false) and
            ($dbReferenceNumber !== $reconAuthCode))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => $infoCode,
                    'message'                   => 'Auth Code in db is not same as in recon',
                    'payment_id'                => $this->payment->getId(),
                    'amount'                    => $this->payment->getAmount(),
                    'db_reference_number'       => $dbReferenceNumber,
                    'recon_reference_number'    => $reconAuthCode,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $gatewayPayment->setAuthCode($reconAuthCode);
    }
}
