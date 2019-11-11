<?php

namespace RZP\Gateway\FirstData\Mock;

use RZP\Base;
use RZP\Gateway\FirstData\ConnectRequestFields;
use RZP\Gateway\FirstData\PaymentMode;
use RZP\Gateway\FirstData\PaymentMethod;
use RZP\Gateway\FirstData\TxnType;
use RZP\Gateway\FirstData\Codes;
use RZP\Constants\HashAlgo;
use RZP\Exception;
use RZP\Models\Currency\Currency;

class Validator extends Base\Validator
{
    protected static $enrollRules = [
        'Transaction'                                            => 'required',
        'Transaction.CreditCardTxType'                           => 'required',
        'Transaction.CreditCardTxType.StoreId'                   => 'required',
        'Transaction.CreditCardTxType.Type'                      => 'required|in:sale,preauth|string',
        'Transaction.CreditCardData'                             => 'required',
        'Transaction.CreditCardData.CardNumber'                  => 'required|numeric|digits_between:12,19',
        'Transaction.CreditCardData.ExpMonth'                    => 'required|size:2',
        'Transaction.CreditCardData.ExpYear'                     => 'required|size:2',
        'Transaction.CreditCardData.CardCodeValue'               => 'required|numeric',
        'Transaction.CreditCard3DSecure'                         => 'sometimes',
        'Transaction.CreditCard3DSecure.AuthenticateTransaction' => 'sometimes|boolean',
        'Transaction.Payment'                                    => 'required',
        'Transaction.Payment.Currency'                           => 'required|size:3',
        'Transaction.Payment.ChargeTotal'                        => 'required',
        'Transaction.Payment.HostedDataID'                       => 'sometimes',
        'Transaction.TransactionDetails'                         => 'required',
        'Transaction.TransactionDetails.OrderId'                 => 'required'
     ];

    protected static $authenticateRules = [
        'PaReq'   => 'required|string',
        'MD'      => 'required|string',
        'TermUrl' => 'required|url'
    ];

    protected static $authRules = [
        ConnectRequestFields::CARD_FUNCTION             => 'sometimes|in:credit,debit|string',
        ConnectRequestFields::CARD_NUMBER               => 'required|numeric|digits_between:12,19',
        ConnectRequestFields::CHARGE_TOTAL              => 'required|numeric',
        ConnectRequestFields::COMMENTS                  => 'sometimes|',
        ConnectRequestFields::CURRENCY                  => 'required|',
        ConnectRequestFields::CVV                       => 'required|numeric|digits_between:2,4',
        ConnectRequestFields::DYNAMIC_MERCHANT_NAME     => 'sometimes|string',
        ConnectRequestFields::EXP_MONTH                 => 'required|size:2',
        ConnectRequestFields::EXP_YEAR                  => 'required|size:4',
        ConnectRequestFields::HASH                      => 'required|size:40|string',
        ConnectRequestFields::HASH_ALGORITHM            => 'required|',
        ConnectRequestFields::INVOICE_NUMBER            => 'sometimes|',
        ConnectRequestFields::LANGUAGE                  => 'sometimes|',
        ConnectRequestFields::MODE                      => 'sometimes|',
        ConnectRequestFields::NAME                      => 'sometimes|',
        ConnectRequestFields::NUMBER_OF_INSTALLMENTS    => 'sometimes|',
        ConnectRequestFields::ORDER_ID                  => 'sometimes|',
        ConnectRequestFields::MERCHANT_TXN_ID           => 'sometimes|',
        ConnectRequestFields::PAYMENT_METHOD            => 'required|',
        ConnectRequestFields::RESPONSE_FAIL_URL         => 'required|url',
        ConnectRequestFields::RESPONSE_SUCCESS_URL      => 'required|url',
        ConnectRequestFields::STORE_NAME                => 'required|size:10|string',
        ConnectRequestFields::TIME_ZONE                 => 'required|string',
        ConnectRequestFields::TXN_DATE_TIME             => 'required|string',
        ConnectRequestFields::TXN_TYPE                  => 'required|in:preauth,sale',
        ConnectRequestFields::TOKEN                     => 'sometimes|string',
    ];

