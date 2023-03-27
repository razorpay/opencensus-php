<?php

namespace RZP\Models\Merchant\Methods;

use RZP\Models\Terminal\Category;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Models\Merchant\Core as MerchantCore;

class DefaultMethodsForCategory
{
        const BLACKLISTED_METHODS = 'blacklisted_methods';
        const GREYLISTED_METHODS = 'greylisted_methods';
        const IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS = 'ignore_blacklisted_for_instrument_requests_methods'; // methods are blacklisted for auto enablement at the time of merchant activation but merchant should be able to request it via insttruments(Payment Methods) dashboard

    const AMEX_BLACKLISTED_MCCS = [
            '4411', '4813', '4816', '5962', '5963', '5966', '5967', '6010', '6011', '6012', '6532', '6536', '6537', '6538', '7273',
            '7800', '7801', '7802', '7995', '9223', '6533', '8651', '6050', '6051', '6513', '6211', '7012', '4829', '3064', '3043',
            '3287', '3167', '3285', '3263', '3286', '3011', '3030', '3076', '3083', '3021', '3236', '3068', '3296', '3071', '3009',
            '4511', '3261', '3282', '3007', '3020', '3065', '3280', '3044', '3148', '3028', '3298', '3025', '3267', '3266', '3177',
            '3151', '3096', '3256', '3013', '3161', '3252', '3253', '3001', '3033', '3098', '3243', '3051', '3242', '3053', '3039',
            '3241', '3300', '3240', '3041', '3239', '3106', '3005', '3111', '3171', '3234', '3099', '3228', '3072', '3078', '3206',
            '3223', '3222', '3220', '3219', '3061', '3088', '3046', '3217', '3292', '3059', '3058', '3212', '3004', '3245', '3293',
            '3037', '3032', '3026', '3294', '3034', '3003', '3084', '3042', '3204', '3132', '3103', '3156', '3247', '3040', '3200',
            '3062', '3197', '3196', '3102', '3050', '3193', '3191', '3006', '3056', '3174', '3079', '3190', '3295', '3082', '3038',
            '3055', '3187', '3054', '3052', '3186', '3185', '3184', '3182', '3008', '3146', '3100', '3213', '3181', '3178', '3087',
            '3023', '3175', '3085', '3172', '3045', '3164', '3211', '3060', '3031', '3183', '3024', '3002', '3022', '3159', '3012',
            '3136', '3048', '3010', '3246', '3229', '3231', '3014', '3016', '3075', '3226', '3029', '3017', '3066', '3097', '3260',
            '3069', '3130', '3129', '3015', '3127', '3248', '3125', '3035', '3297', '3077', '3047', '3089', '3221', '3049', '3063',
            '3090', '3027', '3000', '3067', '3018', '3036', '3117', '3057', '3144', '3188', '3131', '3180', '3299', '3112', '3094',
            '3412', '3374', '3354', '3441', '3351', '3364', '3361', '3376', '3387', '3421', '3362', '3352', '3420', '3400', '3436',
            '3425', '3423', '3389', '3427', '3353', '3366', '3428', '3390', '3398', '3405', '3381', '3391', '3409', '3357', '3368',
            '3429', '3438', '3394', '3430', '3388', '3439', '3393', '3359', '3370', '3431', '3432', '3386', '3355', '3360', '3395',
            '3396', '3380', '3385', '3433', '3434', '3435', '3681', '3817', '3560', '3788', '3619', '3754', '3517', '3617', '3614',
            '3514', '3536', '3537', '3511', '3670', '3671', '3824', '3594', '3805', '3797', '3549', '3826', '3728', '3556', '3764',
            '3765', '3502', '3749', '3743', '3620', '3733', '3683', '3547', '3727', '3684', '3685', '3712', '3771', '3582', '3672',
            '3559', '3833', '3757', '3744', '3821', '3787', '3716', '3763', '3544', '3662', '3717', '3792', '3687', '3677', '3747',
            '3742', '3611', '3552', '3736', '3562', '3688', '3538', '3721', '3689', '3828', '3829', '3690', '3529', '3750', '3678',
            '3593', '3832', '3629', '3510', '3648', '3581', '3691', '3804', '3780', '3587', '3589', '3623', '3692', '3527', '3693',
            '3525', '3644', '3694', '3669', '3807', '3746', '3652', '3695', '3798', '3628', '3696', '3621', '3627', '3630', '3697',
            '3715', '3784', '3590', '3664', '3711', '3607', '3505', '3570', '3778', '3714', '3543', '3578', '3766', '3507', '3827',
            '3566', '3608', '3609', '3610', '3506', '3571', '3753', '3561',' 3794', '3823', '3618', '3799', '3760', '3665', '3698',
            '3731', '3734', '3588', '3616', '3604', '3504', '3535', '3815', '3501', '3816', '3755', '3800', '3751', '3595', '3680',
            '3580', '3533', '3813', '3579', '3541', '3546', '3625', '3663', '3548', '3638', '3602', '3585', '3647', '3640', '3812',
            '3540', '3673', '3651', '3724', '3512', '3675', '3606', '3729', '3558', '3563', '3820', '3605', '3758', '3718', '3531',
            '3660', '3810', '3701', '3516', '3576', '3568', '3624', '3654', '3667', '3808', '3767', '3818', '3777', '3577', '3557',
            '3668' ,'3509', '3735', '3520', '3622', '3661', '3730', '3613', '3699', '3741', '3551', '3572', '3676', '3700', '3639',
            '3612', '3772', '3776', '3774', '3603', '3642', '3657', '3786', '3592', '3732', '3759', '3658', '3785', '3819', '3802',
            '3599', '3795', '3553', '3830', '3781', '3752', '3523', '3653', '3796', '3554', '3811', '3761', '3526', '3584', '3806',
            '3719', '3519', '3508', '3713', '3645', '3649', '3583', '3790', '3637', '3633', '3674', '3528', '3650', '3550', '3598',
            '3702', '3565', '3530', '3703', '3635', '3723', '3726', '3597', '3737', '3515', '3782', '3542', '3532', '3521', '3704',
            '3600', '3682', '3564', '3573', '3705', '3775', '3656', '3636', '3655', '3725', '3809', '3545', '3503', '3706', '3707',
            '3679', '3768', '3631', '3789', '3641', '3567', '3586', '3518', '3591', '3534', '3720', '3770', '3745', '3666', '3791',
            '3643', '3769', '3626', '3539', '3686', '3709', '3646', '3634', '3659', '3793', '3632', '3710', '3814', '3783', '3522',
            '3740', '3601', '3615', '3555', '3569', '3738', '3575', '3825', '3773', '3574', '3708', '3779', '3831', '3524', '3748',
            '3513', '3762', '3803', '3801', '3739', '3722', '3596', '6540', '9405'
        ];

