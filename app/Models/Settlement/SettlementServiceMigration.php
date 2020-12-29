<?php

namespace RZP\Models\Settlement;

use RZP\Models\Feature\Constants;
use RZP\Models\Merchant\Preferences;

class SettlementServiceMigration
{
    const PREFIX_INTERNATIONAL = 'international:';

    const PREFIX_DOMESTIC = 'domestic:';

    const DEFAULT_CONST  = 'default';
    const DOMESTIC       = 'domestic';

    const NO_SCHEDULE_MAPPING_PRESENT = 'no schedule mapping present';

    const scheduleIdMapping = [
            'live' => [
                'Exelo4dBIBNb7w' =>	'FaBOwnO4AVhpQP',
                'EkMPag0vPhEoII' =>	'FaBSNZYCXUd3Je',
                'EbDIK1BCsdChRO' =>	'FaBX2rsdiIdGKK',
                'E199S87u5emrhc' =>	'FaBXxd1MOLqvuz',
                'D9OYLuzMqpixEN' =>	'FaBc7ypWPhaQ6B',
                'D9M7aRrlKklxeA' =>	'FaBdBtGZksK7Ig',
                'CopOjZuuZlVJQF' =>	'FaBeG4Y339hE8v',
                'C6RlMskzOd4P1f' =>	'FaBfuI7GmFfmVR',
                'Bxn1GzzaOXYiUH' =>	'FaBgrBufcq4kVt',
                'BoHfGJokmCajnV' =>	'FaBhcVSqEVRk4L',
                'Bn2WcETPmn44M4' =>	'FaBjQdy3ZmFl8V',
                'Bn2TDNApLgprBN' =>	'FaBdJpUChkiTjd',
                'BU3qfzAjT3xfI8' =>	'FaBeXhqRsbtVOx',
                'BOqaXQX7kGvZAw' =>	'FaBg1IijNzE1lL',
                'BOqZw6mMPCiAZ6' =>	'FaBgvG6J2VGTTP',
                'BOqHxJ2begGv3h' =>	'FaBhmmTl1dUDF1',
                'BNSX7DllPSd6FH' =>	'FaBlAA8ZgWgBYP',
                'BEEgsA9DoDOtMR' =>	'FaBmLJbf2mSleI',
                'BEEgUzZZhUEEx0' =>	'FaBnf4IhuO3xHD',
                'B2j5vKmxqwkNsb' =>	'FaBpNAUudchIHL',
                'B0MUVJul984k1k' =>	'FaBqDl03OzGRzS',
                'AMrWBvk7AWHEb1' =>	'FaBquiH6tKr0x3',
                'AHeF0Ljio2Ertp' =>	'FaBrkLnGNeX9Tq',
                '9qP0GhZzHqJAJZ' =>	'FaBZt8dKpqHlst',
                '9gDcKNbZsdka2i' =>	'FaBbsMRRZrZq3V',
                '9WDh2pkY3h9HWX' =>	'FaBd80qi8NGBgq',
                '9JBZK3HBwiECrd' =>	'FaBe8lNn6afgKa',
                '81yazpHIGJCPKQ' =>	'FaBexWYwaitTew',
                '7y2tOBpciGUxKA' =>	'FaBfuhX4epaofT',
                '7xc78ePv15g3bz' =>	'FaBghFc4LrdShI',
                '7s3Je6PYgxT2s1' =>	'FaBhWvWUHWCULn',
                '7eNCPavacsWE5D' =>	'FaBjPRxE2l1NjC',
                '7NcC6RxVACi5K7' =>	'FaBkQiywSGGXL0',
                '70cLLZOrU1rda6' =>	'FaBl4BrK2sjfYq',
                '70cFKcUYGQ7z0b' =>	'FaBmZDTyhYFfX2',
                '6iSiMdFzj16vMz' =>	'FaBcIvVpHXwSRI',
                '6iSiKg3whz8vTD' =>	'FaBdHQx131SzsD',
                '6iSiLM8shHpTub' =>	'FaBeR4RciLLr0s',
                '6iSiL3rghEV5qm' =>	'FaBf642uym87EV',
                '6iSiKBFKiFewWp' =>	'FaBgAT22diKMlo',
                '6iSiKJJojOtOQl' =>	'FaBguK2nuCvJNI',
                '6iSiK02cEdsncf' =>	'FaBhiWz7gMvhkW',
                '6iSiKMoPSRYJ2o' =>	'FaBiWgj7VcwsY2',
                '6iSiK54y6I6K75' =>	'FaBjHMtZopcUPE',
                '6i9KXrnqHXFHk9' =>	'FaBkAsTsDtrlKG',
                '6aAnMAFmYpY8Ps' =>	'FaBmFOVrSxuon3',
                'F8rIlU86u40T5U' =>	'FaBo58vbIwuJbq',
                'FaaE8UTF0BkMjX' =>	'FZiLhQXkTuUkIi',
            ],
            'test' => [
                'Exelo4dBIBNb7w' =>	'FVW4076gpnontA',
                'EkMPag0vPhEoII' =>	'FaBSNbVAtgir4H',
                'EbDIK1BCsdChRO' =>	'FaBX2xyyaTDBL8',
                'E199S87u5emrhc' =>	'FaBXxh61agAieq',
                'D9OYLuzMqpixEN' =>	'FaBc8jORXUmwcC',
                'D9M7aRrlKklxeA' =>	'FaBdBwPaxBNINY',
                'CopOjZuuZlVJQF' =>	'FaBeG38aUsofbV',
                'C6RlMskzOd4P1f' =>	'FaBfuGVb8EsbtV',
                'Bxn1GzzaOXYiUH' =>	'FaBgrESHeEzwm2',
                'BoHfGJokmCajnV' =>	'FaBhcXv7cmBc8U',
                'Bn2WcETPmn44M4' =>	'FaBjQgiMnWOm1P',
                'Bn2TDNApLgprBN' =>	'FaBdKAJbVg5aZD',
                'BU3qfzAjT3xfI8' =>	'FaBeXgBm4eSgZH',
                'BOqaXQX7kGvZAw' =>	'FaBg1JTJTtVkjf',
                'BOqZw6mMPCiAZ6' =>	'FaBgvGhXj7IRvg',
                'BOqHxJ2begGv3h' =>	'FaBkZdShIHHyu7',
                'BNSX7DllPSd6FH' =>	'FaBlANZjw4DtcG',
                'BEEgsA9DoDOtMR' =>	'FaBmLIQJOs0KuG',
                'BEEgUzZZhUEEx0' =>	'FaBnf7c1vDcYzU',
                'B2j5vKmxqwkNsb' =>	'FaBpNBPetoVpaO',
                'B0MUVJul984k1k' =>	'FaBqDmtX8JyHjx',
                'AMrWBvk7AWHEb1' =>	'FaBqulIXo5OLCb',
                'AHeF0Ljio2Ertp' =>	'FaBrkSOAjH3ryZ',
                '9qP0GhZzHqJAJZ' =>	'FaBZt8Q4i5oAIj',
                '9gDcKNbZsdka2i' =>	'FaBbsPVSg8BFWB',
                '9WDh2pkY3h9HWX' =>	'FaqsdxLvy4rvkG',
                '9JBZK3HBwiECrd' =>	'FaBe8msbMVhw9J',
                '81yazpHIGJCPKQ' =>	'FaBexYbyF3VWLL',
                '7y2tOBpciGUxKA' =>	'FaBfuiZK7dgvKb',
                '7xc78ePv15g3bz' =>	'Far1aT7zlilIIk',
                '7s3Je6PYgxT2s1' =>	'FaBhxVgYQ4Nqnn',
                '7eNCPavacsWE5D' =>	'FaBjPTCsyVDDbr',
                '7NcC6RxVACi5K7' =>	'FaBkQhRTMyLswv',
                '70cLLZOrU1rda6' =>	'FaBl4A7wqB29QE',
                '70cFKcUYGQ7z0b' =>	'FaBmZ3XQon8BzW',
                '6iSiMdFzj16vMz' =>	'FaBcIzd6EBA6Ba',
                '6iSiKg3whz8vTD' =>	'Faqvn3ijaVS61H',
                '6iSiLM8shHpTub' =>	'FaBeR4dhqae2dz',
                '6iSiL3rghEV5qm' =>	'FaBf64a8QX5ACD',
                '6iSiKBFKiFewWp' =>	'FaBgAQ27WQAl0q',
                '6iSiKJJojOtOQl' =>	'FaBguLLEQUQ47I',
                '6iSiK02cEdsncf' =>	'FaBhiWyIvME9Hk',
                '6iSiKMoPSRYJ2o' =>	'FaBiWhCCCX788p',
                '6iSiK54y6I6K75' =>	'FaBjHMKYWAoxHG',
                '6i9KXrnqHXFHk9' =>	'FaBkAsiZBb5yAM',
                '6aAnMAFmYpY8Ps' =>	'FaBmFN7468bc0m',
                'F8rIlU86u40T5U' =>	'FaBo58ziGxpCpw',
                'FaaE8UTF0BkMjX' =>	'FZiI5V59gdLg3r',
            ]
        ];

