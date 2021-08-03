<?php

namespace RZP\Models\Merchant\PurposeCode;

class PurposeCodeList
{
    const CODES = "codes";
    const PURPOSECODE = "purposeCode";
    const DESCRIPTION = "description";
    const PURPOSEGROUP = "purposeGroup";

    //purpose code categories
    const TRAVEL = "Travel";
    const PERSONAL = "Personal, Cultural & Recreational services";
    const EXPORT_OF_GOODS = "Exports (of Goods)";
    const OTHER_SERVICES = "Other Business Services";
    const COMPUTER = "Computer & Information Services";
    const MANUFACTURING_SERVICES = "Manufacturing services";
    const TELECOMMUNICATION = "Telecommunication, Computer & Information Services";

    //purpose code
    const P0103 = 'P0103';
    const P0802 = 'P0802';
    const P0807 = 'P0807';
    const P1004 = 'P1004';
    const P1005 = 'P1005';
    const P1006 = 'P1006';
    const P1007 = 'P1007';
    const P1008 = 'P1008';
    const P1009 = 'P1009';
    const P1002 = 'P1002';
    const P1019 = 'P1019';
    const P0301 = 'P0301';
    const P0801 = 'P0801';
    const P0803 = 'P0803';
    const P0804 = 'P0804';
    const P0805 = 'P0805';
    const P0806 = 'P0806';
    const P1013 = 'P1013';
    const P1101 = 'P1101';
    const P1020 = 'P1020';
    const P1104 = 'P1104';
    const P1099 = 'P1099';
    const P1015 = 'P1015';
    const P1016 = 'P1016';
    const P1107 = 'P1107';
    const P1109 = 'P1109';
    const P1701 = 'P1701';
    const P0302 = 'P0302';
    const P0304 = 'P0304';
    const P0305 = 'P0305';
    const P0306 = 'P0306';
    const P0808 = 'P0808';
    const P1014 = 'P1014';
    const P1105 = 'P1105';
    const P1106 = 'P1106';
    const P1108 = 'P1108';

    //purpose code descriptions
    const P0103_DESC = 'Advance receipts against export contracts, which will be covered later by GR/PP/SOFTEX/SDF';
    const P0802_DESC = 'Software consultancy/implementation (other than those covered in SOFTEX form)';
    const P0807_DESC = 'Off site Software Exports';
    const P1004_DESC = 'Legal services';
    const P1005_DESC = 'Accounting, auditing, book keeping and tax consulting services';
    const P1006_DESC = 'Business and management consultancy and public relations services';
    const P1007_DESC = 'Advertising, trade fair, market research and public opinion polling services';
    const P1008_DESC = 'Research & Development services';
    const P1009_DESC = 'Architectural, engineering and other technical services';
    const P1002_DESC = 'Trade related services – Commission on exports/imports.';
    const P1019_DESC = 'Other services not included elsewhere';
    const P0301_DESC = 'Purchases towards travel (Includes purchases of foreign TCs, currency notes etc over the counter, by hotels, hospitals, Emporiums, Educational institutions etc. as well as amount received by TT/SWIFT transfers or debit to Non-Resident account).';
    const P0801_DESC = 'Hardware consultancy/implementation';
    const P0803_DESC = 'Data base, data processing charges';
    const P0804_DESC = 'Repair and maintenance of computer and software';
    const P0805_DESC = 'News agency services';
    const P0806_DESC = 'Other information services- Subscription to newspapers, periodicals, etc.';
    const P1013_DESC = 'Environmental Services';
    const P1101_DESC = 'Audio-visual and related services – services and associated fees related to production of motion pictures, rentals, fees received by actors, directors, producers and fees for distribution rights.';
    const P1020_DESC = 'Wholesale and retailing trade services.';
    const P1104_DESC = 'Entertainment services';
    const P1099_DESC = 'Other services not included elsewhere';
    const P1015_DESC = 'Tax consulting services';
    const P1016_DESC = 'Market research and public opinion polling service';
    const P1107_DESC = 'Educational services (e.g. fees received for correspondence courses offered to non-resident by Indian institutions)';
    const P1109_DESC = 'Other Personal, Cultural & Recreational services';
    const P1701_DESC = 'Receipts on account of processing of goods';
    const P0302_DESC = 'Business travel';
    const P0304_DESC = 'Travel for medical treatment including TCs purchased by hospitals';
    const P0305_DESC = 'Travel for education including TCs purchased by educational institutions';
    const P0306_DESC = 'Other travel receipts';
    const P0808_DESC = 'Telecommunication services including electronic mail services and voice mail services';
    const P1014_DESC = 'Engineering Services';
    const P1105_DESC = 'Museums, library and archival services';
    const P1106_DESC = 'Recreation and sporting activity services';
    const P1108_DESC = 'Health Service (Receipts on account of services provided by Indian hospitals, doctors, nurses, paramedical and similar services etc.rendered remotely or on-site) ';

