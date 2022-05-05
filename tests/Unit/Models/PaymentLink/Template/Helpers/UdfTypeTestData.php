<?php

use \RZP\Models\PaymentLink\Template\UdfType;

return [
    "testIsValid" => [
        "UdfType NUMBER"    => [UdfType::NUMBER, true],
        "UdfType STRING"    => [UdfType::STRING, true],
        "OptionCmp UNKNOWN" => ["unknown", false],
    ],
    "testValidate" => [
        "UdfType NUMBER"    => [UdfType::NUMBER, true],
        "UdfType STRING"    => [UdfType::STRING, true],
        "OptionCmp UNKNOWN" => ["unknown", false],
    ],
];
