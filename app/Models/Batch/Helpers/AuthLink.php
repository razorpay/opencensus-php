<?php

namespace RZP\Models\Batch\Helpers;

use RZP\Models\Batch;
use RZP\Models\Invoice;
use RZP\Models\Customer;
use RZP\Constants\Entity;
use RZP\Models\BankAccount;
use RZP\Models\Base\Utility;
use RZP\Models\SubscriptionRegistration;

class AuthLink
{
    public static $batchFieldsToBankMapping = [
        Batch\Header::AUTH_LINK_IFSC           => BankAccount\Entity::IFSC_CODE,
        Batch\Header::AUTH_LINK_ACCOUNT_NUMBER => BankAccount\Entity::ACCOUNT_NUMBER,
        Batch\Header::AUTH_LINK_ACCOUNT_TYPE   => BankAccount\Entity::ACCOUNT_TYPE,
    ];

    public static function getAuthLinkInput(array & $entry, array $params): array
    {
        $receipt = $entry[Batch\Header::AUTH_LINK_RECEIPT];

        $receipt = empty($receipt) === true ? null : (string) $receipt;

        $expireBy = $entry[Batch\Header::AUTH_LINK_EXPIRE_BY];

        if (is_numeric($expireBy) === true) {

            $expireBy = self::fromExcelToEpoch($expireBy);
        }

        if (empty($expireBy) === false)
        {
            $expireBy = Utility::parseAsEpoch($expireBy);
        }

        $amount = $entry[Batch\Header::AUTH_LINK_AMOUNT_IN_PAISE];

        if (empty($amount) === true)
        {
            $amount = 0;
        }

        $amount = (is_numeric($amount) === true) ? (int) number_format($amount, 0, '', '') : $amount;

        $smsNotify = $params[Invoice\Entity::SMS_NOTIFY] ?? '0';

        $emailNotify = $params[Invoice\Entity::EMAIL_NOTIFY] ?? '0';

        $input = [
            Invoice\Entity::SMS_NOTIFY   => $smsNotify,
            Invoice\Entity::EMAIL_NOTIFY => $emailNotify,
            Invoice\Entity::TYPE         => Invoice\Type::LINK,
            Invoice\Entity::RECEIPT      => $receipt,
            Invoice\Entity::AMOUNT       => $amount,
            Invoice\Entity::DESCRIPTION  => (string) $entry[Batch\Header::AUTH_LINK_DESCRIPTION],
            Invoice\Entity::EXPIRE_BY    => $expireBy,
            Invoice\Entity::CUSTOMER     => self::getCustomerInput($entry),
            Invoice\Entity::NOTES        => $entry[Invoice\Entity::NOTES] ?? [],
        ];

        $mandateInput = self::getMandateEntityInput($entry);

        $method = $entry[Batch\Header::AUTH_LINK_METHOD];

        if (($method === SubscriptionRegistration\Method::EMANDATE) or
            ($method === SubscriptionRegistration\Method::NACH))
        {
            if ($amount > 0)
            {
                $input[Invoice\Entity::AMOUNT] = 0;

                $mandateInput[SubscriptionRegistration\Entity::FIRST_PAYMENT_AMOUNT] = $amount;
            }
        }

        $input[Entity::SUBSCRIPTION_REGISTRATION] = $mandateInput;

        return $input;
    }

    public static function getCustomerInput(array & $entry): array
    {
        $customer = [
            Customer\Entity::NAME    => (string) $entry[Batch\Header::AUTH_LINK_CUSTOMER_NAME],
            Customer\Entity::CONTACT => (string) $entry[Batch\Header::AUTH_LINK_CUSTOMER_PHONE],
            Customer\Entity::EMAIL   => (string) $entry[Batch\Header::AUTH_LINK_CUSTOMER_EMAIL],
        ];

        return $customer;
    }

    public static function getBankEntityInput(array & $entry): array
    {
        $method = $entry[Batch\Header::AUTH_LINK_METHOD];

        $input = [];

        if (($method == SubscriptionRegistration\Method::EMANDATE) or
            ($method == SubscriptionRegistration\Method::NACH))
        {
            $input = self::buildBankAccountFromBatchInput($entry);
        }

        return $input;
    }