    protected static $preAuthRules = [
        'Transaction'                                                   => 'required',
        'Transaction.CreditCardTxType'                                  => 'required',
        'Transaction.CreditCardTxType.StoreId'                          => 'required',
        'Transaction.CreditCardTxType.Type'                             => 'required|in:preauth,sale',
        'Transaction.CreditCardData'                                    => 'required',
        'Transaction.CreditCardData.CardCodeValue'                      => 'required|numeric',
        'Transaction.CreditCardData.CardNumber'                         => 'required|numeric|luhn|digits_between:12,19',
        'Transaction.CreditCardData.ExpMonth'                           => 'required|integer|digits_between:1,2|max:12|min:1',
        'Transaction.CreditCardData.ExpYear'                            => 'required|integer|digits:2',
        'Transaction.CreditCard3DSecure'                                => 'required|array',
        'Transaction.CreditCard3DSecure.VerificationResponse'           => 'required|string|in:Y',
        'Transaction.CreditCard3DSecure.PayerAuthenticationResponse'    => 'required|string|in:Y',
        'Transaction.CreditCard3DSecure.AuthenticationValue'            => 'required|string',
        'Transaction.CreditCard3DSecure.XID'                            => 'required|string',
        'Transaction.TransactionDetails'                                => 'required|array',
        'Transaction.Payment'                                           => 'required|array',
        'Transaction.Payment.ChargeTotal'                               => 'required|numeric',
        'Transaction.Payment.Currency'                                  => 'required|numeric|digits:3',
        'Transaction.TransactionDetails.OrderId'                        => 'required|string',
    ];

    protected static $authValidators = [
        ConnectRequestFields::MODE,
        ConnectRequestFields::PAYMENT_METHOD,
        ConnectRequestFields::HASH_ALGORITHM,
        ConnectRequestFields::CURRENCY,
        ConnectRequestFields::LANGUAGE,
    ];

    protected function validateMode($input)
    {
        if ((isset($input['mode']) === true) and
            (in_array($input['mode'], PaymentMode::MODE_LIST, true) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid mode');
        }
    }

    protected function validatePaymentMethod($input)
    {
        if ((isset($input['paymentMethod']) === false) or
            (in_array($input['paymentMethod'], array_values(PaymentMethod::METHOD_MAP), true) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid paymentMethod');
        }
    }

    protected function validateLanguage($input)
    {
        if ((isset($input['language']) === true) and
            ($input['language'] !== Codes::ENGLISH_UK_LANG_CODE_CONNECT))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Unsupported language');
        }
    }

    protected function validateCurrency($input)
    {
        if ((isset($input['currency']) === false) or
            ($input['currency'] !== Currency::ISO_NUMERIC_CODES['INR']))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Unsupported currency');
        }
    }

    protected function validateHashAlgorithm($input)
    {
        if ((isset($input['hash_algorithm']) === false) or
            ($input['hash_algorithm'] !== strtoupper(HashAlgo::SHA1)))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Unsupported hash_algorithm');
        }
    }

    protected static $authorizeRules = [
        'Transaction'                                                                   => 'required',
        'Transaction.CreditCardTxType'                                                  => 'required',
        'Transaction.CreditCardTxType.StoreId'                                          => 'required',
        'Transaction.CreditCardTxType.Type'                                             => 'required|in:preauth,sale',
        'Transaction.CreditCardData'                                                    => 'required',
        'Transaction.CreditCardData.CardCodeValue'                                      => 'required|numeric',
        'Transaction.CreditCard3DSecure'                                                => 'required',
        'Transaction.CreditCard3DSecure.Secure3DRequest'                                => 'required',
        'Transaction.CreditCard3DSecure.Secure3DRequest.Secure3DAuthenticationRequest'
                                                                                        => 'required|array',
        'Transaction.CreditCard3DSecure.Secure3DRequest.Secure3DAuthenticationRequest.AcsResponse'
                                                                                        => 'required',
        'Transaction.CreditCard3DSecure.Secure3DRequest.Secure3DAuthenticationRequest.AcsResponse.MD'
                                                                                        => 'required|string',
        'Transaction.CreditCard3DSecure.Secure3DRequest.Secure3DAuthenticationRequest.AcsResponse.PaRes'
                                                                                        => 'required|string',
        'Transaction.TransactionDetails'                                                => 'required',
        'Transaction.TransactionDetails.IpgTransactionId'                               => 'required|string',
        'Transaction.TransactionDetails.TransactionOrigin'                              => 'required|in:ECI',
    ];
}
