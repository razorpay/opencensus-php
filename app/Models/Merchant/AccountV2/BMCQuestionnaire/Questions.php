<?php

namespace RZP\Models\Merchant\AccountV2\BMCQuestionnaire;

class Questions
{

    const QUESTION_2      = "question_2";
    const QUESTION_4      = "question_4";
    const QUESTION_24     = "question_24";
    const QUESTION_11     = "question_11";
    const QUESTION_11_4_1 = "question_11_4_1";
    const QUESTION_43     = "question_43";
    const QUESTION_34     = "question_34";

    const OPTION_2_11     = "option_2_11";
    const OPTION_2_12     = "option_2_12";
    const OPTION_2_13     = "option_2_13";
    const OPTION_2_14     = "option_2_14";
    const OPTION_2_15     = "option_2_15";
    const OPTION_2_5      = "option_2_5";
    const OPTION_4_1      = "option_4_1";
    const OPTION_4_2      = "option_4_2";
    const OPTION_11_1     = "option_11_1";
    const OPTION_11_2     = "option_11_2";
    const OPTION_11_3     = "option_11_3";
    const OPTION_11_4     = "option_11_4";
    const OPTION_11_4_1_1 = "option_11_4_1_1";
    const OPTION_11_4_1_2 = "option_11_4_1_2";
    const OPTION_24_1     = "option_24_1";
    const OPTION_24_2     = "option_24_2";
    const OPTION_43_1     = "option_43_1";
    const OPTION_43_2     = "option_43_2";

    const VALUE    = "value";
    const TYPE     = "type";
    const OPTIONS  = "options";
    const NEXT     = "next";
    const PREVIOUS = "previous";
    const VALIDATIONS = "validations";
    const ITEM_VALIDATIONS = "item_validations";

    const TYPE_STRING = "string";
    const TYPE_ARRAY  = "array";

    const API_KEY = "api_key";

    const AVERAGE_DELIVERY_TIME            = "average_delivery_time";
    const PRODUCE_GROCERY_OR_FOOD_PRODUCTS = "produce_grocery_or_food_products";
    const ACCOUNTING_BUSINESS_MODEL_TYPE   = "accounting_business_model_type";
    const RESTAURANT_BUSINESS_MODEL_TYPE   = "restaurant_business_model_type";
    const ALCOHOLIC_BEVERAGES_DELIVERY     = "alcoholic_beverages_delivery";
    const HOSPITAL_SETUP_TYPE              = "hospital_setup_type";
    const SECOND_HAND_PRODUCTS_SOLD        = "second_hand_products_sold";

    //const PGOS_KEYS_TO_API_KEYS = [
    //    self::QUESTION_2      => "average_delivery_time",
    //    self::QUESTION_4      => "produce_grocery_or_food_products",
    //    self::QUESTION_24     => "accounting_business_model_type",
    //    self::QUESTION_11     => "restaurant_business_model_type",
    //    self::QUESTION_11_4_1 => "alcoholic_beverages_delivery",
    //    self::QUESTION_43     => "hospital_setup_type",
    //];

    const API_KEYS_TO_QUESTION_KEYS = [
        self::AVERAGE_DELIVERY_TIME            => self::QUESTION_2,
        self::PRODUCE_GROCERY_OR_FOOD_PRODUCTS => self::QUESTION_4,
        self::ACCOUNTING_BUSINESS_MODEL_TYPE   => self::QUESTION_24,
        self::RESTAURANT_BUSINESS_MODEL_TYPE   => self::QUESTION_11,
        self::ALCOHOLIC_BEVERAGES_DELIVERY     => self::QUESTION_11_4_1,
        self::HOSPITAL_SETUP_TYPE              => self::QUESTION_43,
        self::SECOND_HAND_PRODUCTS_SOLD        => self::QUESTION_34,
    ];

