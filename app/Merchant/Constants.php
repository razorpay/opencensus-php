<?php

namespace App\Merchant;

class Constants
{
    /**
     * partner config types
     */
    const COMMISSION = 'commission';
    const SUBVENTION = 'subvention';

    /**
     * partner config column which contains config type
     */
    const COMMISSION_MODEL = 'commission_model';
    const FETCH_PARTNER_ACTIVATION_FAILED   = 'fetch_partner_activation_failed';

    const AES_LOGIN_PATH   = 'twirp/rzp.adminexperienceservice.loginasmerchant.v1.AdminLoginMerchantServicePublic/Login';
    //
    // This is temporary code to get merchant waitlist
    // This code will be removed, in few weeks
    //
    const MERCHANT_WAITLIST = [
        "BNn4JCbqKeI9kn" => 349,
        "9H42ERznEMp12Z" => 384,
        "CBZDNKyXZT8BmY" => 494,
        "B0OszHtkeEm7kO" => 302,
        "7LlHblOnfxBBGw" => 306,
        "BuBa51Wx3rJilo" => 448,
        "DqOvP94pqXuMdM" => 497,
        "6EUGojcI1r3ffP" => 334,
        "CKmPC9WMUOsOfb" => 340,
        "BR4PFcuSg7WKDD" => 364,
        "AVoyUHjgk8LcHb" => 256,
        "7UNAQYEm0o4El3" => 460,
        "C7HszSJ4PA7Bqc" => 393,
        "BKu3fjEmc42HWR" => 464,
        "D3MN61BfV1zU2H" => 438,
        "Am4ZX0toiMEwYh" => 325,
        "4yTjAm6nyn36XR" => 338,
        "CvywGmaGdf0p57" => 420,
        "A864u38hEkMh2X" => 350,
        "5u8118uTQCCK9s" => 433,
        "Akw9dUHYo128Xm" => 326,
        "DJcvc7cTUfC0A1" => 267,
        "BiDwIRbXT8VSTN" => 276,
        "DBEgFo1H2yMl6K" => 356,
        "99aHOmqIkm1TT0" => 450,
        "9Dt0Yra48xlGCd" => 447,
        "D3QzOZZoCSm0nK" => 449,
        "DlqhhpJIHHpvnB" => 369,
        "DkvSsj2tXTUG4B" => 301,
        "DI9lmFgA87xS0a" => 321,
        "CxcJmCpNpZJaBs" => 341,
        "BrAwPaVlp4ZsPU" => 355,
        "CBH3UnLohs6Gt6" => 333,
        "ALkyE6yylg2AJa" => 358,
        "By58m7nSxH65Yl" => 348,
        "D3BeGvNh6KF6Za" => 408,
        "BzKfOfioxof2Ga" => 455,
        "CRnU0PuZqRHoso" => 405,
        "DotqxnLWpkuOtC" => 470,
        "AZiSXL9LftcRNT" => 285,
        "DUG1vfSUXH42k8" => 454,
        "7ie6pGJzBm3tIh" => 368,
        "DjgeJzSdAc4XJO" => 479,
        "9ALAZjRt2QW4cg" => 273,
        "DdLTxgrN7QVDDV" => 456,
        "903GKlsOZ3dP7D" => 397,
        "8qdbJwDBXUI0jf" => 437,
        "Daiom2SthZupUs" => 291,
        "9QM7izQTxCuqM7" => 472,
        "D0dpFqeyyjgGie" => 428,
        "87dR5R4X6d3RqL" => 459,
        "D48rWmdX5HZwDw" => 353,
        "Dm6h5CQNxQhrGL" => 410,
        "4qVAzJ06Dy9BnE" => 461,
        "Dl4oQLT8amVB4W" => 316,
        "6GGnVq4kb41zw8" => 309,
        "A98VSGYSUhF0F7" => 287,
        "Dm3BCb2BfhybKk" => 374,
        "C7GnThHUzoxttX" => 440,
        "CnbsBZJ4qN59Rw" => 423,
        "ArAEU68jVOPoxO" => 431,
        "4YbIWYiDX181Zd" => 463,
        "DlfE8ChF8p3Mad" => 345,
        "7Ew4H68LIu2u6r" => 452,
        "DlI7loYauUgFWL" => 361,
        "5mSD83iaEsBF02" => 421,
        "DoXEWcpeHk1FOu" => 499,
        "5zTlewWmlaDgeo" => 481,
        "8t5aCMhTp05OcG" => 263,
        "AXxixjwLdvLdKN" => 367,
        "Dli1O50WNhkLti" => 360,
        "DFImvEuhYcw4TL" => 307,
        "BCGU19hYCT0Ns5" => 402,
        "8pARQOHye2AvUX" => 424,
        "CfB5wqkt8IVeNS" => 416,
        "AgILZm5fJY69Eb" => 342,
        "BSQHLpf0eZOlC9" => 441,
        "DkrwlNxt6n2Gb2" => 294,
        "DKnTmNc6yefyka" => 498,
        "DHD1hx5ViNqnBd" => 265,
        "A8dV40nOqQ0IzW" => 284,
        "9ZnPau0gln8rIK" => 387,
        "DbM3OJbbCSQ7CQ" => 319,
        "Dg6joLuItvXGpL" => 310,
        "Cp2dcvC5WmKyXQ" => 328,
        "DaZsrusMT61I2g" => 436,
        "DbNPqYs1WtH7EL" => 451,
        "7t97YP5zftD7BR" => 380,
        "BiiZMuamarRAl6" => 419,
        "Boxb94IH9L8bvM" => 339,
        "BJ1FRHVLZE76f7" => 272,
        "DbOIWdGOhhbuIB" => 406,
        "DpPXPRqDFr2fmL" => 488,
        "DAaURRTgBecw92" => 305,
        "ALpxS6P6PwR57B" => 492,
        "CsPF5oYFo9WFrh" => 336,
        "CKiWlkgXmZzALW" => 279,
        "Cdf6kdyi4isWYh" => 425,
        "DqG0dbfsIBqCcm" => 495,
        "Agfls0JKRWCpkh" => 422,
        "DmHs2CElSCGZjB" => 390,
        "Bh70y5cA1PYffD" => 290,
        "BzLdelIJK3dl69" => 371,
        "Bhq2ADj9Btm7UK" => 303,
        "7YkYMOETDc3TMy" => 386,
        "CFcf28GiOmlZq0" => 320,
        "CTM0UTDjgrlEuD" => 357,
        "BGN3GZE98YDr2D" => 388,
        "Ci4vjhm3JHlKM7" => 443,
        "DGrvFNRQDyvTZP" => 323,
        "DS2irB55NSombj" => 275,
        "B93YL8ShNU72av" => 366,
        "CrEJZwYgLmQPno" => 389,
        "DVtQL89BVnVUms" => 379,
        "BKSLlRLaWpihP4" => 487,
        "64X0E18n9NErY9" => 335,
        "BZxYEZbPqLegay" => 458,
        "B80CDGFm8nEf3H" => 295,
        "BPhrIcdG4Chqot" => 477,
        "6WbmafD3Dd6yXE" => 296,
        "DlKx0lnuzvcNm4" => 346,
        "C7Ot74mVogHUSf" => 269,
        "Dku3nthEipbQqo" => 412,
        "DkAAzqJ2m4LNBx" => 262,
        "7Jk86UE598AODv" => 261,
        "AazmItn6QroGxc" => 278,
        "Cugj4D8SCsynCD" => 473,
        "BCbKzrEt97WADa" => 396,
        "AA3hyitEGctE3a" => 370,
        "Ck14KJ3lpgJ490" => 372,
        "DIyYkjhFg5pvkP" => 257,
        "B1UWcBnA28L9hr" => 274,
        "6XQ5C24DESBBwS" => 400,
        "8loXSgRv9WEC0Q" => 322,
        "BAfKHtVylI4cB0" => 362,
        "AoFISKXe1YgMPf" => 313,
        "DPTbCMXCXQ2OUA" => 268,
        "CAr623XTXjth71" => 413,
        "BW6CAKLhD3WXSd" => 392,
        "Dot0ix6HHADxrD" => 469,
        "6gp1ViIugz6BQb" => 314,
        "8JoEh4KNsH7fIi" => 324,
        "BGR3VPpR8ERdJt" => 299,
        "AbJHpZU3Mz3gIJ" => 462,
        "CIRm4r8Gthejot" => 282,
        "D5LzZQNJOvDt9n" => 474,
        "DoOzNcBueeHGvT" => 480,
        "AadSTP0VpNJTeU" => 332,
        "C4zNur5eJXhBec" => 292,
        "C6uIo6e6jUXpgL" => 315,
        "Dkr9ltEq9aACIY" => 377,
        "6d3OL3ipKLHWOc" => 471,
        "B95QcZQnhWsjUd" => 318,
        "CNUPItNc3NkWe8" => 411,
        "DeE4suCb7ibxr8" => 490,
        "BbefgRY5FBrhk9" => 491,
        "DgbCcjbAZeu2zY" => 435,
        "CSkHiRj083qhnP" => 381,
        "DjK8qmSz33imtY" => 258,
        "DkshjOibzY5Awu" => 378,
        "D8YF42PbFGrEsn" => 283,
        "DizK5qOMk7tEMq" => 352,
        "C6whQVUTfydWpb" => 399,
        "Cx87HpMY2U54mx" => 264,
        "AUkYQ4oAzArgHN" => 493,
        "Dkr7PFZd0vv5tw" => 293,
        "ChasI1gbo2iakm" => 429,
        "DpL2BYmBd424Ou" => 486,
        "DnMvOdvGoLYwBi" => 407,
        "3HtThbUPPtEE1v" => 347,
        "AbOVNqUlDSyD38" => 391,
        "AQLPVB2uUSyhju" => 496,
        "BSuMK9jcQb7Mvo" => 432,
        "AdClnaIlrz8JeY" => 255,
        "BEYT1o5w32QDqB" => 317,
        "CKdx4HZzAZuWYC" => 308,
        "Cgp0lgIOtWK4BP" => 280,
        "CWpsDXBwapXagp" => 300,
        "Dm9wEcSQzcXOdF" => 395,
        "D1VW49TvOQrYQm" => 484,
        "DPbnaAxrJgkKfJ" => 414,
        "D2YROmfbA57TUf" => 289,
        "DlHLo9gDUODOaO" => 363,
        "DoTTJ8v0qFBLCs" => 453,
        "DnP3eJrrV7PVAJ" => 409,
        "BgGTpQoqx6d8Dg" => 430,
        "DpIoCgYHhQYU85" => 485,
        "DIkXoesH5zkAQP" => 260,
        "CgtLpPjpmyg9ct" => 343,
        "DnIiZKog9kFMBq" => 475,
        "Bt360DVrq2Z20Y" => 297,
        "44guNk1cMMDOc4" => 439,
        "BcgcR3LZrvmG7o" => 329,
        "8wYfVLHf7hzwyg" => 304,
        "8DsJ1FO3rDE0Z5" => 259,
        "BGJpK51Ywqc9Dy" => 331,
        "Dle06yY4hsV04G" => 344,
        "BZUluDcf5qrdHm" => 385,
        "B55f9yMXCCPDgq" => 312,
        "8JxqB7ofZMDs6m" => 418,
        "DRcSDBidA6wmCH" => 351,
        "6z7QhyHNYb3JUF" => 383,
        "BaooZ6IcZ8Y16a" => 427,
        "DeNLatGJtUDXjw" => 468,
        "9eFLEbOMNB1w3d" => 271,
        "BcrQVwyaBiYNyB" => 398,
        "DKlHEfvle97XBb" => 354,
        "CtrzOPIeznrJHI" => 415,
        "DUbdiBh9YjJ15t" => 444,
        "C8ncZNPgjM084F" => 446,
        "5oZY1CkQB1HtVF" => 457,
        "BvrThlPIagLsgA" => 266,
        "Be35ykzf2ZKxz2" => 382,
        "4ZYTA4mtHw2Mrk" => 376,
        "BxjdGZClje81PC" => 277,
        "C9vCzdOmXpRnvb" => 401,
        "DLuOfa01z5KcPo" => 466,
        "Dm7Cs3U8sIsUwS" => 483,
        "DgCnWzD490TCvS" => 298,
        "Ddpu8HBSXhrkMJ" => 373,
        "DND9rTqPgt4nAH" => 426,
        "DkYpUYDatQtQCT" => 442,
        "DHLoUuh2447HXM" => 465,
        "C3DhiMDtvTlWoC" => 467,
        "7kWHFfLTbT3apR" => 489,
        "AYiEKGRv6QCpee" => 286,
        "Dlm5O67QXmuVwv" => 375,
        "Dn1Ge7bwbcOm7i" => 403,
        "DkYxPRPoSUpj2n" => 478,
        "DgAKEBG387v1ZL" => 434,
        "CZq2L9S3o8dQmS" => 337,
        "80pEQr3MKEFgHI" => 365,
        "BnnLc6tyJk8Z0k" => 281,
        "DlHUqGwApzttAD" => 327,
        "DFB7ubghQaekk3" => 330,
        "CI0lStdkudLa4S" => 394,
        "DRv0jiB0PN6oTW" => 288,
        "CuZiRjwPMx6OOF" => 359,
        "7EtAPe8oJ05si8" => 482,
        "Dnk4EniFZoh42u" => 417,
        "DPGlyUCWAduweG" => 311,
        "DkxvpGImKk7AUc" => 476,
        "BfzVQO7NVsj4VX" => 270,
        "6V30Sl8NoMEfno" => 445,
    ];

