<?php

namespace RZP\Gateway\Kotak;

use RZP\Models\Payment\TwoFaStatus;

class ResponseCode
{
    const SUC   = '00';
    const VER   = 'VER';
    const HNM   = 'HNM';
    const STO   = 'STO';
    const IER   = 'IER';
    const TO    = 'TO';
    const CAN   = 'CAN';

    const PAYMENT_SUCCESS_STATUS = ['00', '0'];

    protected static $code = array(
        self::VER   => 'Validation Error Occurs if field data is incorrect',
        self::HNM   => 'Hash Not Match Occurs if the data is tampered',
        self::STO   => 'Session Timeout If user session is timed out',
        self::IER   => 'Internal Error System Error',
        self::TO    => 'Timeout Time out while connecting to RuPay PaySecure',
        self::CAN   => 'Cancel User pressed Cancel Button',
        '00'        => 'Success, Call was completed successfully, Call was successful move to next step in process',
        '0'         => 'Success, Call was completed successfully, Call was successful move to next step in process',
        '1'         => 'Missing parameter',
        '2'         => 'Invalid command',
        '3'         => 'Partner id error',
        '4'         => 'Command not supported',
        '5'         => 'Commaand not authorized',
        '12'        => 'Failed to find transaction',
        '13'        => 'Amount error',
        '14'        => 'Card number error, Issuer found PAN to be invalid',
        '41'        => 'Declined (lost card), Issuer reported card as lost, Decline transaction',
        '42'        => 'Declined (no account), Issuer rejected transaction, Make sure card number is correct',
        '43'        => 'Declined (stolen), Issuer reported card as stolen, Decline transaction',
        '51'        => 'Non sufficient funds, Issuer declined transaction: NSF, Decline transaction',
        '54'        => 'Expired card, Issuer declined transaction: card expired, Decline transaction',
        '55'        => 'Wrong pin, Issuer declined transaction: PIN number does not match the PIN on file, Ask for PIN second time',
        '56'        => 'Declined (no card), No card record, Make sure card number is correct',
        '57'        => 'Delcined (not cardholder),  Trnasaction not permitted to cardholder, Decline trasaction',
        '59'        => 'Declined (fraud), , Decline transaction',
        '60'        => 'Declined (contact acquirer), , Decline transaction',
        '61'        => 'Declined (Exceeds with), Exceeds withdrawal amount limit, Not enough available funds to cover the transaction, normally exceeds the daily withdrawal',
        '62'        => 'Declined (restriced card), Restricted card, Card is not eligible for this transaction type. Normally a deposit only account without withdrawal rights',
        '65'        => 'Declined (exceeds frequency), Exceeds withdrawal frequency limit, They have used their card too many times in a fixed time period',
        '75'        => 'Pin exceeded, Allowable number of PIN tries exceeded, The entered their PIN incorrectly too many times. Most likely the Issuer will block the card and the cardholder needs to call the issuer to get it unblocked',
        '92'        => 'No routing available, A technical issue at PaySecure or Issuer occurred, No such issuer or routing available',
        '93'        => 'Violation, Transaction cannot be completed, Refund requested but transaction is not in authorized state',
        '96'        => 'System error, A technical issue at PaySecure or Issuer occurred, PaySecure monitors for these and researches all occurrences. Please report to representative if occurs more than once',
        '110'       => 'No Acct, Issuer does not have this card number on record, Ask consumer to reenter their card number',
        '120'       => 'Acct closed, Account has been closed no longer valid, Ask consumer for another card number',
        '130'       => 'Fraud, Account closed due to fraud, Perform additonal review on transaction prior to accepting',
        '200'       => 'Transactiond eclined, Issuer declined transaction, Decline transaction',
        '303'       => 'DCE Invalid data, Dynamic currency exchange error, PaySecure does not support DCE so this error should never be seen, Decline transaction',
        '399'       => 'System unavailable, , System is not available due to maintenance. Contact PaySecure technical support and process as signature Debit, if possible. With two active systems fully operational, should never happen',
        '400'       => 'General, Unhandled exception while processing merchant web service request, Internal PaySecure problem. Shall never be observed by Merchant/Acquirer',
        '401'       => 'Command is NULL or EMPTY, SOAP parameter strCommand is empty, Failed validating SOAP request parameters, Merchant integration problem',
        '402'       => 'XML is null or empty, SOAP parameter strXML is empty, Failed validating SOAP request parameters Merchant integration problem',
        '403'       => 'Unknown command, SOAP parameter strCommand shall be "authorize", "checkbin", "initiate", "refund", "transactionstatus", "transactionupdate", Failed validating SOAP request parameters. Merchant integration problem',
        '405'       => 'Bad credentials "No SoapHeaderCredentials" or "No UserCredentials", SOAP request header does not contain correct user credentials, Merchant itnegration problem',
        '406'       => 'Not authenticated, Failed to authenticate, webservice user credentials, Merchant integration problem',
        '407'       => 'Not authorized, ErrMsg details the problem: "IP Address ... is not authorized for User ...", Merchant is not authorized to run requested command or merchant IP address is no tknown, Merchant integration problem',
        '408'       => 'XML data error, SOAP parameter strXML contains XML string of unexpected form (i.e. not encoded one), or required parameters missing, Merchant integration problem',
        '409'       => 'SHopper service error, During processing "initiate" request merchant channel initiated transaction was not found by shopper channel',
        '410'       => 'Invalid bin or error with bin check, Can happen only on "initaite" request. PAN is not numerc, or 6-9 first characters contains unknown bin, Most likely merchant integration problem. For example, merchant did not issue "bincheck" request prior "initiate" or did not handle probperly negative "bincheck" response.',
        '411'       => 'Ineligible PAN, PAN\'s bin is "special" one: Issuer does not accept transaction from particular merchant, The same as 410',
        '412'       => 'IAS Error, IAS time out/ if IAS sends bad data/required data, Issuer web server issue',
        '5000'      => 'No funds remian, Requested refund that has no money available for refund, Reject refund request trasaction was already fully refunded',
        '5001'      => 'Invalid refund reason code, "refund" request contains invalid reason_code value, Currently the only supported value is 0 (default value) or 1',
        '5002'      => 'Amount exceeds available funds, "refund" requeted for amount that exceeds availalbe transaction remianing balance, Correct reufnd amount or issue "calculated" refund request (no amount or amount is 0). In this case PaySecure refunds full transaction balance',
        '5003'      => 'Tran bus day exceeded, Authorize the transaction PaySecure, Internal to PaySecure only',
        'ACCU000'   => 'PIN was successfully received (PIN is verified on authorize web service call), End the session for the user from the merchant website as a security measure',
        'ACCU400'   => "User has been inactive for X minutes, Process the transaction as a 'credit' through already etablished means",
        'ACCU600'   => 'Invalid data was posted to the PaySecure PIN pad, Error occurred on issuer side, select another payment type',
        'ACCU800'   => 'Generic PaySecure error, None',
        'ACCU999'   => 'PIN Pad was successfully opened',
    );

    public static function getTwoFaStatus($code)
    {
        switch ($code) {
            case '00':
            case '0':
                return TwoFaStatus::PASSED;
            case '55':
            case '75':
                return TwoFaStatus::FAILED;
            default:
                return TwoFaStatus::UNKNOWN;
        }
    }
}