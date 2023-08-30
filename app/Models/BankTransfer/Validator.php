<?php

namespace RZP\Models\BankTransfer;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;

class Validator extends Base\Validator
{
    /**
     * @var Entity
     */
    protected $entity;

    const IFSC_LENGTH = 11;
    const VA_CURRENCY = 'va_currency';
    const ACCEPT_B2B_TNC = 'accept_b2b_tnc';

    protected static $createRules = [
        Entity::PAYER_NAME              => 'nullable|string|max:100',
        Entity::PAYER_ACCOUNT           => 'nullable|string|max:40',
        Entity::PAYER_ACCOUNT_TYPE      => 'nullable|string|max:40',
        Entity::PAYER_IFSC              => 'nullable|string',
        Entity::PAYER_ADDRESS           => 'nullable|string',
        Entity::PAYEE_NAME              => 'nullable|string|max:100',
        Entity::PAYEE_ACCOUNT           => 'required|string|max:40',
        Entity::PAYEE_IFSC              => 'required|string|size:' . self::IFSC_LENGTH,
        Entity::MODE                    => 'required|custom',
        Entity::REQ_UTR                 => 'required|string|max:255',
        Entity::TIME                    => 'required',
        Entity::AMOUNT                  => 'required|numeric|min:0',
        Entity::CURRENCY                => 'nullable|in:INR',
        Entity::DESCRIPTION             => 'nullable|string|max:255',
        Entity::ATTEMPT                 => 'nullable|integer',
        Entity::NARRATION               => 'nullable|string',
        Entity::FIRST_TIME_ON_TEST_MODE => 'sometimes|boolean',
    ];

    public static $rblRules = [
        'ServiceName'                       => 'required|in:VirtualAccount',
        'Action'                            => 'required|in:VirtualAccountTransaction',
        'Data'                              => 'required|array',
        'Data.0.messageType'                => 'required|string',
        'Data.0.amount'                     => 'required|string',
        'Data.0.UTRNumber'                  => 'required|string',
        'Data.0.beneficiaryAccountNumber'   => 'required|string|alpha_num',
        'Data.0.senderIFSC'                 => 'nullable|string',
        'Data.0.senderAccountNumber'        => 'nullable|string',
        'Data.0.senderName'                 => 'nullable|string',
        'Data.0.creditAccountNumber'        => 'required|string',
    ];

    public static $iciciRules = [
        'Virtual_Account_Number_Verification_IN'                    => 'required|array',
        'Virtual_Account_Number_Verification_IN.0.client_code'      => 'required|string',
        'Virtual_Account_Number_Verification_IN.0.payee_account'    => 'required|string',
        'Virtual_Account_Number_Verification_IN.0.amount'           => 'required|string',
        'Virtual_Account_Number_Verification_IN.0.mode'             => 'required|string',
        'Virtual_Account_Number_Verification_IN.0.transaction_id'   => 'required|string',
        'Virtual_Account_Number_Verification_IN.0.payer_name'       => 'required|string',
        'Virtual_Account_Number_Verification_IN.0.payer_account'    => 'required|string',
        'Virtual_Account_Number_Verification_IN.0.payer_ifsc'       => 'required|string',
        'Virtual_Account_Number_Verification_IN.0.description'      => 'nullable|string',
        'Virtual_Account_Number_Verification_IN.0.date'             => 'required|string',
    ];

    protected static $editBankTransferRules = [
        Entity::PAYER_NAME              => 'sometimes|string|max:100',
        Entity::PAYER_ACCOUNT           => 'sometimes|string|max:20',
        Entity::PAYER_IFSC              => 'sometimes|alpha_num|size:'.self::IFSC_LENGTH,
        Entity::PAYER_BANK_ACCOUNT_ID   => 'sometimes|integer|size:14',
    ];

    protected static $createValidators = [
        Entity::PAYER_IFSC,
    ];

    protected static $pendingBankTransferRules = [
        \RZP\Models\BankTransferRequest\Entity::BANK_TRANSFER_REQUEST_ID   => 'required|string',
    ];

    protected static $validateDuplicateReqRules = [
        Entity::AMOUNT         => 'required|numeric|min:0',
        Entity::REQ_UTR        => 'required|string|max:255',
        Entity::PAYEE_ACCOUNT  => 'required|string|max:40',
    ];

    public static $createAccountForCurrencyCloudRules = [
        self::VA_CURRENCY       => 'sometimes|string|max:5',
        self::ACCEPT_B2B_TNC    => 'sometimes|boolean',
    ];

    protected function validateMode($attribute, $mode)
    {
        if (Mode::isValid($mode) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid bank transfer mode',
                $attribute,
                $mode);
        }
    }

    const MODES_WITHOUT_IFSC = [
        Mode::IMPS,
        Mode::UPI,
        Mode::IFT,
    ];

    protected function validatePayerIfsc($input)
    {
        // We currently aren't getting the actual payer_ifsc for IMPS payments.
        if ((strlen($input[Entity::PAYER_IFSC]) !== self::IFSC_LENGTH) and
            (in_array($input[Entity::MODE], self::MODES_WITHOUT_IFSC, true) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'IFSC is of invalid length',
                Entity::PAYER_IFSC,
                $input[Entity::PAYER_IFSC]);
        }
    }

    public function validatePaymentForRefund(Payment\Entity $payment)
    {
        $bankTransfer = $payment->bankTransfer;

        if ($payment->qrPayment !== null)
        {
            $bankTransfer = $payment->qrPayment;
        }

        if ($bankTransfer->payerBankAccount === null)
        {
            throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED,
                    $bankTransfer, ['method' => $payment->getMethod()]);
        }
    }

    protected static $crossBorderInvoiceWorkflowCallbackRules = [
        'payment_id'                       => 'required|string|size:14',
        'workflow_status'                  => 'required|string|in:approved,rejected',
        'merchant_id'                      => 'required|string|size:14',
        'priority'                         => 'required|string|in:P0,P1',
    ];
}
