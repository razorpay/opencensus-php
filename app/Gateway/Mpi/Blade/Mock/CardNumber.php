<?php

namespace RZP\Gateway\Mpi\Blade\Mock;

class CardNumber
{
    const ENROLLED_13_DIGIT_PAN                     = '5567630000002004';
    const VALID_ENROLL_NUMBER                       = '5567630000002004';
    const VALID_NOT_ENROLL_NUMBER                   = '5257834104683413';
    const VALID_VISA_NOT_ENROLLED                   = '4024001104457538';
    const INVALID_MEESAGE                           = '4024007197911620';
    const BLANK_MEESAGE                             = '4486705296247132';
    const INVALID_VERSION                           = '5110731267079214';
    const INTERNATIONAL_VISA                        = '4264511038488895';
    const INTERNATIONAL_MASTER                      = '5101281038487891';
    const INVALID_ECI                               = '5200000000000031';
    const INTERNATIONAL_MAESTRO                     = '5893163050216758';
    const INVALID_PARES                             = '4539074965827394';
    const UNKNOWN_ENROLLED                          = '4539074965827311';
    const INTERNATIONAL_VISA_NE                     = '5149066434045615';
    const INTERNATIONAL_VISA_ENROLLED               = '5149063701342189';

    public static function getAccId($cardNumber) // nosemgrep : razorpay:card_pii_data_parameters
    {
        return base64_encode($cardNumber);
    }

    public static function getCardNumberFromAccId($accId)
    {
        return base64_decode($accId);
    }
}