        const PAYLATER_DISABLED_MCCS = [
            '5960',
            '5961',
            '6010',
            '6011',
            '6050',
            '6051',
            '6535',
            '6211',
            '7273',
            '7297',
            '7800',
            '7801',
            '7802',
            '7995',
            '8398',
            '8641',
            '8651',
            '8661',
            '8675',
            '9405',
            '6012',
            '4829',
            '6534',
            '5816'
        ];

        const AMAZONPAY_DISABLED_MCCS = [
            '6051',
            '6012',
            '6010',
            '8931',
            '6050',
            '6211',
            '7361',
            '6538',
            '4829',
            '6011',
            '7012',
            '5971',
            '5993',
            '5999',
            '5972',
            '5169',
            '5933',
            '5921',
            '5681',
            '5932',
            '5931',
            '5813',
            '7801',
            '5816',
            '7994',
            '7829',
            '7311',
            '5964',
            '8041',
            '5969',
            '763',
            '7297',
            '7802',
            '7321',
            '7800',
            '5963',
            '5962',
            '7995',
            '5968',
            '5960',
            '7399',
            '5967',
            '5996',
            '5966',
            '7296',
            '6513',
            '8661',
            '8398',
            '7273',
            '8641',
            '4821',
            '8699',
            '8651',
            '743',
            '744',
            '5715',
            '5832',
            '5965',
            '7322',
        ];

