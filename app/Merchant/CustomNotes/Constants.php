<?php

namespace App\Merchant\CustomNotes;

class Constants
{
    const PAYMENT_LINKS_CUSTOM_NOTES = [
        "C9ZfBRKNLOljG8" => [
            "name" => 'SriRam',
            "type" => 'Business Segment',
            "options" => [
                [ "label" => 'BONUS', "value" => 'BONUS' ],
                [ "label" => 'DUPLICATE_CARD', "value" => 'DUPLICATE_CARD' ],
                [ "label" => 'EXCHANGE', "value" => 'EXCHANGE' ],
                [ "label" => 'GUEST_CERTIFICATE', "value" => 'GUEST_CERTIFICATE' ],
                [ "label" => 'OTHERS', "value" => 'OTHERS' ],
                [ "label" => 'PLATINUM_ENROLLMENT', "value" => 'PLATINUM_ENROLLMENT' ],
                [ "label" => 'PLATINUM_KIT', "value" => 'PLATINUM_KIT' ],
                [ "label" => 'PLATINUM_RENEWALS', "value" => 'PLATINUM_RENEWALS' ],
                [ "label" => 'RCI', "value" => 'RCI' ],
                [ "label" => 'RCI_DIRECTORY', "value" => 'RCI_DIRECTORY' ],
                [ "label" => 'RENEWALS', "value" => 'RENEWALS' ],
                [ "label" => 'RENEWAL_EXTENSION', "value" => 'RENEWAL_EXTENSION' ],
                [ "label" => 'RENTAL', "value" => 'RENTAL' ],
                [ "label" => 'SELF_ENROLLMENT', "value" => 'SELF_ENROLLMENT' ],
                [ "label" => 'SPACEBANK_EXTENSION', "value" => 'SPACEBANK_EXTENSION' ],
                [ "label" => 'ST_COMP_BOOK', "value" => 'ST_COMP_BOOK' ],
                [ "label" => 'TPRO1', "value" => 'TPRO1' ],
                [ "label" => 'TPRO2', "value" => 'TPRO2' ]
            ]
        ],

        "DJy3GWYGs76MnR" => [
            "name"  => 'RCI',
            "type"  => 'Business Segment',
            "options" => [
                [ 
                    "label" => 'BONUS', 
                    "value" => 'BONUS' 
                ],
                [ "label" => 'DUPLICATE_CARD', "value" => 'DUPLICATE_CARD' ],
                [ "label" => 'EXCHANGE', "value" => 'EXCHANGE' ],
                [ "label" => 'GUEST_CERTIFICATE', "value" => 'GUEST_CERTIFICATE' ],
                [ "label" => 'OTHERS', "value" => 'OTHERS' ],
                [ "label" => 'PLATINUM_ENROLLMENT', "value" => 'PLATINUM_ENROLLMENT' ],
                [ "label" => 'PLATINUM_KIT', "value" => 'PLATINUM_KIT' ],
                [ "label" => 'PLATINUM_RENEWALS', "value" => 'PLATINUM_RENEWALS' ],
                [ "label" => 'RCI', "value" => 'RCI' ],
                [ "label" => 'RCI_DIRECTORY', "value" => 'RCI_DIRECTORY' ],
                [ "label" => 'RENEWALS', "value" => 'RENEWALS' ],
                [ "label" => 'RENEWAL_EXTENSION', "value" => 'RENEWAL_EXTENSION' ],
                [ "label" => 'RENTAL', "value" => 'RENTAL' ],
                [ "label" => 'SELF_ENROLLMENT', "value" => 'SELF_ENROLLMENT' ],
                [ "label" => 'SPACEBANK_EXTENSION', "value" => 'SPACEBANK_EXTENSION' ],
                [ "label" => 'ST_COMP_BOOK', "value" => 'ST_COMP_BOOK' ],
                [ "label" => 'TPRO1', "value" => 'TPRO1' ],
                [ "label" => 'TPRO2', "value" => 'TPRO2' ],
            ],
        ],

        "BYqeLRvN6FfCCY" =>  [
            "name"  => 'Apollo',
            "type"  => 'Scenario',
            "options"  => [
                [
                    "label" => 'NB',
                    "value" => 'NB',
                ],
                [
                    "label" => 'RNC',
                    "value" => 'RNC',
                ],
                [
                    "label" => 'RNNC',
                    "value" => 'RNNC',
                ],
                [
                    "label" => 'LOAD',
                    "value" => 'LOAD',
                ],
                [
                    "label" => 'SHPR',
                    "value" => 'SHPR',
                ],
                [
                    "label" => 'TRVL',
                    "value" => 'TRVL',
                ],
                [
                    "label" => 'ENDO',
                    "value" => 'ENDO',
                ],
                [
                    "label" => 'GRP',
                    "value" => 'GRP',
                ],
                [
                    "label" => 'CHBO',
                    "value" => 'CHBO',
                ],
                [
                    "label" => 'RECOR',
                    "value" => 'RECOR',
                ],
            ],
        ]
    ];

    public static function getNotesForPaymentLinksByMID($mid)
    {
        return self::PAYMENT_LINKS_CUSTOM_NOTES[$mid] ?? null;
    }
}