    //purpose code mapping
    protected static $purposeCodeDescMappings = [
        self::P0103 => self::P0103_DESC,
        self::P0802 => self::P0802_DESC,
        self::P0807 => self::P0807_DESC,
        self::P1004 => self::P1004_DESC,
        self::P1005 => self::P1005_DESC,
        self::P1006 => self::P1006_DESC,
        self::P1007 => self::P1007_DESC,
        self::P1008 => self::P1008_DESC,
        self::P1009 => self::P1009_DESC,
        self::P1002 => self::P1002_DESC,
        self::P1019 => self::P1019_DESC,
        self::P0301 => self::P0301_DESC,
        self::P0801 => self::P0801_DESC,
        self::P0803 => self::P0803_DESC,
        self::P0804 => self::P0804_DESC,
        self::P0805 => self::P0805_DESC,
        self::P0806 => self::P0806_DESC,
        self::P1013 => self::P1013_DESC,
        self::P1101 => self::P1101_DESC,
        self::P1020 => self::P1020_DESC,
        self::P1104 => self::P1104_DESC,
        self::P1099 => self::P1099_DESC,
        self::P1015 => self::P1015_DESC,
        self::P1016 => self::P1016_DESC,
        self::P1107 => self::P1107_DESC,
        self::P1109 => self::P1109_DESC,
        self::P1701 => self::P1701_DESC,
        self::P0302 => self::P0302_DESC,
        self::P0304 => self::P0304_DESC,
        self::P0305 => self::P0305_DESC,
        self::P0306 => self::P0306_DESC,
        self::P0808 => self::P0808_DESC,
        self::P1014 => self::P1014_DESC,
        self::P1105 => self::P1105_DESC,
        self::P1106 => self::P1106_DESC,
        self::P1108 => self::P1108_DESC,
    ];

    //purpose category and their code mapping
    const EXPORT_OF_GOODS_CODES = [
        [
            self::PURPOSECODE => self::P0103,
            self::DESCRIPTION => self::P0103_DESC,
        ]
    ];

    const MANUFACTURING_SERVICES_CODES = [
        [
            self::PURPOSECODE => self::P1701,
            self::DESCRIPTION => self::P1701_DESC,
        ]
    ];

    const TELECOMMUNICATION_CODES = [
        [
            self::PURPOSECODE => self::P0808,
            self::DESCRIPTION => self::P0808_DESC,
        ]
    ];

    const COMPUTER_CODES = [
        [
            self::PURPOSECODE => self::P0801,
            self::DESCRIPTION => self::P0801_DESC,
        ],
        [
            self::PURPOSECODE => self::P0802,
            self::DESCRIPTION => self::P0802_DESC,
        ],
        [
            self::PURPOSECODE => self::P0803,
            self::DESCRIPTION => self::P0803_DESC,
        ],
        [
            self::PURPOSECODE => self::P0804,
            self::DESCRIPTION => self::P0804_DESC,
        ],
        [
            self::PURPOSECODE => self::P0805,
            self::DESCRIPTION => self::P0805_DESC,
        ],
        [
            self::PURPOSECODE => self::P0806,
            self::DESCRIPTION => self::P0806_DESC,
        ],
        [
            self::PURPOSECODE => self::P0807,
            self::DESCRIPTION => self::P0807_DESC,
        ]
    ];

    const TRAVEL_CODES = [
        [
            self::PURPOSECODE => self::P0301,
            self::DESCRIPTION => self::P0301_DESC,
        ],
        [
            self::PURPOSECODE => self::P0302,
            self::DESCRIPTION => self::P0302_DESC,
        ],
        [
            self::PURPOSECODE => self::P0304,
            self::DESCRIPTION => self::P0304_DESC,
        ],
        [
            self::PURPOSECODE => self::P0305,
            self::DESCRIPTION => self::P0305_DESC,
        ],
        [
            self::PURPOSECODE => self::P0306,
            self::DESCRIPTION => self::P0306_DESC,
        ]
    ];

