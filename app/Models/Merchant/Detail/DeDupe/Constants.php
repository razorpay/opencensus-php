<?php


namespace RZP\Models\Merchant\Detail\DeDupe;

use RZP\Models\Merchant\Detail;
use RZP\Models\User\Entity as UserEntity;

class Constants
{
    const ACTION    = 'action';
    const CLIENT_IP = 'client_ip';
    const DEACTIVATE            = 'deactivate';
    const UNREG_DEACTIVATE      = 'unreg_deactivate';
    const RAS_SIGNUP_LOCK       = 'ras_signup_lock';

    const DEDUPE_BLOCKED_TAG    = 'dedupe_blocked';
    const DEDUPE_TAG            = 'dedupe_underreview';

    const BRAND_LIST = 'brand_list';
    const BLACKLIST = 'blacklist';
    const HIGH_RISK_LIST = 'high_risk_list';
    const AUTHORITIES_LIST = 'authorities_list';
    const AUTHORITIES_CIN_LIST = 'authorities_CIN_list';
    const AUTHORITIES_PPAN_LIST = 'authorities_PPAN_list';
    const AUTHORITIES_CPAN_LIST = 'authorities_CPAN_list';
    const AUTHORITIES_GSTIN_LIST = 'authorities_GSTIN_list';
    const AUTHORITIES_BANK_ACCOUNT_LIST = 'authorities_bank_account_list';

    const EXACT_MATCH = 'exact_match';
    const FUZZY_MATCH = 'fuzzy_match';
    const FUZZY_MATCH_THRESHOLD = 'FUZZY_MATCH_THRESHOLD';
    const MERCHANT_RISK_CLIENT_TYPE_ONBOARDING = 'onboarding';

    const MATCHED_ENTITY = 'matched_entity';
    const FIELDS         = 'fields';
    const KEY            = 'key';
    const VALUE          = 'value';

    const MERCHANT_RISK_CONFIG = [
        Detail\Entity::PROMOTER_PAN => [
            'lists' => [
                self::BLACKLIST,
                self::AUTHORITIES_PPAN_LIST,
            ],
            'config_key' => 'promoter_pan'
        ],
        Detail\Entity::COMPANY_PAN => [
            'lists' => [
                self::BLACKLIST,
                self::AUTHORITIES_CPAN_LIST,
            ],
            'config_key' => 'company_pan'
        ],
        Detail\Entity::COMPANY_CIN => [
            'lists' => [
                self::BLACKLIST,
                self::AUTHORITIES_CIN_LIST,
            ],
            'config_key' => 'cin'
        ],
        Detail\Entity::GSTIN => [
            'lists' => [
                self::BLACKLIST,
                self::AUTHORITIES_GSTIN_LIST,
            ],
            'config_key' => 'gstin'
        ],
        Detail\Entity::BANK_ACCOUNT_NUMBER => [
            'lists' => [
                self::BLACKLIST,
                self::AUTHORITIES_BANK_ACCOUNT_LIST,
            ],
            'config_key' => 'bank_account_number'
        ],
        Detail\Entity::BANK_BRANCH_IFSC => [
            'lists' => [
                self::BLACKLIST,
            ],
            'config_key' => 'bank_branch_ifsc'
        ],
        Detail\Entity::CONTACT_MOBILE => [
            'lists' => [
                self::BLACKLIST,
            ],
            'config_key' => 'mobile'
        ],
        Detail\Entity::CONTACT_EMAIL => [
            'lists' => [
                self::BLACKLIST,
            ],
            'config_key' => 'email'
        ],
        Detail\Entity::BUSINESS_WEBSITE => [
            'lists' => [
                self::BLACKLIST,
                self::BRAND_LIST
            ],
            'config_key' => 'website'
        ],
        Detail\Entity::BUSINESS_NAME => [
            'lists' => [
                self::BRAND_LIST,
                self::HIGH_RISK_LIST,
                self::AUTHORITIES_LIST,
            ],
            'config_key' => 'merchant_name'
        ],
        Detail\Entity::BUSINESS_DBA => [
            'lists' => [
                self::BRAND_LIST,
                self::HIGH_RISK_LIST,
                self::AUTHORITIES_LIST,
            ],
            'config_key' => 'billing_name'
        ],
        self::CLIENT_IP                 => [
            'lists'      => [
                self::BLACKLIST,
            ],
            'config_key' => self::CLIENT_IP,
        ],
        UserEntity::CLIENT_ID           => [
            'lists'      => [
                self::BLACKLIST,
            ],
            'config_key' => "client_id",
        ],
    ];