        // map of category to auto prohibited methods i.e. methods which should not be enabled automatically by default
        // for the merchant belonging to that category, however can be enabled by admins
        // key is currently merchant category concatanated by category2, (in future business type etc can also come)
        // data is from this sheet - https://docs.google.com/spreadsheets/d/1eZMlh007Utp8JWGYGJ7Hk6zADSVlBWspHEzcGoKOydI/edit?usp=sharing
        // As of 19 Jan 2022 - https://docs.google.com/spreadsheets/d/1ZSJagVypcxKG_s1FLuY-D_tqyEb2U3hJ
        const CATEGORY_DEFAULT_PROHIBITED_METHODS_MAP = [
            'default' => [
                // Financial Services
                '6211'    => [
                    Category::MUTUAL_FUNDS => [
                        self::BLACKLISTED_METHODS   => [Entity::CREDIT_CARD, Entity::AMEX, Entity::EMI, Entity::CARDLESS_EMI, Entity::PREPAID_CARD, Entity::PAYLATER, Entity::PHONEPE, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS   =>  [],
                    ],
                    Category::SECURITIES => [
                        self::BLACKLISTED_METHODS   => [Entity::CREDIT_CARD, Entity::AMEX, Entity::EMI, Entity::CARDLESS_EMI, Entity::PREPAID_CARD, Entity::PAYLATER, Entity::PHONEPE, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS   =>  [],
                    ],
                ],
                '6051' => [
                    Category::CRYPTOCURRENCY => [
                        self::BLACKLISTED_METHODS =>  self::CATEGORY_DEPENDENT_METHODS, // cateogory is blacklisted
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => []
                    ],
                ],
                '6012' => [
                    Category::LENDING => [
                        self::BLACKLISTED_METHODS => [Entity::CREDIT_CARD, Entity::AMEX, Entity::EMI, Entity::CARDLESS_EMI, Entity::PREPAID_CARD, Entity::PAYLATER, Entity::PHONEPE, Entity::HDFC_DEBIT_EMI, Entity::AIRTELMONEY],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::PHONEPE],
                    ],
                    Category::OTHERS => [
                        self::BLACKLISTED_METHODS => [Entity::CREDIT_CARD, Entity::AMEX, Entity::EMI, Entity::CARDLESS_EMI, Entity::PREPAID_CARD, Entity::PAYLATER, Entity::PHONEPE, Entity::HDFC_DEBIT_EMI, Entity::AIRTELMONEY],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS   =>  [Entity::PHONEPE],
                    ],
                ],
                '6011' => [
                    Category::OTHERS => [
                        self::BLACKLISTED_METHODS => [Entity::AMEX, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ],
                ],

                '6300' => [
                    Category::INSURANCE => [
                        self::BLACKLISTED_METHODS => [Entity::AMEX, Entity::PHONEPE],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS   =>  [Entity::AMEX],
                    ]
                ],
                '6010'  =>  [
                    Category::FOREX =>  [
                        self::BLACKLISTED_METHODS => [Entity::CREDIT_CARD, Entity::AMEX, Entity::EMI, Entity::CARDLESS_EMI, Entity::PREPAID_CARD, Entity::PAYLATER, Entity::PHONEPE, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::PHONEPE],
                    ],
                ],
                '8931'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [Entity::AMEX, Entity::EMI, Entity::CARDLESS_EMI, Entity::PHONEPE, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ],
                ],
                '6050'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [Entity::AMEX, Entity::EMI, Entity::CARDLESS_EMI, Entity::PHONEPE, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::PHONEPE],
                    ]
                ],
                '7361'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => self::CATEGORY_DEPENDENT_METHODS, // cateogory is blacklisted
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [ENTITY::AMEX],
                    ]
                ],

                // Education
                '8220'  =>  [
                    Category::PVT_EDUCATION => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '8211'  =>  [
                    Category::PVT_EDUCATION => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '8299'  =>  [
                    Category::PVT_EDUCATION => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '8351'  =>  [
                    Category::OTHERS => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],

                // Healthcare
                '5912'  =>  [
                    Category::PHARMA    =>  [
                        // need to support phonepe.
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::EMI, Entity::CARDLESS_EMI, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5122'  =>  [
                    Category::PHARMA    =>  [
                        // need to support phonepe.
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '8062'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '8071'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '7298'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5499'  =>  [
                    Category::ECOMMERCE =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],

                // Utilities
                '4900'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '4814'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '4899'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '4816'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],
                // Government Bodies
                '9399'  =>  [
                    Category::GOVERNMENT    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],

                // Logistics
                '4214'  =>  [
                    Category::LOGISTICS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ],
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '4215'  =>  [
                    Category::LOGISTICS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '4225'  =>  [
                    Category::OTHERS       =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],

                // Tours and Travel
                '4511'  =>  [
                    Category::TRAVEL_AGENCY     =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],
                '7011'  =>  [
                    Category::HOSPITALITY       =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '4722'  =>  [
                    Category::TRAVEL_AGENCY     =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],

                // Transport
                '4121'  =>  [
                    Category::OTHERS  =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '4131'  =>  [
                    Category::OTHERS  =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '4112'  =>  [
                    Category::OTHERS  =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],

                // Ecommerce
                // 5399, ecommerce is in Healthcare as well, but have same value
                '5399'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::HDFC_DEBIT_EMI, Entity::AIRTELMONEY],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ],
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::EMI, Entity::CARDLESS_EMI, Entity::HDFC_DEBIT_EMI, Entity::AIRTELMONEY],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '7322'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [Entity::AIRTELMONEY],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],
                '5193'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5942'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5732'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '7311'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ],
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '7394'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5691'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5411'  =>  [
                    Category::GROCERY   => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::AIRTELMONEY],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5945'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5111'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5300'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5973'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5995'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5941'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5971'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::EMI, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5999'  => [
                    Category::OTHERS => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::PHONEPE],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5993'  => [
                    Category::OTHERS => [
                        self::BLACKLISTED_METHODS => self::CATEGORY_DEPENDENT_METHODS, // cateogory is blacklisted
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],


                // Food and Beverage
                '5811'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5818'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AIRTELMONEY],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],
                '5812'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5814'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5813'  =>  [
                    Category::OTHERS => [
                        self::BLACKLISTED_METHODS => self::CATEGORY_DEPENDENT_METHODS, // cateogory is blacklisted
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],

                    ]
                ],
                '7299'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],

                // IT and Software
                '5817'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '7372'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '7379'  => [
                    Category::OTHERS => [
                        self::BLACKLISTED_METHODS => self::CATEGORY_DEPENDENT_METHODS, // cateogory is blacklisted
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],

                // Games
                '5816'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::EMI, Entity::CARDLESS_EMI, Entity::FREECHARGE, Entity::HDFC_DEBIT_EMI, Entity::PAYLATER],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '7801'  =>  [
                    Category::OTHERS => [
                        self::BLACKLISTED_METHODS => self::CATEGORY_DEPENDENT_METHODS, // cateogory is blacklisted
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],

                // Media and Entertainment
                '5815'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '7832'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '7994'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '2741'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5994'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],

                // Services
                '7531'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '8911'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '8111'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '8999'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5511'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                // 7392, others is in 'IT and Software' as well, have same value
                '7392'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5964'  =>  [
                    Category::OTHERS => [
                        self::BLACKLISTED_METHODS => self::CATEGORY_DEPENDENT_METHODS, // cateogory is blacklisted
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX, Entity::PHONEPE],
                    ],
                ],

                // Housing and Real Estate
                '6513'  =>  [
                    Category::HOUSING   =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ],
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],
                '7349'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],

                // Not for profit
                '8398'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::EMI, Entity::CARDLESS_EMI, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '8661'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],

                // Social
                '7273'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],
                '8641'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '4821'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '4829'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],
                '8699'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5094'  =>  [
                    Category::ECOMMERCE    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::EMI, Entity::CARDLESS_EMI, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ],
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::CARDLESS_EMI, ENTITY::EMI, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],
                '5172'  =>  [
                    Category::ECOMMERCE    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5921'  =>  [
                    Category::ECOMMERCE    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5983'  =>  [
                    Category::ECOMMERCE    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5944'  =>  [
                    Category::ECOMMERCE    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::EMI, Entity::CARDLESS_EMI, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ],
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::CARDLESS_EMI, ENTITY::EMI, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],
                '7631'  =>  [
                    Category::ECOMMERCE    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::EMI, Entity::CARDLESS_EMI, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ],
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::CARDLESS_EMI, ENTITY::EMI, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],

                // if category, category2 does not matches with any of above, add methods which are to be blacklised for all category merchants in this
                Category::OTHERS  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
            ],

            // For VAS orgs, adding only category [others, other] as blacklisted methods are same for all categories
            OrgEntity::AXIS_ORG_ID => [
                Category::OTHERS  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [
                            Entity::EMI,
                            Entity::CARDLESS_EMI,
                            Entity::PREPAID_CARD,
                            Entity::PAYLATER,
                            Entity::AIRTELMONEY,
                            Entity::FREECHARGE,
                            Entity::JIOMONEY,
                            Entity::MOBIKWIK,
                            Entity::MPESA,
                            Entity::OLAMONEY,
                            Entity::PAYUMONEY,
                            Entity::PAYZAPP,
                            Entity::SBIBUDDY,
                            Entity::PHONEPE,
                            Entity::PAYTM,
                            Entity::PAYPAL,
                            Entity::PHONEPE_SWITCH,
                            Entity::BANK_TRANSFER,
                        ],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],
            ],
            OrgEntity::HDFC_ORG_ID => [
                Category::OTHERS  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [
                            Entity::EMI,
                            Entity::CARDLESS_EMI,
                            Entity::PREPAID_CARD,
                            Entity::PAYLATER,
                            Entity::AIRTELMONEY,
                            Entity::FREECHARGE,
                            Entity::JIOMONEY,
                            Entity::MOBIKWIK,
                            Entity::MPESA,
                            Entity::OLAMONEY,
                            Entity::PAYUMONEY,
                            Entity::PAYZAPP,
                            Entity::SBIBUDDY,
                            Entity::PHONEPE,
                            Entity::PAYTM,
                            Entity::PAYPAL,
                            Entity::PHONEPE_SWITCH,
                            Entity::BANK_TRANSFER,
                        ],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],
            ],
            OrgEntity::ICICI_ORG_ID => [
                Category::OTHERS  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS =>[
                            Entity::PAYLATER,
                            Entity::PREPAID_CARD,
                        ],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],
            ],

            OrgEntity::SIB_ORG_ID => [
                Category::OTHERS  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [
                            Entity::AMEX,
                            Entity::EMI,
                            Entity::PREPAID_CARD,
                            Entity::PAYLATER,
                            Entity::AIRTELMONEY,
                            Entity::FREECHARGE,
                            Entity::JIOMONEY,
                            Entity::MOBIKWIK,
                            Entity::MPESA,
                            Entity::OLAMONEY,
                            Entity::PAYUMONEY,
                            Entity::PAYZAPP,
                            Entity::SBIBUDDY,
                            Entity::PHONEPE_SWITCH,
                            Entity::BANK_TRANSFER,
                        ],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],
            ],

            OrgEntity::AXIS_EASYPAY_ORG_ID => [
                Category::OTHERS  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [
                            Entity::AMEX,
                            Entity::EMI,
                            Entity::PREPAID_CARD,
                            Entity::PAYLATER,
                            Entity::AIRTELMONEY,
                            Entity::FREECHARGE,
                            Entity::JIOMONEY,
                            Entity::MOBIKWIK,
                            Entity::MPESA,
                            Entity::OLAMONEY,
                            Entity::PAYUMONEY,
                            Entity::PAYZAPP,
                            Entity::SBIBUDDY,
                            Entity::PHONEPE_SWITCH,
                            Entity::BANK_TRANSFER,
                        ],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],
            ],

            OrgEntity::KOTAK_ORG_ID => [
                Category::OTHERS  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [
                            Entity::AMEX,
                            Entity::EMI,
                            Entity::PREPAID_CARD,
                            Entity::PAYLATER,
                            Entity::AIRTELMONEY,
                            Entity::FREECHARGE,
                            Entity::JIOMONEY,
                            Entity::MOBIKWIK,
                            Entity::MPESA,
                            Entity::OLAMONEY,
                            Entity::PAYUMONEY,
                            Entity::PAYZAPP,
                            Entity::SBIBUDDY,
                            Entity::PHONEPE_SWITCH,
                            Entity::BANK_TRANSFER,
                        ],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],
            ],
        ];

    // These are all the methods provided in the sheet https://docs.google.com/spreadsheets/d/1eZMlh007Utp8JWGYGJ7Hk6zADSVlBWspHEzcGoKOydI
    // This is needed because the sheet does not provide info about some other methods like nach, amazonpay etc. Letting default get picked for them
    const CATEGORY_DEPENDENT_METHODS = [
        Entity::UPI,
        Entity::CREDIT_CARD,
        Entity::DEBIT_CARD,
        Entity::AMEX,
        Entity::NETBANKING,
        Entity::EMI,
        Entity::CARDLESS_EMI, // should be enabled if EMI is enabled, have placed in all the same places where Entity::EMI is there in the above map
        Entity::PREPAID_CARD,
        Entity::PAYLATER,
        Entity::AIRTELMONEY,
        Entity::FREECHARGE,
        Entity::JIOMONEY,
        Entity::MOBIKWIK,
        Entity::MPESA,
        Entity::OLAMONEY,
        Entity::PAYUMONEY,
        Entity::PAYZAPP,
        Entity::SBIBUDDY,
        Entity::PHONEPE,
        Entity::PAYTM,
        Entity::PAYPAL,
        Entity::HDFC_DEBIT_EMI,
    ];

    const AUTO_DISABLED_METHODS = [
        Entity::PAYTM,
        Entity::PAYPAL,
        Entity::PHONEPE,
    ];

    // Refer: https://docs.google.com/spreadsheets/d/1SbG4Zi29QFBjwN8QjKS47V13DW6LFbDk0U-OXQ1FLQo
    // As of 19 Jan 2022 - https://docs.google.com/spreadsheets/d/1ZSJagVypcxKG_s1FLuY-D_tqyEb2U3hJ
    const ORG_WISE_METHODS_ENABLEMENT = [
        'default' => self::CATEGORY_DEPENDENT_METHODS,
        OrgEntity::AXIS_ORG_ID => [
            Entity::CREDIT_CARD,
            Entity::DEBIT_CARD,
            Entity::NETBANKING,
            Entity::UPI,
        ],
        // All except prepaid card and paylater
        OrgEntity::ICICI_ORG_ID => [
            Entity::CREDIT_CARD,
            Entity::DEBIT_CARD,
            Entity::AMEX,
            Entity::NETBANKING,
            Entity::UPI,
            Entity::EMI,
            Entity::CARDLESS_EMI, // should be enabled if EMI is enabled, have placed in all the same places where Entity::EMI is there in the above map
            Entity::AIRTELMONEY,
            Entity::FREECHARGE,
            Entity::JIOMONEY,
            Entity::MOBIKWIK,
            Entity::MPESA,
            Entity::OLAMONEY,
            Entity::PAYUMONEY,
            Entity::PAYZAPP,
            Entity::SBIBUDDY
        ],
        OrgEntity::HDFC_ORG_ID => [
            Entity::CREDIT_CARD,
            Entity::DEBIT_CARD,
            Entity::NETBANKING,
            Entity::UPI,
        ],
        OrgEntity::SIB_ORG_ID => [
            Entity::CREDIT_CARD,
            Entity::DEBIT_CARD,
            Entity::NETBANKING,
            Entity::UPI,
        ],
        OrgEntity::AXIS_EASYPAY_ORG_ID => [
            Entity::CREDIT_CARD,
            Entity::DEBIT_CARD,
            Entity::NETBANKING,
            Entity::UPI,
        ],
        OrgEntity::KOTAK_ORG_ID => [
            Entity::CREDIT_CARD,
            Entity::DEBIT_CARD,
            Entity::NETBANKING,
            Entity::UPI,
        ],
    ];

    public static function getDefaultMethodsFromMerchantCategories($category, $category2, string $orgId = 'default', $variantFlag = 'control')
    {
        $orgLevelMethodsMap = self::CATEGORY_DEFAULT_PROHIBITED_METHODS_MAP[$orgId] ?? self::CATEGORY_DEFAULT_PROHIBITED_METHODS_MAP['default'];

        if ($variantFlag === 'on')
        {
            $orgLevelMethodsMap = self::CATEGORY_DEFAULT_PROHIBITED_METHODS_MAP['default'];
        }

        $prohibitedMethods = $orgLevelMethodsMap[$category][$category2][self::BLACKLISTED_METHODS] ?? ($orgLevelMethodsMap[$category][Category::OTHERS][self::BLACKLISTED_METHODS] ?? $orgLevelMethodsMap[Category::OTHERS][Category::OTHERS][self::BLACKLISTED_METHODS]);

        $orgWiseMethodsForEnablement = self::ORG_WISE_METHODS_ENABLEMENT[$orgId] ?? self::ORG_WISE_METHODS_ENABLEMENT['default'];
        if ($variantFlag === 'on')
        {
            $orgWiseMethodsForEnablement = self::ORG_WISE_METHODS_ENABLEMENT['default'];
        }

        $methodData = [];

        if (in_array($category, self::AMEX_BLACKLISTED_MCCS) && !in_array(Entity::AMEX, $prohibitedMethods))
        {
            array_push($prohibitedMethods, Entity::AMEX);
        }

        if (in_array($category, self::PAYLATER_DISABLED_MCCS) && !in_array(Entity::PAYLATER, $prohibitedMethods))
        {
            array_push($prohibitedMethods, Entity::PAYLATER);
        }

        if (in_array($category, self::AMAZONPAY_DISABLED_MCCS) && !in_array(Entity::AMAZONPAY, $prohibitedMethods))
        {
            array_push($prohibitedMethods, Entity::AMAZONPAY);
        }

        foreach($orgWiseMethodsForEnablement as $method)
        {
            $methodData[$method] = true;
        }

        foreach($prohibitedMethods as $prohibitedMethod)
        {
            $methodData[$prohibitedMethod] = false;
        }

        foreach(self::AUTO_DISABLED_METHODS as $disabledMethod)
        {
            $methodData[$disabledMethod] = false;
        }

        return $methodData;
    }

    // This is being used in get_auto_disabled_methods/<merchant_id> which TS calls to validate if we should create instrument or not
    public static function getDefaultDisabledMethodsForInstrumentRequestFromMerchantCategories($category, $category2)
    {
        // if $category,$category2 matches in above map, pick that
        // else if $category is there but $category2 doesn't matches, pick map[$category]['others']
        // else it means nothing matches, then we pick map['others']['others']
        $orgLevelMethodsMap = self::CATEGORY_DEFAULT_PROHIBITED_METHODS_MAP['default'];

        $disabledMethods = $orgLevelMethodsMap[$category][$category2][self::BLACKLISTED_METHODS] ?? ($orgLevelMethodsMap[$category][Category::OTHERS][self::BLACKLISTED_METHODS] ?? $orgLevelMethodsMap[Category::OTHERS][Category::OTHERS][self::BLACKLISTED_METHODS]);

        $greyedMethods = $orgLevelMethodsMap[$category][$category2][self::GREYLISTED_METHODS] ?? ($orgLevelMethodsMap[$category][Category::OTHERS][self::GREYLISTED_METHODS] ?? $orgLevelMethodsMap[Category::OTHERS][Category::OTHERS][self::GREYLISTED_METHODS]) ?? [];

        $ignoreMethods = $orgLevelMethodsMap[$category][$category2][self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS] ?? ($orgLevelMethodsMap[$category][Category::OTHERS][self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS] ?? $orgLevelMethodsMap[Category::OTHERS][Category::OTHERS][self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS]);

        $disabledMethods = array_merge($disabledMethods, $greyedMethods);

        $disabledMethodsForInstrumentRequest = array_diff($disabledMethods, $ignoreMethods);

        if (in_array($category, self::AMEX_BLACKLISTED_MCCS) && !in_array(Entity::AMEX, $disabledMethodsForInstrumentRequest))
        {
            array_push($disabledMethodsForInstrumentRequest, Entity::AMEX);
        }

        if (in_array($category, self::PAYLATER_DISABLED_MCCS) && !in_array(Entity::PAYLATER, $disabledMethodsForInstrumentRequest))
        {
            array_push($disabledMethodsForInstrumentRequest, Entity::PAYLATER);
        }

        if (in_array($category, self::AMAZONPAY_DISABLED_MCCS) && !in_array(Entity::AMAZONPAY, $disabledMethodsForInstrumentRequest))
        {
            array_push($disabledMethodsForInstrumentRequest, Entity::AMAZONPAY);
        }

        return array_values($disabledMethodsForInstrumentRequest);
    }
}
