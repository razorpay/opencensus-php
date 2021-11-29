<?php

return [
    "testGetProductConfig"  => [
        "valid json string"  => [
            '{"a": "b", "c":"d"}',
            "a",
            "b"
        ],
        "empty json string"  => [
            '',
            "a"
        ],
        "empty key"  => [
            '{"a": "b", "c":"d"}',
            null,
            ["a" => "b", "c" => "d"]
        ]
    ]
];
