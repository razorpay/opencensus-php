<?php

namespace RZP\Models\BankingAccount\Gateway\Rbl;

use RZP\Models\BankingAccount\Entity;

/**
 * Class Fields
 *
 * @package RZP\Models\BankingAccount\Gateway\Rbl
 */
class Fields
{
    const ACCOUNT_NUMBER                    = 'Account No.';
    const ACCOUNT_NO                        = 'Account No';
    const CUSTOMER_NAME                     = 'Customer Name';
    const CUSTOMER_ID                       = 'Customer ID';
    const ACTIVATION_DATE                   = 'Account Open Date';
    const IFSC                              = 'IFSC';
    const RZP_REFERENCE_NUMBER              = 'RZP_Ref No';
    const ADDR_1                            = 'Address1';
    const ADDR_2                            = 'Address2';
    const ADDR_3                            = 'Address3';
    const CITY                              = 'CITY';
    const STATE                             = 'STATE';
    const COUNTRY                           = 'COUNTRY';
    const PINCODE                           = 'PINCODE';
    const BODY                              = 'Body';
    const HEADER                            = 'Header';
    const TRAN_ID                           = 'TranID';
    const STATUS                            = 'Status';
    const PHONE_NUM                         = 'Phone no.';
    const PHONE_NO                          = 'Phone no';
    const EMAIL_ID                          = 'Email Id';
    const RZP_ALERT_NOTIFICATION_REQUEST    = 'RZPAlertNotiReq';
    const RZP_ALERT_NOTIFICATION_RESPONSE   = 'RZPAlertNotiRes';

    // Credentials fields
    const SUBCORP_ID                        = 'subcorp_id';
    const SUBCORP_USER_ID                   = 'subcorp_user_id';
    const SUBCORP_USER_NAME                 = 'subcorp_user_name';
    const SUBCORP_USER_PASSWORD             = 'subcorp_user_password';
    const CLIENT_ID                         = 'client_id';
    const CLIENT_SECRET                     = 'client_secret';
    const USERNAME                          = 'auth_username';
    const PASSWORD                          = 'auth_password';
    const MOZART_IDENTIFIER                 = 'mozart_identifier';

    // Fields for Fetch Balance API
    const SOURCE_ACCOUNT                    = 'source_account';
    const SOURCE_ACCOUNT_NUMBER             = 'account_number';
    const ID                                = 'id';
    const CREDENTIALS                       = 'credentials';
    const DATA                              = 'data';
    const GET_ACCOUNT_BALANCE               = 'PayGenRes';
    const AMOUNT_VALUE                      = 'amountValue';
    const BAL_AMOUNT                        = 'BalAmt';
    const AUTH_USERNAME                     = 'auth_username';
    const AUTH_PASSWORD                     = 'auth_password';

    public static $rblFieldsToEntityMap = [
      self::ACCOUNT_NUMBER          => Entity::ACCOUNT_NUMBER,
      self::IFSC                    => Entity::ACCOUNT_IFSC,
      self::CUSTOMER_NAME           => Entity::BENEFICIARY_NAME,
      self::CUSTOMER_ID             => Entity::BANK_INTERNAL_REFERENCE_NUMBER,
      self::ADDR_1                  => Entity::BENEFICIARY_ADDRESS1,
      self::ADDR_2                  => Entity::BENEFICIARY_ADDRESS2,
      self::ADDR_3                  => Entity::BENEFICIARY_ADDRESS3,
      self::CITY                    => Entity::BENEFICIARY_CITY,
      self::STATE                   => Entity::BENEFICIARY_STATE,
      self::COUNTRY                 => Entity::BENEFICIARY_COUNTRY,
      self::PINCODE                 => Entity::BENEFICIARY_PIN,
      self::ACTIVATION_DATE         => Entity::ACCOUNT_ACTIVATION_DATE,
      self::RZP_REFERENCE_NUMBER    => Entity::BANK_REFERENCE_NUMBER,
      self::EMAIL_ID                => Entity::BENEFICIARY_EMAIL,
      self::PHONE_NUM               => Entity::BENEFICIARY_MOBILE,
      self::SUBCORP_ID              => Entity::REFERENCE1,
      self::SUBCORP_USER_NAME       => Entity::USERNAME,
      self::SUBCORP_USER_PASSWORD   => Entity::PASSWORD
    ];
}
