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

    const FAILED_TO_FETCH_PARENT_CONFIG = 'failed to fetch parent config';

    const REGEX_MATCH_FAILURE_FOR_MERCHANT = 'regex match failing for merchant bank account name';

    const scheduleIdMapping = [
            'live' => [
                'Exelo4dBIBNb7w' =>	'FaBOwnO4AVhpQP',
                'EkMPag0vPhEoII' =>	'FaBSNZYCXUd3Je',
                'EbDIK1BCsdChRO' =>	'FaBX2rsdiIdGKK',
                'E199S87u5emrhc' =>	'FaBXxd1MOLqvuz',
                'D9OYLuzMqpixEN' =>	'IcV5wWjWweAqNK',
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
                'Fc53TUJT3yOLCo' => 'Fc4sKjBrNH4ATl',
                'GCx1lJpppoXTXG' => 'GBk1QuCe1bANtI',
                'FZivDneoLFhJaE' => 'IuCaAEToxkiH2l',
                'GdLNkaDChUHMCY' => 'IuCd5oVT4srsTQ',
            ],
            'test' => [
                'Exelo4dBIBNb7w' =>	'FVW4076gpnontA',
                'EkMPag0vPhEoII' =>	'FaBSNbVAtgir4H',
                'EbDIK1BCsdChRO' =>	'FaBX2xyyaTDBL8',
                'E199S87u5emrhc' =>	'FaBXxh61agAieq',
                'D9OYLuzMqpixEN' =>	'IcV5wcINXdtmx6',
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
                'Fc53TUJT3yOLCo' => 'Fc4sKotXXl09a0',
                'GCx1lJpppoXTXG' => 'GBkX2MtoXXLz9p',
                'FZivDneoLFhJaE' => 'IuCaACli1rIQGq',
                'GdLNkaDChUHMCY' => 'IuCd5rBhur3dDb',
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
                    Preferences::MID_WEALTHY => 'G4bBO9L8Xy31It',
                    Preferences::MID_PAISABAZAAR => 'GBk1QuCe1bANtI',
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
                    Preferences::MID_WEALTHY => 'G4bBO9kBmnzIw3',
                    Preferences::MID_PAISABAZAAR => 'GBkX2MtoXXLz9p',
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

    const MIGRATION_BLACKLISTED_MIDS = [

        //internal mids
        '9KmKJncCnrvko6','EegSBrCa8CXW0U','9KmLPrgmHhqjri','9KmPH3HU8XjHrq', '9KmMiCZ2rN1Bms','EegSCHnAG28ZRB',
        'EOriM2QWvzSBrO','EOs9InFtjbiVjv','CIHACFS1pQkdpF','HBnQvKYDg8X7dE',

    ];

    //this contans parent blacklisted mids, linked greylisted mids, and mswipe mids. These all are unblocked for migration To AXIS3.
    const MIGRATION_BLACKLISTED_MIDS_TO_AXIS3 = [

        'EZdvsF1SJ4fQ2s','Cr5PN8DFcCrosQ','EZe131Pviu19kf','CaTMnDC4WEZ86R','EZdsGl9wXWJVWu','EjR2V6HTDimML8','CqJaDXUzdQrfVv','7jPHjgTfP88Gnj','7jIJK4IwV1F4ui',
        'EcIeUFX04IvFLo','EZdXyuTeL5e9OW','EZdoan59NTBydX','3qIOThYDLRB4W4','EZdqqYyoTO11tb','EZdQQ2LvKbigY3','CDZvVwsQsJ79qS','EZdxJ43uewoEgE','7LAuMvKMcy7s0f',
        'GRRpOWPJlrea32','CqLpX9WWLyomO3','EZdznLvBNJCxem','EcJXIcUJqYptho','CZlf3XM7wZSjgt','EcIgOzkAQjxtxc','D2AZxA5xOgH06A','H59up9kvpszjtX','73oiq89pn3FBsl',
        'GtMiaYDOa25lLE','Gwkn3fR5Z9T1IH','HFfhKKMIFen1VW','HAaLI4yxLvGNDm','H4Fl9Hej9XL1iW','H9kJBtO070ZSiB','H9qcjJPosTjozO','H9sAHP91GRvb0T','GJ9pYET7RH2hRZ',
        'HFNvBEpPggifPQ','HI5pqymgWEs48i','HICRiqfUtDa1wS','Gs2Fg9UteKOpVZ','GyjXQVOEvtIXHz','H1r3Vbh3kqQDuO','H1yutb04Z7Eitz','H7qSPqAvTqlfAx','4sR6aB3rYxH3Sv',
        'Gpg694vXCYZEPJ','FpSlEttuQiVZly','FlVLID7iwAmoYM','FgQWP7p4caqIUD','G5HyByZ6eRYpEO','GoqhtDdLF1l7en','G6C3TKWnbeHCDf','GRAJlc4Eczn5sZ','4jrfbTLsua1pWJ',
        'GRAe5AzKJLRGkZ','GdJsdAgyH59tXG','GdM8kivIXV8kgh','Gkone0m5diaBC0','FrSrRzBomKqaWJ','FreHGYKC2tfRls','Fqkvwi11BJkuYf','FeME1n6GwlEifd','6UBmm8ExgIErvK',
        'FufhsES7YHJNWD','Fgn8wykFf699wb','FyboAdJ1vlCsGb','GQkXkGnGxTcpq1','Geb8sumu8LRYAy','Fx1vrFMPW0Byu9','Fx2b1PuVMXUqae','Flwli5eLqc6DL2','GX0500K9nKn9vY',
        'FdbItKMuu8I8Wa','FjCoNNc1YlSW93','FjEd6CogIa5zQW','GoRqGEIC9TizPj','G7orHxF3YuCetR','GZ5QNUi3jAUvI4','GO3z8vyJ35vGBk','GNTfGaPcE3imYe','GMJcLQTM3uUzfd',
        'EmbsYiqYfzrKAv','EtKJLPXJ1dUxMM','F4TmiwbVHzappB','Ey2HM94bFdlN4K','Ey5SIeLhE5xxBY','EyA7DnVwflVjxs','Ey9QlzNPHBsHWM','EOAGkWwiVupveL','EvHWJea1FaO2S3',
        'FFbDwHaTVE7grp','FEmSlhHBX3F2Ni','F2oB6hrqB8fjAn','ExO4eKBgjHgbNd','F0sFCmi0LOeeGc','FXHwNYp4cdLbUq','EfUUMd5zGw3nZx','FHTlpuWFDpXU6q','EmGIbvueqANhwe',
        'F01cvb5zEvt3ze','F02iHSCplfL5m7','EeJycFe4IGjJWR','EOQtf2ZZzVVY1i','EifsQLe6w1mwd7','EyPNOssL8tA1yo','F40u24NuYoOvib','F8iwLlM7BzVP7l','F8ivOAelDRG5ys',
        'F8idseasYbIOpz','EZDSYRFNcprD2Y','EZau38HQQsAmT4','EZajSwD7vePD1j','Esxa6aub2DY482','FW7mNDfUAREN3c','FBTQ59itdVwVFb','FBajiYfEBx2xUB','FPplojaGNGFA0q',
        'FPsUZ1nJKZGMhE','FBvA9mxGKtqWPx','EdrvaOsavCX2Ay','El5ThCUQaFYdpv','F8LJKi4ufbeE28','F8Lq0Ez9TEp7QQ','ErhYRsmOjiVdoU','FB6TKFh93qpHQy','FUsNPIODpJUE4L',
        'F7avOLkGNXmsYr','EU2hEnZlumoh7X','Egfl68lAFEDbdJ','EghHCGb1egBgNr','FOzVyMPVjo8hbg','EhtHoWq8Bx2EU9','EzJQOzN6kg48LY','EzJPtuXMDJRlxl','EQtmYqa3cHIr6B',
        'FSYhUDEDdTccrb','ENJMLanzObt6kg','Exgj3pcIYh57rw','Eq667fVw4rNTXB','FYAgmcCOAfGASp','EQ8AzfZip2meDu','Es5CywkLOhncCa','DNBqJJ4Jb2dB8X','DtxGGUPoTtCMX6',
        'DqwztYxyN3m9Vn','ED1vW52iLobQH8','Dsq2DRcddbudib','Dkwlba66uUF1DZ','Eh54Q1B6HQKbS3','EuxJCz8cZV9V63','FJSEZl2K9ftcPA','FJUeBC1VDIHxtu','FJVQ0XZwIAzofR',
        'E8D4A78IIz4SAB','D8sjcF5jo2mGhd','D8zYMlDih850dZ','DJeSPlsnQJcVZc','DSIImbTS8k9J7d','DBlZ9HUvb1Dbtf','E15HXgvPQB3vwj','E1DSYT6KV0l3j3','DgD2eIbfer2goF',
        'Dlk6XxrYxPxSk4','DyP8dTjuXkgcAA','D5iKLxghK6ppfI','EB2T2Ud5ZTD4xU','EB33bx1bQ9kenC','DUmi6ejwDKMg1L','EFSCVQ7vFsCe5W','E2Mw08X9tYN35Z','DVXdxST41fNRQo',
        'DVQObbyCpH8hoT','DVXjISdZvflZ0d','DUJVt5fuzA5Jg9','DUJEYQf1bKqpiV','DVA7FpFflGOmCm','DV2F2BG6NcbCzP','DV1r6RGeO37jPN','DaYe8FjDYFN1qz','DafY7CuC98D8mK',
        'DabN0OvdyG4uO9','E1ZR8A7vB35XY2','EAHJkntsW26eJt','EAFrs8XuyeooxQ','EAHatIYXmRtTtr','E75ckLHDIBJtyf','E4m7d8Z275epyQ','Dwn9FI4icXkjp1','E01AEnCiTSbEwz',
        'DzwV0Vho6fRZGG','D5OCp3uMEoA744','D9JUsPBWDiZMd1','EAfVEwqkn6L498','D6Xv2oa7rFR5jx','DYelXf2NpGJkOB','Dj3SdIHMMEYGBc','DKQNZKLQpFBqcd','DngsXfEnOwLx7G',
        'DXpgQKB0jrhso7','EFpYq8AXv2Ol4t','EFpYY6Tzj9Pjgp','EFpkhyOj38QS4v','DGqFCa77mcS1tS','DTwS3u7Thm5rvt','D3luOr5RBcpoqE','D3pxtJZxfYvsOG','DJFgCEcOrWHi4M',
        'DPursctwBGXKc3','Drg6umJRU0QMw7','DDoGL5ZnxTtMjm','Cc2UGe1AmkzVYg','Cc057xzfWMnyBn','DBJyfP5kRGNCnE','DBJyF5fJpaj9zZ','DBJxpKPn4XhsUx','DBJwWZIAR6ab32',
        'DBJvrtNiJw0K8V','BvJSFhgpLxaOoX','EHj1yrXZ4E7fcW','E5Y7vfjCRyb33C','D8SvPn3mg3XvCZ','CgqLTlU2yKG8av','Dm4Tadn7M2vXcu','CYseUgx4bt9VFp','Dhm3EIwBkOOiqO',
        'DhmmszCJe7x4sv','CY9aKsJrHsyW5v','CY8qu6PCIbh1kO','CcQjyA1mjLgVvV','CpCZN3YmnokQU5','Cp8lOtMZcVJ84l','CPS8AXRVPFnJPM','D2G2FP8v6eALvH','D2AI6Qy9nUOWMr',
        'D2ADUFPL3b2w83','D2BsrUJVg04abr','D3029l2s25cevz','D2zXCs9uJ6l0fI','Cz7p1zTADQ8Ttd','CpvXWGY0P9h1sY','BzJIw8uwBVbVUc','CVKpuoxsbkNLcH','C0XMstgt7Z06Qh',
        'CrwAJSDTaxFf4a','CZKwRrgAqAZDhR','CIg2kVL7np0wtA','CIgmp4xZrrGZSO','CdgQQVmlzjICNy','BsvNlRzGk2w7aG','CR3D37POcSDpR3','C3zNpHQkbmI8VR','C3zai3NleEoRxp',
        'CipLYVcizBrLUR','CHuWJkBvfYFau2','CaXhTC64tJLoHA','CLojVAOzbM5sGZ','CLoKh8xgw4V0e3','CLp7DnOal9jUfa','CLoroV0oM31y6R','CLozFPXGlj1lnt','CLpEoDXhZRgUTI',
        'CLniP7awACPKbM','CdHxnDVUXHkVgw','D0Eq1oW4x97AxO','D0FKg2U31L9shd','D0FIVXMkho6Ges','D0FGIlqzCfxVw0','D0FClz7VhKYwEU','Cv8Pbk9WMJsoBj','CBcPtPwFgpjdUp',
        'D0dJuTLPMCKwKE','CL7QQLGFtunX1x','CL7IkelU3kb7OC','CL7yc19WrRjBmE','CLAJQ7l29iesyk','CL9vMCmWQjkcrh','CKcihfUJB2qht3','ChAL2QBvKdunGT','CN7w5Kx2k6yfVi',
        'BsgisUUpYMCmq6','BsbLU3EIDmypSo','BsbCh4bndAKg5E','Cly15akyPGGIUj','CwbmtIQAudwMat','ByWbZS28NK9CeG','BDRXQLRCjCiQ6U','B02NGbrSXccgSX','AiAYycwSQYdCSV',
        'AXrozz1lM4UW3o','B1uh6CFFBKk35S','BhTq9tGcL4jMxI','AiWdjAyyF4RKBa','BGcSnU5DtXiNRf','BREsAWr9hzga0n','BkIuV4S2yMSRrT','Bl2864ytwSSy0I','Bl3JYI8Sq8yHJK',
        'AsSlzovv23CDfR','BGCcjW3LjskBAn','BGCD0MTKznEMvm','B5RPluj6SBVTri','BFRecjCGCORp0B','AYfqAtukCN25Ji','AXRuIp5uiz5Jsp','AXVtBzM1KI5M4r','Am9fknbvCwOhG9',
        'BO32cEvng3Rikz','AgeBUR39tqKaYu','B8fqIfYIBxh7GR','B7vexPwsHJh1CX','B7u5pSv2ZKuKjM','AbOaTM0K4VyadS','Bf6qijIBTngr6j','B8E18leeoIyujo','B8E9ahA3cRf3ex',
        'B8E8SPI9YJA6tg','B8JpyDBiJ6rIjb','BYqeLRvN6FfCCY','AqTDBVdDcu22Lj','BMYYnn9IkpH6TQ','AoURvHfDZlj9oy','AazhBBgirfMECr','AmReTNPu1KFKBn','Ao6bHUwxZYJLRD',
        'AoAMvqgYgr5lu3','BZE1zElyvepgPU','A5KRzVs3EaIkQX','AGQJfLbWcmjxDX','ABeBVV8dVzf7se','ABfrOZipdTNBvA','9xqtMsnyEkcg1l','9cRnJjmQyHBPNy','9kJ2oUN6UhvohS',
        '9Jb4lf1ioQEIFS','AVwtc7Gz7kDRFx','9ufleSBffUl7yn','9udYSvN6mdfLnm','AGxlcj6pkthRXO','9IjdEkLQb0j2ro','9RWSGxJscj8lLn','AC4DJNMIX9xXOz','9vqvEqi8BHdlpU',
        '9atfs7Y4uNAjdL','A5k8HpPS5wKcET','9fPqfKeIICEud0','9VIi8FakOk1SiV','9sGWNPsHm5Z9hj','9gV6aMB3vmmNFs','9k04AexLbZ6DwI','9dhe2WRR0XCQz6','8UQWiU5kHn1lnK',
        '8ytYezIThlseJd','8B2cVXWv3C2U96','92tu1zJ3WzqReo','8lv4idBRY4C9c0','8YPXDAy5HoBX0F','8YQygO7pzP3Gut','8oCcfYlaCs3bgR','7153Y38mzSIb26','7thBRSDflu7NHL',
        '7BfRNg10LH7N6T','5kmSnKGSV99DO0','5XBrWzODBkDPmi','5zJACbxPORFLk8',

        ];
}
