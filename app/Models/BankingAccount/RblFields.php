<?php

namespace RZP\Models\BankingAccount;

class RblFields
{
    const FORACID                           = 'FORACID';
    const ACCT_NAME                         = 'ACCT_NAME';
    const CIF_ID                            = 'CIF_ID';
    const ACTIVATION_DATE                   = 'ACTIVATION_DATE';
    const IFSC                              = 'IFSC';
    const REF_NUM_1                         = 'REF_NUM_1';
    const ADDR_1                            = 'ADDR_1';
    const ADDR_2                            = 'ADDR_2';
    const ADDR_3                            = 'ADDR_3';
    const CITY                              = 'CITY';
    const STATE                             = 'STATE';
    const COUNTRY                           = 'COUNTRY';
    const PINCODE                           = 'PINCODE';
    const BODY                              = 'Body';
    const HEADER                            = 'Header';
    const TRAN_ID                           = 'TranID';
    const STATUS                            = 'Status';
    const PHONE_NUM                         = 'PHONE_NUM';
    const EMAIL_ID                          = 'EMAIL_ID';

    const RZP_ALERT_NOTIFICATION_REQUEST    = 'RZPAlertNotiReq';
    const RZP_ALERT_NOTIFICATION_RESPONSE   = 'RZPAlertNotiRes';

    public static $rblFieldsToEntityMap = [
      self::FORACID                 => Entity::ACCOUNT_NUMBER,
      self::IFSC                    => Entity::ACCOUNT_IFSC,
      self::ACCT_NAME               => Entity::BENEFICIARY_NAME,
      self::CIF_ID                  => Entity::BANK_INTERNAL_REFERENCE_NUMBER,
      self::ADDR_1                  => Entity::BENEFICIARY_ADDRESS1,
      self::ADDR_2                  => Entity::BENEFICIARY_ADDRESS2,
      self::ADDR_3                  => Entity::BENEFICIARY_ADDRESS3,
      self::CITY                    => Entity::BENEFICIARY_CITY,
      self::STATE                   => Entity::BENEFICIARY_STATE,
      self::COUNTRY                 => Entity::BENEFICIARY_COUNTRY,
      self::PINCODE                 => Entity::PINCODE,
      self::ACTIVATION_DATE         => Entity::ACCOUNT_ACTIVATION_DATE,
      self::REF_NUM_1               => Entity::BANK_REFERENCE_NUMBER,
      self::EMAIL_ID                => Entity::BENEFICIARY_EMAIL,
      self::PHONE_NUM               => Entity::BENEFICIARY_MOBILE,
    ];
}
