<?php

namespace RZP\Models\Settlement;

use RZP\Constants\Mode;
use RZP\Models\Settlement\Channel;

class NodalAccount
{
    const ACCOUNT_MAP = [
        Mode::LIVE => [
            Channel::AXIS    => '9KmKJncCnrvko6',
            Channel::HDFC    => '9LAQrNwLUOthh5',
            Channel::ICICI   => '9KmLPrgmHhqjri',
            Channel::KOTAK   => '9KmHswlZnMMR7I',
            Channel::RBL     => '9KmPH3HU8XjHrq',
            Channel::YESBANK => '9KmMiCZ2rN1Bms',
            Channel::AXIS2   => '9KmKJncCnrvko6',
        ],

        Mode::TEST => [
            Channel::AXIS    => '10000000000000',
            Channel::HDFC    => '10000000000000',
            Channel::ICICI   => '10000000000000',
            Channel::KOTAK   => '10000000000000',
            Channel::RBL     => '10000000000000',
            Channel::YESBANK => '10000000000000',
            Channel::AXIS2   => '10000000000000',
        ]
    ];
}