    const MID_SPECIFIC_MAPPINGS = [
            'domestic' => [
                'live' => [
                    Constants::ES_AUTOMATIC_THREE_PM => 'Fbh3lz0fnlLJtv',
                    Constants::ES_AUTOMATIC => 'FaBc7ypWPhaQ6B',
                    Preferences::MID_GOALWISE_TPV => 'Fbgz8YqG4QRsgb',
                    Preferences::MID_GOALWISE_NON_TPV => 'Fbgz8YqG4QRsgb',
                    Preferences::MID_WEALTHAPP => 'Fbgz8YqG4QRsgb',
                    Preferences::MID_WEALTHY => 'FbgyTlx4dWMiaU',
                    Preferences::MID_PAISABAZAAR => 'FaBe8lNn6afgKa',
                    Preferences::MID_KARVY => 'FbgzY8mEQohG09',
                    Preferences::MID_SCRIP_BOX => 'Fbh07JzgaM1Owh',
                    Bucket\Constants::MERCHANT_DSP => 'FbgwZ8rMIeZge0',
                    Preferences::MID_ET_MONEY => 'GIsrgdePEHYf60',
                ],
                'test' => [
                    Constants::ES_AUTOMATIC => 'FaBc8jORXUmwcC',
                    Constants::ES_AUTOMATIC_THREE_PM => 'Fbh3lzpQK8DVY2',
                    Preferences::MID_GOALWISE_TPV => 'Fbgz8XnQSaD23Y',
                    Preferences::MID_GOALWISE_NON_TPV => 'Fbgz8XnQSaD23Y',
                    Preferences::MID_WEALTHAPP => 'Fbgz8XnQSaD23Y',
                    Preferences::MID_WEALTHY => 'FbgyTxyGFJ6hMr',
                    Preferences::MID_PAISABAZAAR => 'FaBe8msbMVhw9J',
                    Preferences::MID_KARVY => 'FbgzY8IqB25BQ7',
                    Preferences::MID_SCRIP_BOX => 'Fbh07KpzvxqKeA',
                    Bucket\Constants::MERCHANT_DSP => 'FbgwJHq7sB3jMJ',
                    Preferences::MID_ET_MONEY => 'GIsrgfsZi13Mt7',
                ],
            ],
            'international' => [
                'live' => [
                    Constants::ES_AUTOMATIC_THREE_PM => 'Fbh3lz0fnlLJtv',
                    Constants::ES_AUTOMATIC => 'FaBc7ypWPhaQ6B',
                ],
                'test' => [
                    Constants::ES_AUTOMATIC => 'FaBc8jORXUmwcC',
                    Constants::ES_AUTOMATIC_THREE_PM => 'Fbh3lzpQK8DVY2',
                ],
            ],
        ];
}