    const MERCHANT_RISK_ACTIONS = [
        [
            'keysToCheck' => [
                Detail\Entity::PROMOTER_PAN => [
                    'list' => self::BLACKLIST,
                    'matchType'=> self::EXACT_MATCH,
                ]
            ],
            self::ACTION => self::DEACTIVATE
        ],
        [
            'keysToCheck' => [
                Detail\Entity::PROMOTER_PAN => [
                    'list' => self::AUTHORITIES_PPAN_LIST,
                    'matchType'=> self::EXACT_MATCH,
                ]
            ],
            self::ACTION => self::DEACTIVATE
        ],
        [
            'keysToCheck' => [
                Detail\Entity::COMPANY_PAN => [
                    'list' => self::BLACKLIST,
                    'matchType'=> self::EXACT_MATCH,
                ]
            ],
            self::ACTION => self::DEACTIVATE
        ],
        [
            'keysToCheck' => [
                Detail\Entity::COMPANY_PAN => [
                    'list' => self::AUTHORITIES_CPAN_LIST,
                    'matchType'=> self::EXACT_MATCH,
                ]
            ],
            self::ACTION => self::DEACTIVATE
        ],
        [
            'keysToCheck' => [
                Detail\Entity::COMPANY_CIN => [
                    'list' => self::BLACKLIST,
                    'matchType'=> self::EXACT_MATCH,
                ]
            ],
            self::ACTION => self::DEACTIVATE
        ],
        [
            'keysToCheck' => [
                Detail\Entity::COMPANY_CIN => [
                    'list' => self::AUTHORITIES_CIN_LIST,
                    'matchType'=> self::EXACT_MATCH,
                ]
            ],
            self::ACTION => self::DEACTIVATE
        ],
        [
            'keysToCheck' => [
                Detail\Entity::GSTIN => [
                    'list' => self::BLACKLIST,
                    'matchType'=> self::EXACT_MATCH,
                ]
            ],
            self::ACTION => self::DEACTIVATE
        ],
        [
            'keysToCheck' => [
                Detail\Entity::GSTIN => [
                    'list' => self::AUTHORITIES_GSTIN_LIST,
                    'matchType'=> self::EXACT_MATCH,
                ]
            ],
            self::ACTION => self::DEACTIVATE
        ],
        [
            'keysToCheck' => [
                Detail\Entity::BANK_ACCOUNT_NUMBER => [
                    'list' => self::BLACKLIST,
                    'matchType'=> self::EXACT_MATCH,
                ],
                Detail\Entity::BANK_BRANCH_IFSC => [
                    'list' => self::BLACKLIST,
                    'matchType'=> self::EXACT_MATCH,
                ]
            ],
            self::ACTION => self::DEACTIVATE
        ],
        [
            'keysToCheck' => [
                Detail\Entity::BANK_ACCOUNT_NUMBER => [
                    'list' => self::AUTHORITIES_BANK_ACCOUNT_LIST,
                    'matchType'=> self::EXACT_MATCH,
                ],
            ],
            self::ACTION => self::DEACTIVATE
        ],
        [
            'keysToCheck' => [
                Detail\Entity::CONTACT_MOBILE => [
                    'list' => self::BLACKLIST,
                    'matchType'=> self::EXACT_MATCH,
                ]
            ],
            self::ACTION => self::DEACTIVATE
        ],
        [
            'keysToCheck' => [
                Detail\Entity::CONTACT_EMAIL => [
                    'list' => self::BLACKLIST,
                    'matchType'=> self::EXACT_MATCH,
                ]
            ],
            self::ACTION => self::DEACTIVATE
        ],
        [
            'keysToCheck' => [
                Detail\Entity::BUSINESS_WEBSITE => [
                    'list' => self::BLACKLIST,
                    'matchType'=> self::EXACT_MATCH,
                ]
            ],
        ],
        [
            'keysToCheck' => [
                Detail\Entity::BUSINESS_WEBSITE => [
                    'list' => self::BRAND_LIST,
                    'matchType'=> self::EXACT_MATCH,
                ]
            ],
        ],
        [
            'keysToCheck' => [
                Detail\Entity::BUSINESS_NAME => [
                    'list' => self::HIGH_RISK_LIST,
                    'matchType'=> self::EXACT_MATCH
                ]
            ],
        ],
        [
            'keysToCheck' => [
                Detail\Entity::BUSINESS_NAME => [
                    'list' => self::AUTHORITIES_LIST,
                    'matchType'=> self::EXACT_MATCH
                ]
            ],
        ],
        [
            'keysToCheck' => [
                Detail\Entity::BUSINESS_DBA => [
                    'list' => self::HIGH_RISK_LIST,
                    'matchType'=> self::EXACT_MATCH
                ]
            ],
        ],
        [
            'keysToCheck' => [
                Detail\Entity::BUSINESS_DBA => [
                    'list' => self::AUTHORITIES_LIST,
                    'matchType'=> self::EXACT_MATCH
                ]
            ],
        ],
        [
            'keysToCheck' => [
                Detail\Entity::BUSINESS_NAME => [
                    'list' => self::BRAND_LIST,
                    'matchType'=> self::EXACT_MATCH
                ]
            ],
        ],
        [
            'keysToCheck' => [
                Detail\Entity::BUSINESS_DBA => [
                    'list' => self::BRAND_LIST,
                    'matchType'=> self::EXACT_MATCH
                ]
            ],
        ],
        [
            'keysToCheck' => [
                self::CLIENT_IP => [
                    'list' => self::BLACKLIST,
                    'matchType'=> self::EXACT_MATCH,
                ]
            ],
        ],
        [
            'keysToCheck' => [
                UserEntity::CLIENT_ID => [
                    'list' => self::BLACKLIST,
                    'matchType'=> self::EXACT_MATCH,
                ]
            ],
        ],
    ];

    const MERCHANT_RISK_CONFIG_NOT_IN_MERCHANT_DETAILS_ENTITY = [self::CLIENT_IP, UserEntity::CLIENT_ID];

    const MERCHANT_RISK_FIELD_CONFIG_KEY_MAP = [
        Detail\Entity::PROMOTER_PAN  => 'promoter_pan',
        Detail\Entity::COMPANY_PAN	=> 'company_pan',
        Detail\Entity::GSTIN =>	'gstin',
        Detail\Entity::BANK_ACCOUNT_NUMBER => 'bank_account_number',
        Detail\Entity::CONTACT_MOBILE => 'mobile'
    ];

    const XPRESS_ONBOARDING_CLIENT_TYPE = "xpress_onboarding";

    const FIELD_ALREADY_EXIST           = 'FIELD_ALREADY_EXIST';
}