    const X_DEMO_MERCHANT_IDS = [
        'Hrw2ujXW6LGEk7', // Demo banking user merchant account - Beta
        'Hy5Vxj9TTVm4Oi'  // Demo banking user merchant account - Prod
    ];

    const OAUTH_ACTION_REDIRECT = 'REDIRECT';
    const OAUTH_ACTION_RENDER   = 'RENDER';

    const ACTIVATION_STATUS_ALLOWED_FOR_OAUTH_ACTION = [
        'activated',
        'under_review',
        'needs_clarification',
        'activated_mcc_pending',
        'instantly_activated'
    ];

    const OAUTH_SOURCE = 'oauth';
    const SOURCE       = 'source';

    const AMOUNT              = 'amount';
    const ACTION              = 'action';
    const SUCCESS             = 'success';
    const TXN_ID              = 'txnId';
    const APP_KEY             = 'appKey';
    const USERNAME            = 'username';
    const EXTERNAL_REF_NUMBER = 'externalRefNumber';
    const RAZORPAY_REFERENCE_ID = 'razorpayReferenceId';
    const RAZORPAY_MERCHANT_ID = 'razorpayMerchantId';
    const RECEIPT_IMAGE_TYPE = "receiptImageType";
    const MONOCHROME_ONE_BIT = "MONOCHROME_ONE_BIT";
    const EZETAP_RECEIPT_ENCODED_IMAGE = 'encodedImageString';
    const EZETAP_RECEIPT_ENDPOINT = '/api/2.0/receipt/image/fetch/omni';
    const EZETAP_FETCH_POS_DEVICES_ENDPOINT = '/api/2.0/pos/devices/';

