<?php

return [
    'testValidGstin' => [
        '29ABCDE1234L1Z1',
        '37ABCDE1237L1Z1',
        '37ABCDE1237X1Z2',
        '01ABCDE1237X1ZP',
    ],

    'testInvalidGstin' => [
        '',
        '123',
        'ABCDE1234M',
        '37ABCDE1237L1R1',
        '123456789012345',
        '00ABCDE1237L1R1',
    ],
];