    const PERSONAL_CODES = [
        [
            self::PURPOSECODE => self::P1101,
            self::DESCRIPTION => self::P1101_DESC,
        ],
        [
            self::PURPOSECODE => self::P1104,
            self::DESCRIPTION => self::P1104_DESC,
        ],
        [
            self::PURPOSECODE => self::P1107,
            self::DESCRIPTION => self::P1107_DESC,
        ],
        [
            self::PURPOSECODE => self::P1109,
            self::DESCRIPTION => self::P1109_DESC,
        ],
        [
            self::PURPOSECODE => self::P1105,
            self::DESCRIPTION => self::P1105_DESC,
        ],
        [
            self::PURPOSECODE => self::P1106,
            self::DESCRIPTION => self::P1106_DESC,
        ],
        [
            self::PURPOSECODE => self::P1108,
            self::DESCRIPTION => self::P1108_DESC,
        ]
    ];

    const OTHER_SERVICES_CODES = [
        [
            self::PURPOSECODE => self::P1002,
            self::DESCRIPTION => self::P1002_DESC,
        ],
        [
            self::PURPOSECODE => self::P1004,
            self::DESCRIPTION => self::P1004_DESC,
        ],
        [
            self::PURPOSECODE => self::P1005,
            self::DESCRIPTION => self::P1005_DESC,
        ],
        [
            self::PURPOSECODE => self::P1006,
            self::DESCRIPTION => self::P1006_DESC,
        ],
        [
            self::PURPOSECODE => self::P1007,
            self::DESCRIPTION => self::P1007_DESC,
        ],
        [
            self::PURPOSECODE => self::P1008,
            self::DESCRIPTION => self::P1008_DESC,
        ],
        [
            self::PURPOSECODE => self::P1009,
            self::DESCRIPTION => self::P1009_DESC,
        ],
        [
            self::PURPOSECODE => self::P1013,
            self::DESCRIPTION => self::P1013_DESC,
        ],
        [
            self::PURPOSECODE => self::P1019,
            self::DESCRIPTION => self::P1019_DESC,
        ],
        [
            self::PURPOSECODE => self::P1020,
            self::DESCRIPTION => self::P1020_DESC,
        ],
        [
            self::PURPOSECODE => self::P1099,
            self::DESCRIPTION => self::P1099_DESC,
        ],
        [
            self::PURPOSECODE => self::P1015,
            self::DESCRIPTION => self::P1015_DESC,
        ],
        [
            self::PURPOSECODE => self::P1016,
            self::DESCRIPTION => self::P1016_DESC,
        ],
        [
            self::PURPOSECODE => self::P1014,
            self::DESCRIPTION => self::P1014_DESC,
        ]
    ];

    //purpose code list with categories and  their codes
    const PURPOSE_CODE_LIST = [
        [
            self::PURPOSEGROUP => self::EXPORT_OF_GOODS,
            self::CODES => self::EXPORT_OF_GOODS_CODES,
        ],
        [
            self::PURPOSEGROUP => self::MANUFACTURING_SERVICES,
            self::CODES => self::MANUFACTURING_SERVICES_CODES,
        ],
        [
            self::PURPOSEGROUP => self::TELECOMMUNICATION,
            self::CODES => self::TELECOMMUNICATION_CODES,
        ],
        [
            self::PURPOSEGROUP => self::COMPUTER,
            self::CODES => self::COMPUTER_CODES,
        ],
        [
            self::PURPOSEGROUP => self::TRAVEL,
            self::CODES => self::TRAVEL_CODES,
        ],
        [
            self::PURPOSEGROUP => self::PERSONAL,
            self::CODES => self::PERSONAL_CODES,
        ],
        [
            self::PURPOSEGROUP => self::OTHER_SERVICES,
            self::CODES => self::OTHER_SERVICES_CODES,
        ]
    ];

    public static function getPurposeCodeDescDescription($purposeCode)
    {
        return self::$purposeCodeDescMappings[$purposeCode];
    }

}
