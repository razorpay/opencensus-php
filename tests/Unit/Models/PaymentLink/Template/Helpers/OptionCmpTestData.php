<?php

use \RZP\Models\PaymentLink\Template\OptionCmp;

return [
    "testIsValid" => [
        "OptionCmp DATE"        => [OptionCmp::DATE, true],
        "OptionCmp SELECT"      => [OptionCmp::SELECT, true],
        "OptionCmp TEXTAREA"    => [OptionCmp::TEXTAREA, true],
        "OptionCmp UNKNOWN"     => ["unknown", false],
    ],
    "testValidate" => [
        "OptionCmp DATE"        => [OptionCmp::DATE, true],
        "OptionCmp SELECT"      => [OptionCmp::SELECT, true],
        "OptionCmp TEXTAREA"    => [OptionCmp::TEXTAREA, true],
        "OptionCmp UNKNOWN"     => ["unknown", false],
    ],
];
