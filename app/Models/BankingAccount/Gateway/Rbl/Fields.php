<?php

namespace RZP\Models\BankingAccount\Gateway\Rbl;

use RZP\Models\BankingAccount\Entity;
use RZP\Models\BankingAccount\Detail;

/**
 * Class Fields
 *
 * @package RZP\Models\BankingAccount\Gateway\Rbl
 */
class Fields
{
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

    // The RBL webhook fields contains Account No and Phone no in below format
    const PHONE_NUM                         = 'Phone no.';
    const ACCOUNT_NUMBER                    = 'Account No.';

    // We will convert these fields into below format. Since Laravel validator
    // converts fields with dot into array notation and the validations failed
    // for above formats.
    // Slack - https://razorpay.slack.com/archives/C3L5D6DC2/p1564559938317300
    const ACCOUNT_NO                        = 'Account No';
    const PHONE_NO                          = 'Phone no';

    const EMAIL_ID                          = 'Email Id';
    const RZP_ALERT_NOTIFICATION_REQUEST    = 'RZPAlertNotiReq';
    const RZP_ALERT_NOTIFICATION_RESPONSE   = 'RZPAlertNotiRes';

    // For RBL Co-Created Leads
    const LEAD_ID                           = 'LeadID';
    const CO_CREATED_CORP_ID                = 'Corp_ID';
    const CUSTOMER_CITY                     = 'Customer_City';
    const CUSTOMER_MOBILE_NUMBER            = 'Customer_Mobile_Number';
    const CUSTOMER_ADDRESS                  = 'Customer_Address';
    const CO_CREATED_CUSTOMER_NAME          = 'Customer_Name';
    const CUSTOMER_PINCODE                  = 'Customer_PinCode';
    const EMAIL_ADDRESS                     = 'EmailAddress';
    const NEO_BANKING_LEAD_REQUEST          = 'NeoBankingLeadReq';
    const NEO_BANKING_LEAD_RESPONSE         = 'NeoBankingLeadResp';

    // Credentials fields
    const CORP_ID                           = 'corp_id';
    const CLIENT_ID                         = 'client_id';
    const CLIENT_SECRET                     = 'client_secret';
    const USERNAME                          = 'auth_username';
    const PASSWORD                          = 'auth_password';
    const MOZART_IDENTIFIER                 = 'mozart_identifier';

    // Credentials for UPI
    const PAYER_VPA                         = 'payerVpa';
    const BCAGENT                           = 'bcagent';
    const BCAGENT_USERNAME                  = 'bcagent_username';
    const BCAGENT_PASSWORD                  = 'bcagent_password';
    const HMAC_KEY                          = 'hmacKey';
    const MRCH_ORG_ID                       = 'mrchOrgId';
    const AGGR_ORG_ID                       = 'aggrOrgId';

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

    // Fields which store the credentials for a Banking account

    const LDAP_ID                           = 'ldap_id';
    const MERCHANT_EMAIL                    = 'merchant_email';
    const MERCHANT_PASSWORD                 = 'merchant_password';

    // This map holds the account details fields which
    // are sensitive and have to be tokenised before storing

    public static $sensitiveAccountDetails = [
        self::CLIENT_SECRET,
        self::MERCHANT_PASSWORD,
        Entity::PASSWORD,
        self::BCAGENT_PASSWORD,
        self::HMAC_KEY,
    ];

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
      self::CORP_ID                 => Entity::REFERENCE1,
    ];

    public static $upiCredentialsFields = [
        self::PAYER_VPA,
        self::BCAGENT,
        self::BCAGENT_USERNAME,
        self::BCAGENT_PASSWORD,
        self::HMAC_KEY,
        self::MRCH_ORG_ID,
        self::AGGR_ORG_ID,
    ];
}
