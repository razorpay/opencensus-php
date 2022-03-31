<?php

return [
    "testCustomAmountEncryptionDecryption" => [
        "Negative Value Should Fail"                    => ["-1", false],
        "Amount with value 0 should Pass"               => ["0", true],
        "Amount with value 10 should Pass"              => ["10", true],
        "Amount with value 110 should Pass"             => ["110", true],
        "Amount with value 134535 should Pass"          => ["134535", true],
        "Amount with value 345684235 should Pass"       => ["345684235", true],
        "Amount with value 2354 should Pass"            => ["2354", true],
        "Amount with value 496832 should Pass"          => ["496832", true],
        "Amount with value 4294967295 should Pass"      => ["4294967295", true],
    ]
];
