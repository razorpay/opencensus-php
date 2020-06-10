<?php

namespace RZP\Reconciliator\VasAxis\SubReconciliator;

use Carbon\Carbon;
use RZP\Models\BharatQr;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Action;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Worldline\Entity;
use RZP\Models\Base\PublicEntity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Gateway\Terminal\Constants;

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
    const COLUMN_INTL_FLAG              = 'intl_flag';
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

    const METHOD_TO_TXN_TYPE_MAP = [
        self::BHARAT_QR => '1',
        self::UPI       => '2',
    ];

    //
    // This field is manually added in MIS for creating unexpected payment
    // against a reference number (rrn). Its the RRN of the payment to be
    // created but still taking in input as a confirmation token of unexpected
    // payment creation.
    //
    const UNEXPECTED_PAYMENT_REF_ID = 'unexpected_payment_ref_id';

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

            $rrn = $row[self::COLUMN_RRN];

            $worldLine = $this->repo->worldline->findByReferenceNumberAndAction($rrn, Action::AUTHORIZE);

            if (empty($worldLine) === false)
            {
                $paymentId = $worldLine->getPaymentId();

                $this->gatewayPayment = $worldLine;
            }
            else
            {
                if (empty($row[self::UNEXPECTED_PAYMENT_REF_ID]) === false)
                {
                    $paymentId = $this->attemptToCreateUnexpectedPayment($rrn, $row);

                    if (empty($paymentId) === false)
                    {
                        // Payment has been created. Set the gateway payment
                        $this->gatewayPayment = $this->repo->worldline->findByReferenceNumberAndAction($rrn, Action::AUTHORIZE);
                    }
                }
                else
                {
                    $this->messenger->raiseReconAlert(
                        [
                            'info_code'              => Base\InfoCode::UNEXPECTED_PAYMENT,
                            'payment_reference_id'   => $row[self::COLUMN_RRN],
                            'gateway'                => $this->gateway,
                            'batch_id'               => $this->batchId,
                        ]
                    );
                }
            }
        }

        return $paymentId;
    }

    /**
     * Attempts to create unexpected payment
     * Returns payment_id if attempt is successful,
     * null otherwise.
     * @param string $rrn
     * @param array $input
     * @return string|null
     */
    protected function attemptToCreateUnexpectedPayment(string $rrn, array $input)
    {
        $paymentId = null;

        $callbackInput = $this->createCallbackdata($input);

        if (empty($callbackInput) === true)
        {
            return null;
        }

        $this->trace->info(
            TraceCode::RECON_INFO,
            [
                'infoCode'                  => Base\InfoCode::RECON_UNEXPECTED_PAYMENT_CREATE_INITIATED,
                'rrn'                       => $rrn,
                'gateway'                   => $this->gateway,
                'batch_id'                  => $this->batchId,
            ]);

        try
        {
            $response = (new BharatQr\Service)->processPayment($callbackInput, Gateway::WORLDLINE);

            // Fetch and trace alert if payment still not created
            $worldLine = $this->repo->worldline->findByReferenceNumberAndAction($rrn, Action::AUTHORIZE);

            if ($worldLine === null)
            {
                $this->trace->info(
                    TraceCode::RECON_INFO_ALERT,
                    [
                        'infoCode'  => Base\InfoCode::RECON_UNEXPECTED_PAYMENT_CREATION_FAILED,
                        'rrn'       => $rrn,
                        'response'  => $response,
                        'gateway'   => $this->gateway,
                        'batch_id'  => $this->batchId,
                    ]);
            }
            else
            {
                $paymentId = $worldLine->getPaymentId();

                $this->gatewayPayment = $worldLine;

                $this->trace->info(
                    TraceCode::RECON_INFO,
                    [
                        'infoCode'      => Base\InfoCode::RECON_UNEXPECTED_PAYMENT_CREATED,
                        'payment_id'    => $paymentId,
                        'rrn'           => $rrn,
                        'gateway'       => $this->gateway,
                        'batch_id'      => $this->batchId,
                    ]);
            }
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::RECON_UNEXPECTED_PAYMENT_CREATION_FAILED,
                [
                    'rrn'       => $rrn,
                    'gateway'   => $this->gateway,
                    'batch_id'  => $this->batchId,
                ]
            );
        }

        return $paymentId;
    }

    // Prepare and return callback input required
    // for creating unexpected payment
    protected function createCallbackData(array $input)
    {
        // Only supporting domestic payment for now
        if ((empty($input[self::COLUMN_INTL_FLAG]) === false) and
            ($input[self::COLUMN_INTL_FLAG]) === 'N')
        {
            $currencyCode = BharatQr\Constants::CURRENCY_CODE;
        }
        else
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code' => Base\InfoCode::RECON_UNEXPECTED_PAYMENT_CREATION_FAILED,
                    'message'   => 'MIS rows says International, not creating payment.',
                    'rrn'       => $input[self::COLUMN_RRN],
                    'gateway'   => $this->gateway,
                    'batch_id'  => $this->batchId,

                ]);

            return [];
        }

        $cardNumber = $this->getCardNumber($input);

        $cardFirst6 = substr($cardNumber, 0, 6);
        $cardLast4 = substr($cardNumber, 12, 4);

        $mpan = $this->getMpanForTerminal($input);

        return [
            'txn_currency'            => $currencyCode,
            'transaction_type'        => self::METHOD_TO_TXN_TYPE_MAP[self::BHARAT_QR],
            'customer_name'           => '',
            'secondary_id'            => null,
            'bank_code'               => '0031',                    // Bank code for Axis is 0031
            'aggregator_id'           => null,
            'primary_id'              => $input[Entity::PRIMARY_ID] ?? '',
            'auth_code'               => $input[self::COLUMN_AUTH_CODE],
            'ref_no'                  => strval($input[self::COLUMN_RRN]),
            'settlement_amount'       => $input[self::COLUMN_GATEWAY_AMOUNT],
            'mid'                     => strval($input[self::COLUMN_MERCHANT_ID]),
            'txn_amount'              => $input[self::COLUMN_PAYMENT_AMOUNT],
            'mpan'                    => $mpan,
            'time_stamp'              => Carbon::now(Timezone::IST)->format('YmdHis'),
            'consumer_pan'            => null,
            'card_first6'             => $cardFirst6,
            'card_last4'              => $cardLast4,
        ];
    }

    protected function getMpanForTerminal(array $input)
    {
        $gatewayMerchantId = $input[self::COLUMN_MERCHANT_ID];
        $gatewayTerminalId = $input[self::COLUMN_TERMINAL_NUMBER];

        // For worldline, every terminal have unique gateway_terminal_id
        $terminal = $this->repo->terminal->findTerminalByGatewayMerchantIdAndGatewayTerminalId($gatewayMerchantId,
                                                                                                        $gatewayTerminalId,
                                                                                                Gateway::WORLDLINE
        );

        //
        // In future, we might allow terminal to be created with only visa mpan,
        // allowing mc_mpan to be null. So Loop through all networks (mc, visa, rupay)
        // and return the first non-empty mpan.
        //

        if (empty($terminal) === false)
        {
            if (empty($terminal->getMCMpan()) === false)
            {
                return $terminal->getMCMpan();
            }
            else if (empty($terminal->getVisaMpan()) === false)
            {
                return $terminal->getVisaMpan();
            }
            else if (empty($terminal->getRupayMpan()) === false)
            {
                return $terminal->getRupayMpan();
            }
        }

        return null;
    }

    protected function getCardNumber(array $row)
    {
        return $row[self::COLUMN_CARD_NUMBER] ?? null;
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