    public static function getMandateEntityInput(array & $entry): array
    {
        $method = $entry[Batch\Header::AUTH_LINK_METHOD];

        $maxAmount = empty($entry[Batch\Header::AUTH_LINK_MAX_AMOUNT]) === true
            ? null : (int) $entry[Batch\Header::AUTH_LINK_MAX_AMOUNT];

        $maxAmount = (is_numeric($maxAmount) === true) ?
            (int) number_format($maxAmount, 0, '', '') : $maxAmount;

        $authType = empty($entry[Batch\Header::AUTH_LINK_AUTH_TYPE]) === true
            ? null : (string) $entry[Batch\Header::AUTH_LINK_AUTH_TYPE];

        $input = [
            SubscriptionRegistration\Entity::METHOD     => $method,
            SubscriptionRegistration\Entity::MAX_AMOUNT => $maxAmount,
            SubscriptionRegistration\Entity::AUTH_TYPE  => $authType,
        ];

        $expiry = $entry[Batch\Header::AUTH_LINK_TOKEN_EXPIRE_BY];

        if (is_numeric($expiry) === true) {

            $expiry = self::fromExcelToEpoch($expiry);
        }


        if (empty($expiry) === false)
        {
            $expiry = Utility::parseAsEpoch($expiry);

            $input[SubscriptionRegistration\Entity::EXPIRE_AT] = $expiry;
        }

        $bankInput = self::getBankEntityInput($entry);

        // since bank name does not go into bank account and if only bank name is present,
        // we dont need to create a bank account
        $bankInput[BankAccount\Entity::BANK_NAME] = self::getBankName($entry);

        $input[Entity::BANK_ACCOUNT] = $bankInput;

        if ($method === SubscriptionRegistration\Method::NACH)
        {
            $input[SubscriptionRegistration\Entity::NACH] = self::getNachFormData($entry);
        }

        return $input;
    }

    public static function buildBankAccountFromBatchInput(array & $entry): array
    {
        if ((is_double($entry[Batch\Header::AUTH_LINK_ACCOUNT_NUMBER]) === true)
            or (is_float($entry[Batch\Header::AUTH_LINK_ACCOUNT_NUMBER]) === true))
        {
            $entry[Batch\Header::AUTH_LINK_ACCOUNT_NUMBER] = (int)$entry[Batch\Header::AUTH_LINK_ACCOUNT_NUMBER];
        }

        $output = [];

        $keys = array_keys(self::$batchFieldsToBankMapping);

        foreach ($keys as $row)
        {
            if (empty($entry[$row] == false))
            {
                $output[self::$batchFieldsToBankMapping[$row]] = $entry[$row];
            }
        }

        if (empty($output) === false)
        {
            $output[BankAccount\Entity::BENEFICIARY_NAME] =
                $entry[Batch\Header::AUTH_LINK_NAME_ON_ACCOUNT];

            $output[BankAccount\Entity::BENEFICIARY_EMAIL] =
                $entry[Batch\Header::AUTH_LINK_CUSTOMER_EMAIL];

            $output[BankAccount\Entity::BENEFICIARY_MOBILE] =
                $entry[Batch\Header::AUTH_LINK_CUSTOMER_PHONE];

        }

        return $output;
    }

    public static function getBankName(array & $entry)
    {
        $bank = empty($entry[Batch\Header::AUTH_LINK_BANK]) === true
            ? null : (string) $entry[Batch\Header::AUTH_LINK_BANK];

        return $bank;
    }

    /**
     * Convert excel date format to epoch
     * remove 70 years of days: 25569 & multiply for seconds in a day: 86400
     * @param  int $value
     * @return int
     */
    public static function fromExcelToEpoch($value) {
        return ($value - 25569) * 86400;
    }

    protected static function getNachFormData(array & $entry): array
    {
        $nachFormData = [];

        if (empty($entry[Batch\Header::AUTH_LINK_NACH_REFERENCE1]) === false)
        {
            $nachFormData[SubscriptionRegistration\Entity::FORM_REFERENCE1] = strval($entry[Batch\Header::AUTH_LINK_NACH_REFERENCE1]);
        }

        if (empty($entry[Batch\Header::AUTH_LINK_NACH_REFERENCE2]) === false)
        {
            $nachFormData[SubscriptionRegistration\Entity::FORM_REFERENCE2] = strval($entry[Batch\Header::AUTH_LINK_NACH_REFERENCE2]);
        }

        if (isset($entry[Batch\Header::AUTH_LINK_NACH_CREATE_FORM]) === true)
        {
            $nachFormData[SubscriptionRegistration\Entity::CREATE_FORM] = boolval($entry[Batch\Header::AUTH_LINK_NACH_CREATE_FORM]);
        }

        return $nachFormData;
    }
}
