<?php

namespace RZP\Gateway\Mobikwik;

class ResponseCode
{
    public static $codes = array(
        '0'  => 'Transaction completed successfully',
        '10' => 'Merchant secret key does not exist',
        '20' => 'User Blocked',
        '21' => 'Merchant Blocked',
        '22' => 'Merchant does not Exist',
        '23' => 'Merchant not registered on MobiKwik',
        '24' => 'Orderid is Blank or Null',
        '30' => 'Wallet TopUp Failed',
        '31' => 'Wallet Debit Failed',
        '32' => 'Wallet Credit Failed',
        '33' => 'User does not have sufficient balance in his wallet',
        '40' => 'User canceled transaction at Login page',
        '41' => 'User canceled transaction at Wallet Top Up page',
        '42' => 'User canceled transaction at Wallet Debit page',
        '50' => 'Order Id already processed with this merchant.',
        '51' => 'Length of parameter orderid must be between 8 to 30 characters',
        '52' => 'Parameter orderid must be alphanumeric only',
        '53' => 'Parameter email is invalid',
        '54' => 'Parameter amount must be integer only',
        '55' => 'Parameter cell is invalid. It must be numeric, have 10 digits and start with 7,8,9',
        '56' => 'Parameter merchantname is invalid. It must be alphanumeric and its length must be between 1 to 30 characters',
        '57' => 'Parameter redirecturl is invalid',
        '60' => 'User Authentication failed',
        '70' => 'Monthly Wallet Top up limit crossed',
        '71' => 'Monthly transaction limit for this user crossed',
        '72' => 'Maximum amount per transaction limit for this merchant crossed',
        '73' => 'Merchant is not allowed to perform transactions on himself',
        '74' => 'KYC Transactions is not allowed',
        '80' => 'Checksum Mismatch',
        '99' => 'Unexpected Error',
        '110' => 'Invalid action parameter',
        '120' => 'User does not exists',
        '148' => 'Exceeded maximum number of OTP request attempts',
        '150' => 'Invalid Message Code',
        '151' => 'Invalid Request Parameters',
        '152' => 'User with the input email is already registered',
        '153' => 'User with the input cell is already registered',
        '155' => 'OTP mismatch can be due to mismatched order id or amount or OTP code mismatch',
        '156' => 'Account seems to be registered with invalid mobile',
        '157' => 'Either Email or Mobile is required for OTP generation',
        '158' => 'Provide either Email or cell to uniquely identify you',
        '159' => 'No Wallet Account is associated with specified cell',
        '160' => 'Our record suggests that no mobile is registered with your email',
        '164' => 'Either Invalid OTP (Expiry or OTP mismatch) or OTP mismatched due to mismatch in order id or transaction amount',
        '170' => 'Wallet is not semi closed',
    );

    public static function getResponseMessage($code)
    {
        $codes = self::$codes;

        return $codes[(int)$code];
    }
}