    const EZETAP_FETCH_POS_DEVICE_SETTINGS_ENDPOINT = '/api/2.0/pos/devices/%s/settings';
    const RAZORPAY_RECEIPT_ENCODED_IMAGE_RESPONSE = 'receipt_encoded_image';
    const EZETAP_RECEIPT_ERROR = 'errorMessage';
    const HEADERS = [
        'Content-Type' => 'application/json'
    ];

    const MERCHANT_DETAIL_ADMIN_PAGE = 'https://admin-dashboard.razorpay.com/admin/merchants/%s/detail';

    const MERCHANT_BUSINESS_DETAIL = 'merchant_business_detail';
    const WEBSITE_DETAILS = 'website_details';
    const PHYSICAL_STORE = 'physical_store';
    const CUSTOM_CODE = 'custom_code';
    const RZP = 'rzp';
    const IS_SUB_MERCHANT = 'isSubMerchant';
    const PARTNER_TYPE = 'partner_type';
    const ACTIVATION_STATUS = 'activation_status';
    const RZP_ORG_ID = 'org_100000razorpay';
    const INDIA_COUNTRY_CODE = 'IN';
    const COUNTRY_CODE = 'country_code';
    const EASY_ONBOARDING = 'easy_onboarding';
    const ORG_ID = 'id';

    const PG_ONBOARDING_WORKFLOW_TYPE = 'pg_onboarding_workflow_type';
    const MODULAR_ONBOARDING = "MODULAR_ONBOARDING";

}
