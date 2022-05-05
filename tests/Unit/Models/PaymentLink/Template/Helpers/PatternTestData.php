<?php
use \RZP\Models\PaymentLink\Template\Pattern;

return [
    "testIsValid" => [
        "Pattern Pan"           => [Pattern::PAN, true],
        "Pattern Url"           => [Pattern::URL, true],
        "Pattern DATE"          => [Pattern::DATE, true],
        "Pattern EMAIL"         => [Pattern::EMAIL, true],
        "Pattern PHONE"         => [Pattern::PHONE, true],
        "Pattern AMOUNT"        => [Pattern::AMOUNT, true],
        "Pattern NUMBER"        => [Pattern::NUMBER, true],
        "Pattern ALPHABETS"     => [Pattern::ALPHABETS, true],
        "Pattern ALPHANUMERIC"  => [Pattern::ALPHANUMERIC, true],
        "Pattern UNKNOWN"       => ["unknown", false],
    ],

    "testValidate" => [
        "Pattern Pan"           => [Pattern::PAN, true],
        "Pattern Url"           => [Pattern::URL, true],
        "Pattern DATE"          => [Pattern::DATE, true],
        "Pattern EMAIL"         => [Pattern::EMAIL, true],
        "Pattern PHONE"         => [Pattern::PHONE, true],
        "Pattern AMOUNT"        => [Pattern::AMOUNT, true],
        "Pattern NUMBER"        => [Pattern::NUMBER, true],
        "Pattern ALPHABETS"     => [Pattern::ALPHABETS, true],
        "Pattern ALPHANUMERIC"  => [Pattern::ALPHANUMERIC, true],
        "Pattern UNKNOWN"       => ["unknown", false],
    ],
];
