<?php

namespace RZP\Models\BankTransfer;

use App;
use Razorpay\IFSC\IFSC;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;

class PayerBankAccount extends Base\Core
{
    /**
     * Bank account creation requires limited fields, since we
     * are using the addVirtualBankAccount validation rules.
     *
     * @param Entity $bankTransfer
     * @param array  $bankAccountInput
     *
     * @return array
     */
    public static function getBankAccountInput(
        Entity $bankTransfer,
        array $bankAccountInput = [])
    {
        $bankAccountDetailsFromBankTransfer = [
            BankAccount\Entity::IFSC_CODE        => self::getPayerIfsc($bankTransfer),
            BankAccount\Entity::ACCOUNT_NUMBER   => self::getPayerAccount($bankTransfer),
            BankAccount\Entity::BENEFICIARY_NAME => self::getLabel($bankTransfer),
        ];

        return array_merge($bankAccountDetailsFromBankTransfer, $bankAccountInput);
    }

    /**
     * Label (or beneficiary name) for created payer bank account. Use the
     * payer_name given by Kotak if available, else merchant name. Sanitize!
     *
     * @param Entity $bankTransfer
     *
     * @return string
     */
    protected static function getLabel(Entity $bankTransfer)
    {
        $label = $bankTransfer->getPayerName();

        $label = preg_replace('/[^a-zA-Z0-9 ]+/', '', $label);

        // Label could be empty AFTER the preg_replace step
        if (empty(trim($label)) === true)
        {
            $label = $bankTransfer->merchant->getBillingLabel();

            // Still necessary to sanitize merchant name
            $label = preg_replace('/[^a-zA-Z0-9 ]+/', '', $label);
        }

        return substr($label, 0, 39);
    }

    /**
     * Sanitizes account numbers received.
     *
     * @param Entity $bankTransfer
     *
     * @return null|string
     */
    protected static function getPayerAccount(Entity $bankTransfer)
    {
        $account = $bankTransfer->getPayerAccount();

        $account = preg_replace('/[^a-zA-Z0-9]+/', '', $account);

        $account = BankCodes::modifyPayerAccountIfNeeded($account, $bankTransfer);

        return $account;
    }

    /**
     * We don't get valid IFSCs from Kotak for IMPS payments, only a
     * bank code. To create the payer bank account anyway, we derive
     * the bank from the code, and use a random IFSC. Later, IMPS refunds
     * should go through, as IFSC isn't validated by Kotak for payments.
     *
     * It's also possible to receive an invalid (or revoked) IFSC
     * for NEFT/RTGS payments, eg. UTIB0000001 for Axis. Since this
     * would cause refunds to fail, we replace the IFSC with a valid one.
     *
     * @param Entity $bankTransfer
     *
     * @return mixed|null
     */
    public static function getPayerIfsc(Entity $bankTransfer)
    {
        $ifsc = $bankTransfer->getPayerIfsc();

        if ((strlen($ifsc) !== BankAccount\Entity::IFSC_CODE_LENGTH) and
            ($bankTransfer->getMode() === Mode::IMPS))
        {
            $impsBankCode = substr($ifsc, 0, 3);

            $ifsc = BankCodes::getIfscForImpsBankCode($impsBankCode);
        }

        if (IFSC::validate($ifsc) === false)
        {
            $bankCode = substr($ifsc, 0, 4);

            $ifsc = BankCodes::getIfscForBankCode($bankCode);
        }

        return $ifsc;
    }
}
