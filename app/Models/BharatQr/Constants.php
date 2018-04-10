<?php

namespace RZP\Models\BharatQr;

use RZP\Models\Card;
use RZP\Models\Currency\Currency;
use RZP\Models\Payment;

class Constants
{
    const VERSION = '01';
    // TODO: Accept this from input
    // issue: https://github.com/razorpay/api/issues/7054
    const STATIC_POI        = '11';
    const DYNAMIC_POI       = '12';
    const MERCHANT_CATEGORY = '5399';
    const CURRENCY_CODE     = '356';
    const COUNTRY_CODE      = 'IN';
    // TODO: All of these need to be dynamically set
    // based on which merchant is making the request
    // issue: https://github.com/razorpay/api/issues/7237
    const MERCHANT_NAME          = 'PAYMENTS';
    const MERCHANT_CITY          = 'BANGALORE';
    const MERCHANT_PINCODE       = '560030';
    const RUPAY_RID              = 'A000000524';
    const MERCHANT_VPA           = 'razorpaybqr@icici';
    const MUTEX_TIMEOUT          = 60;
    const CARD_CVV               = '123';
    const CARD_NAME              = 'Random';
    const CARD_EXPIRY_MONTH      = '11';
    const CARD_EXPIRY_YEAR       = '2037';
    const UPI_PREFIX             = 'RZP';
    const SHARED_VIRTUAL_ACCOUNT = 'sharedvirtuala';
    const SHARED_QR_CODE         = 'sharedqrcode12';
    const RAZORPAY_TERMINAL_ID   = 'razorpay_terminal_id';
    const DUMMY_CARD_NUMBER      = '4231560000511234';
    const DUMMY_VPA              = 'random@icici';
    const DUMMY_EMAIL            = 'random@gmail.com';
    const DUMMY_CONTACT          = '9876543210';

    public static function getDummyCardPaymentArray($receiver)
    {
        $paymentArray =  [
            Payment\Entity::CURRENCY    => Currency::INR,
            Payment\Entity::METHOD      => Payment\Method::CARD,
            Payment\Entity::AMOUNT      => 100,
            Payment\Entity::DESCRIPTION => 'Bharat Qr Payment',
            Payment\Entity::CONTACT     => self::DUMMY_CONTACT,
            Payment\Entity::EMAIL       => self::DUMMY_EMAIL,
            Payment\Entity::RECEIVER    => $receiver,
        ];

        $card = [
            Card\Entity::NUMBER       => self::DUMMY_CARD_NUMBER,
            Card\Entity::CVV          => self::CARD_CVV,
            Card\Entity::NAME         => self::CARD_NAME,
            Card\Entity::EXPIRY_MONTH => self::CARD_EXPIRY_MONTH,
            Card\Entity::EXPIRY_YEAR  => self::CARD_EXPIRY_YEAR,
        ];

        $paymentArray[Payment\Entity::CARD] = $card;

        return $paymentArray;
    }

    public static function getDummyVpaPaymentArray($receiver)
    {
        return [
            Payment\Entity::CURRENCY    => Currency::INR,
            Payment\Entity::METHOD      => Payment\Method::UPI,
            Payment\Entity::AMOUNT      => 100,
            Payment\Entity::DESCRIPTION => 'Bharat Qr Payment',
            Payment\Entity::CONTACT     => self::DUMMY_CONTACT,
            Payment\Entity::EMAIL       => self::DUMMY_EMAIL,
            Payment\Entity::RECEIVER    => $receiver,
            Payment\Entity::VPA         => self::DUMMY_VPA,
        ];
    }


}
