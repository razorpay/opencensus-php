<?php

namespace RZP\Models\BankingAccount\Gateway\Rbl;

use RZP\Models\BankingAccount\Entity;

class Fields
{
    const ACCOUNT_NUMBER                    = 'Account No';
    const CUSTOMER_NAME                     = 'Customer Name';
    const CUSTOMER_ID                       = 'Customer ID';
    const ACTIVATION_DATE                   = 'Account Open Date';
    const IFSC                              = 'IFSC';
    const RZP_REFERENCE_NUMBER              = 'RZP_Ref No';
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
    const PHONE_NUM                         = 'Phone no';
    const EMAIL_ID                          = 'Email Id';
    const RZP_ALERT_NOTIFICATION_REQUEST    = 'RZPAlertNotiReq';
    const RZP_ALERT_NOTIFICATION_RESPONSE   = 'RZPAlertNotiRes';

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
    ];
}