    const QUESTIONS_OPTIONS_MAP = [
        self::QUESTION_2      => [
            self::API_KEY => self::AVERAGE_DELIVERY_TIME,
            self::TYPE    => self::TYPE_STRING,
            self::OPTIONS => [
                self::OPTION_2_11 => [
                    self::VALUE => "0_7_days",
                ],
                self::OPTION_2_12 => [
                    self::VALUE => "8_14_days"
                ],
                self::OPTION_2_13 => [
                    self::VALUE => "15_21_days"
                ],
                self::OPTION_2_14 => [
                    self::VALUE => "22_35_days"
                ],
                self::OPTION_2_15 => [
                    self::VALUE => "above_35_days"
                ],
                self::OPTION_2_5  => [
                    self::VALUE => "not_applicable"
                ],
            ],
            self::VALIDATIONS => [
                "filled",
                self::TYPE_STRING,
                "in:0_7_days,8_14_days,15_21_days,22_35_days,above_35_days,not_applicable"
            ]
        ],
        self::QUESTION_4      => [
            self::API_KEY => self::PRODUCE_GROCERY_OR_FOOD_PRODUCTS,
            self::TYPE    => self::TYPE_STRING,
            self::OPTIONS => [
                self::OPTION_4_1 => [
                    self::VALUE => "yes",
                ],
                self::OPTION_4_2 => [
                    self::VALUE => "no"
                ],
            ],
            self::VALIDATIONS => [
                "filled",
                self::TYPE_STRING,
                "in:yes,no"
            ]
        ],
        self::QUESTION_11     => [
            self::API_KEY => self::RESTAURANT_BUSINESS_MODEL_TYPE,
            self::TYPE    => self::TYPE_ARRAY,
            self::OPTIONS => [
                self::OPTION_11_1 => [
                    self::VALUE => "restaurant_or_online_food_delivery"
                ],
                self::OPTION_11_2 => [
                    self::VALUE => "catering_service"
                ],
                self::OPTION_11_3 => [
                    self::VALUE => "food_court_with_multiple_brands"
                ],
                self::OPTION_11_4 => [
                    self::VALUE => "sell_alcoholic_beverages",
                    self::NEXT  => [
                        self::QUESTION_11_4_1
                    ],
                ],
            ],
            self::VALIDATIONS => [
                "filled",
                self::TYPE_ARRAY,
                "min:1"
            ],
            self::ITEM_VALIDATIONS => [
                "filled",
                self::TYPE_STRING,
                "in:restaurant_or_online_food_delivery,catering_service,food_court_with_multiple_brands,sell_alcoholic_beverages"
            ]
        ],
        self::QUESTION_11_4_1 => [
            self::API_KEY  => self::ALCOHOLIC_BEVERAGES_DELIVERY,
            self::TYPE     => self::TYPE_STRING,
            self::OPTIONS  => [
                self::OPTION_11_4_1_1 => [
                    self::VALUE => "yes",
                ],
                self::OPTION_11_4_1_2 => [
                    self::VALUE => "no",
                ]
            ],
            self::PREVIOUS => self::QUESTION_11,
            self::VALIDATIONS => [
                "filled",
                self::TYPE_STRING,
                "in:yes,no"
            ]
        ],
        self::QUESTION_24     => [
            self::API_KEY => self::ACCOUNTING_BUSINESS_MODEL_TYPE,
            self::VALUE   => self::TYPE_STRING,
            self::OPTIONS => [
                self::OPTION_24_1 => [
                    self::VALUE => "ca_firm",
                ],
                self::OPTION_24_2 => [
                    self::VALUE => "not_ca_firm"
                ],
            ],
            self::VALIDATIONS => [
                "filled",
                self::TYPE_STRING,
                "in:ca_firm,not_ca_firm"
            ]
        ],
        self::QUESTION_43     => [
            self::API_KEY => self::HOSPITAL_SETUP_TYPE,
            self::TYPE    => self::TYPE_STRING,
            self::OPTIONS => [
                self::OPTION_43_1 => [
                    self::VALUE => "consulting_only",
                ],
                self::OPTION_43_2 => [
                    self::VALUE => "consulting_with_hospitalization_or_surgery"
                ],
            ],
            self::VALIDATIONS => [
                "filled",
                self::TYPE_STRING,
                "in:consulting_only,consulting_with_hospitalization_or_surgery"
            ]
        ],
        self::QUESTION_34     => [
            self::API_KEY => self::SECOND_HAND_PRODUCTS_SOLD,
            self::TYPE    => self::TYPE_STRING,
            self::VALIDATIONS => [
                "filled",
                self::TYPE_STRING,
                "min:5",
                "max:250"
            ]
        ],
    ];
}



