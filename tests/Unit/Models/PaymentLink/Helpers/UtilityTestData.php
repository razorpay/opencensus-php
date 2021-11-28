<?php

/**
 * <method_name> => ["test message" => [...attributes]]
 */
return [
    "testIsTextLink" => [
        "Empty String should fail" => [
            ["",  false],
        ],
        "Url String should pass" => [
            ["loremipsum something or someone is www.some.com",  true],
        ],
        "sub domain url String should pass" => [
            ["www.some.thing.com",  true],
        ],
        "email string should pass"  => [
            ["asds@addd.com",  true],
        ],
        "sub email string should pass"  => [
            ["asds@addd.asdad.com",  true],
        ],
        "email with . string should pass"  => [
            ["asds.aaad@addd.com",  true],
        ],
    ],

    "testConvertTextToQuillFormat" => [
        "String with a link" => [
            [
                "text"  => "This is some text which needs to be converted to quill format www.razorpay.com",
                "text_insert"       => "This is some text which needs to be converted to quill format",
                "link_attribute"    => [
                    "insert"        => "www.razorpay.com",
                    "attributes"    => [
                        "link"      => "www.razorpay.com"
                    ]
                ],
                "metaText"          => "This is some text which needs to be converted to quill format www.razorpay.com",
            ],
        ],

        "String without a link" => [
            [
                "text"          => "This is some text which needs to be converted to quill format",
                "text_insert"   => "This is some text which needs to be converted to quill format",
                "metaText"      => "This is some text which needs to be converted to quill format",
            ],
        ],
    ],
];
