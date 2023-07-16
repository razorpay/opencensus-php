/*
    This file contains a map of Splitz A/B service experiment IDs for all environments

  The structures is : {
    [module_feature_name]: [LIST OF SPLITZ IDs]
  }

  Feature name can be anything which you can use to get list of experiment IDs in the codebase.
*/

export default {
  project_nitro: [
    // beta experiment ids
    'HVcUcOQ5hOIHxH', // Hyderabad Nitro
    'H7361l13HhrBgO', // Kolkata Nitro
    'H75RfvQFecKHsT', // Chennai Nitro
    'H75Qu5SInWp3SQ', // Jaipur Nitro
    'H75Q0JHjnUd5xs', // Surat Nitro
    'HF0Ml2IU6gH9rt', // Gandhinagar Nitro
    'HF0NZThSDtgNGB', // Vadodara Nitro
    'HF0OIJAqllZPRu', // Ahmedabad Nitro
    'HF0Ox4LNEgYHbV', // Bangalore Nitro
    'HZ76WCrNYDOyy9', // App switcher segment

    // prod experiment ids
    'GxtSf8y77iWw9e',
    'H6qGPCBPduY7Gl', // Kolkata Nitro
    'H6qJ2X77dqHG9I', // Chennai Nitro
    'H6qIJWTzrqt54X', // Jaipur Nitro
    'H6qHJJnYOtwfoc', // Surat Nitro
    'HExafLb492K7LU', // Gandhinagar Nitro
    'HExehMbAqYqlWF', // Vadodara Nitro
    'HExiHP6GBUEVcu', // Ahmedabad Nitro
    'HExnzHcFfimA6u', // Bangalore Nitro
    'HPc6GXsuboNXiS', // Delhi Nitro
    'HPc7OB0N3kh5BR', // Mumbai Nitro
    'HPc8DrLeWZc76W', // Pune Nitro
    'HPc9cMyPKKeAAX', // Gurgaon Nitro
    'HPcAKrn53GP41d', // Nagpur Nitro
    'HPcBJQw2E0BzpZ', // Kolhapur Nitro
    'Hrt8rX7v1tehY1', // Coimbatore Nitro
    'HzaFJoqZsRDu0c', // Others Nitro V1
    'HYlnMMJoE1RjFf', // App switcher segment
  ],
  announcement_text_experiment: [
    // beta experiment ids
    'H7S9OMKVGV53ZZ',

    // prod experiment ids
    'H7UUNpcljDyGAn',
  ],
  whats_new_text_experiment: [
    // beta experiment ids
    'H7S8wOn3THVwUa',
    // prod experiment ids
    'H7UYFAJIqhQB0X',
  ],
  partnership_nps: [
    // beta experiment ids
    'JLVDXyLyzWlTyI',

    // prod experiment ids
    'LEhcQ6vsmvThCM',
  ],
  partnership_for_razorpayx: [
    // beta experiment ids
    'HNHi7nieMQCDeh',
    'InVJYbqVV9YmDt', // ramp

    // prod experiment ids
    'HbwBfwuYGuM2Xx',
    'InWTItYNx0bVUa', // ramp
  ],
  submerchant_kyc_reseller: [
    // beta experiment ids
    'I8KTdSuwTZ8dYt',
    'JP4GAIs0SkxmC0', // ramp

    // prod experiment ids
    'I8PCawTxVfMpC5',
    'JP4J3uqyOJLK7D', // ramp
  ],
  whats_new_lazy_experiment: [
    // beta experiment ids
    'HPhSWihQaCQ2wJ',
    // prod experiment ids
    'HQqeKAnGRYPO4S',
  ],
  ultra_campaign_banner_experiment: [
    // beta experiment ids
    'HeLTdHr7f8YNOs',
    // prod experiment ids
    'Hf48AFfhZIa1cH',
  ],
  magic_prepay_cod: [
    //beta experiment id
    'LwajlLeGO2uDm7',

    //prod experiment id
    'LyzIbqJ3ETSBN9',
  ],

  magic_shopify_order_edit: [
    //beta experiment id
    'M9ivxmSdhzmmn2',

    //prod experiment id
    'M9iRe04lj27sdU',
  ],

  magic_rto_analytics_v3: [
    //beta experiment id
    'M3ltpBNar0aDxZ',

    //prod experiment id
    'M4Bi4l52fMeIrE',
  ],

  ultra_p2_cash_advance_banner_experiment: [
    // beta experiment ids
    'HzfUh5Z1nw1toU',
    // prod experiment ids
    'I0w5RCjEkcJJNM',
  ],
  nitro_corporate_cards: [
    // beta experiment ids
    'HVcXIX8S1cokqB', // Hyderabad

    // prod experiment ids
    'HVdaH5ipHEzj6x', // Hyderabad
    'HW1KsF0APg55vP', // Kolkata
    'HW1HhztGJNiaYA', // Chennai
    'HW1382Z5BUYrDV', // Surat
    'HW1IZP67ejfclG', // Jaipur
    'HW14FoCLRKdABS', // Gandhinagar
    'HW15QtkHIilowU', // Vadodara
    'HW16dcasfI78sW', // Ahemdabad
    'HW17UktB0YY7X7', // Bangalore
    'HW18PSOqi56mMN', // Delhi
    'HW19AUgSRz2frR', // Mumbai
    'HW19wimb0Bhu8N', // Pune
    'HW1Al5SNojQepQ', // Gurgaon
    'HW1Bb4TphEGzuU', // Nagpur
    'HW1CwITu2o0hdO', // Kolhapur
    'HrtIYOAiX2ipgI', // Coimbatore
    'HzaHdQAoyYFlFJ', // Others V1
    'HWP22TCyDAfcRG', // Test account prod
  ],

  cross_border_payments_campaign: [
    'IDVMGD3fth5Dkp', // Prod
    'I3f68nS3BRwvuF', // Beta
  ],
  catalyst_campaign_experiment: [
    'HYimXrRKRI0V7y', // Prod
    'HYiqGXEBQX3oo8', // Beta
  ],
  nitro_form_ab_experiment: [
    //prod
    'Hc1t1p85dJkRkp',

    // beta
    'Hb3p41OFV4RKzs',
  ],
  failure_analysis_text_exp: [
    // beta experiment ids
    'Hpid886p3sNk45',
    // prod experiment ids
    'I2OvzhA8n5WXzK',
  ],
  failure_analysis_payment_count_exp: [
    // beta experiment ids
    'I1CIVtHf67KXes',
    // prod experiment ids
    'I2OxeN2XVfI7iO',
  ],
  failure_analysis_rollout_exp: [
    // beta experiment ids
    'I1CKVFPEVTwGYe',
    // prod experiment ids
    'I2Oz7zUyWdMdju',
  ],
  failure_analysis_mtv_exp: [
    // beta experiment ids
    'ICQD4yGH1lPCdi',
    // prod experiment ids
    'ICUOfCJSNQQD15',
  ],

  gs_announcements_experiment: [
    // prod
    'HjnC4NvQhMA1u3',

    // beta
    'HjnDnqN0s8GXnV',
  ],

  gs_banners_experiment: [
    // prod
    'IUYQx3yRryEY64',

    // beta
    'IMixjTEYeDBCN9',
  ],

  gs_exclusive_offer_experiment: [
    // prod
    'IilXWVG87mw0tZ',

    // beta
    'IgmrwyclL61hVU',
  ],

  project_moonshine: [
    // prod
    'HmypdLPF5UusXz',
    'Hmz4DDcpwRNuXK',
    'Hmz5aOOpuLg9QG',

    // beta
    'HmF8BO9pvG303W',
  ],

  keystone_corporate_cards_experiment: [
    // prod
    'Hs6nlYI2qQCsrD',

    // beta
    'HpkTnWlc8nrYLo',
  ],
  keystone_cash_advance_experiment: [
    // prod
    'Hs6oZSmsa2rgey',

    // beta
    'HpkUfEDckfi6IS',
  ],

  independent_partner_kyc: [
    // beta experiment ids
    'HhTUjZcw4WsE2V',

    // prod experiment ids
    'KPe5dzVS6UCAiG',
  ],

  pure_platform_switch: [
    // beta experiment ids
    'LpfLoOMMA13K5O',

    // prod experiment ids
    'LpfIybGTZQHvWm',
  ],

  pp_zapier_announcement: [
    // beta
    'I2TybjEbDlkjqM',

    // prod
    'I2MzbJTVYRFauc',
  ],
  ai_sensy_banner: [
    // beta experiment ids
    'I2oTdpAJb8iQbE',

    // prod experiment ids
    'I2n2kDaDSfPfyx',
  ],
  zapier_integration: [
    // beta experiment ids
    'HpQN5BGbQ793ir',

    // prod experiment ids
    'HpmgNbhHWmh3BS',
  ],

  neostone_experiment: [
    // beta experiment ids
    'IIBFZGhWVgRPR7',

    // prod experiment ids,
    'IJsF9QOizHHLXC',
  ],

  connected_banking_icici_exp: [
    // beta experiment ids
    'IikYcTmmlnrKxi',

    // prod experiment ids,
    'IikVfIRle60YdX',
  ],

  show_razorpayx_widget_exp: [
    //beta experiment ids
    'J1MNmTAEwWWYX2',

    //prod experiment ids,
    'J299iRPbuBRWeb',
  ],

  catalyst_banner_fl_experiment: [
    // beta experiment ids
    'IScORMIXWZ7mKa',

    // prod experiment ids,
    'IStH22WVrNzGLq',
  ],
  catalyst_banner_ef_experiment: [
    // beta experiment ids
    'IScRMg9IVGaRXC',

    // prod experiment ids,
    'IStJ2bbF6rQbff',
  ],
  catalyst_banner_g_experiment: [
    // beta experiment ids
    'IScTk3pKyJE6rz',

    // prod experiment ids,
    'IStJqD0T3wzexZ',
  ],
  twoFA_mobile_signup_exp: [
    // beta experiment ids
    'IY6LESvNlVJsV9',
    // prod experiment ids
    'IZcVhevcxJxejr',
  ],
  ab_bannerCarousel_experiment: [
    // beta experiment ids
    'IgmrwyclL61hVU',

    // prod experiment ids,
    'IlBv2LyCzsyZqI',
  ],
  show_activation_form_full_view: [
    // beta experiment id
    'IuGpRXYJQvkELz',

    // prod experiment id
    'Iw2UPvr88nwZdA',
  ],
  show_L1_Form_on_login: [
    // beta experiment id
    'IuGq9FliGTmv88',

    // prod experiment id
    'Iw2TWM0yRhXrWI',
  ],

  ultra_campagin_capital_cards_only: [
    // prod experiment id
    'IzNDbBZJkmum9p',
  ],
  ultra_campagin_loc_only: [
    // prod experiment id
    'IzNBx4JjPFch8E',
  ],
  ultra_campagin_capital_cards_and_loc_both: [
    // prod experiment id
    'Izqj4llwT0wcZq',
  ],
  cross_sell_edu_exp: [
    // prod experiment id
    'J7KsRnA49jUwMh',
  ],
  cross_sell_other_exp: [
    // prod experiment id
    'J7Kt1EpzaLk9dQ',
  ],
  ultra_exclusiveoffer_card_a: [
    // prod
    'JIswqOkKfeIiRG',
  ],
  ultra_exclusiveoffer_card_b: [
    // prod
    'JIt7Ul9iIt6gUG',
  ],
  ultra_exclusiveoffer_loc_test: [
    // prod
    'JJCnl2fwyUMPvm',
  ],
  digilocker_aadhaar_ekyc: [
    //beta
    'JXRN0Fw8NN7qIG',
    //prod
    'JfNOewpSluhNIB',
  ],
  partnership_onboard_resellers: [
    // beta
    'JpWqxoqQa6aMOZ',

    // prod
    'JpbtDw1u1tmAH2',
  ],
  instant_activations_video_enabled: [
    // beta
    'JwQ4cpxqljzdAc',

    // prod
    'JwQ7TdSB3hZbcu',
  ],
  developer_console: [
    // beta experiment ids
    'IBpLCtGOiepyF3',

    // prod experiment ids
    'Jhk9i7jzGtL2OT',
  ],
  developer_console_webhooks_tab: [
    // beta experiment ids
    'KUpz5tL5Acjm1Z',

    // prod experiment ids
    'KaHGZHjePU5XLx',
  ],
  website_compliance_flow_exp: [
    // stage
    'K2hFpyVAkZomaH',

    // prod
    'K1WjJlJZLj3du1',
  ],
  website_compliance_modal_exp: [
    // stage
    'JylGhh8kdwbZqj',

    // prod
    'K32Ky74FqJfpT8',
  ],
  cash_advance_sidebar_position: [
    // beta
    'JyQuy8T813nUno',

    // prod
    'JyimKdpm7rIDOw',
  ],
  x_corporate_card_status_tracker: [
    // beta
    'KJhJGHB1XCV1zB',

    // prod
    'KK3XCFGhaiLw8J',
  ],
  invoice_currentFY: [
    //beta
    'K6HEHeGcb6D2IL',

    // prod
    'K6GhEu6Y4tmM0X',
  ],

  add_reply_migration: [
    // beta
    'KbvmLkY2BLyxki',
    // prod
    'Kd8w0C0X88RE2K',
  ],
  api_keys_revamp: [
    // beta experiment ids
    'JcBbXBKBU6cFdf',
    // prod experiment ids
    'JcXjyldJIfumzD',
  ],
  product_led_onboarding: [
    // beta experiment ids
    'KDU9Zk7cp7SGQy',
    // prod experiment ids
    'KE6QIX5uyJrKOq',
  ],
  left_nav_revamp: [
    // beta experiment ids
    'KpK9k86u44CxNg',
    // prod experiment ids
    'Kmd4oqDWnxSFe8',
  ],

  reports_revamp_recents: [
    // beta
    'LhJWXVuaVbbpnR',
    // prod
    'LhJcdQNbjDvQo5',
  ],

  merchant_reports_revamp: [
    // beta experiment ids
    'LEG6dZms3jSwvI',
    // prod experiment ids
    'LEIsWDFrD9MivV',
  ],

  partner_reports_revamp: [
    // beta experiment ids
    'Lby69yituOrSj2',
    // prod experiment ids
    'LbzMrW66OjfDsw',
  ],

  la_reports_revamp: [
    // beta experiment ids
    'Lby668Otnw4zQE',
    // prod experiment ids
    'LbzO5k3RZqiN4V',
  ],

  reports_schedules: [
    // beta experiment ids
    'LpcUyqou4GEsNz',
    // prod experiment ids
    'LoMqyEzW0E5wvE',
  ],

  universal_search_enabled: [
    // beta experiment ids
    'Lf6qHEprAH4UCm',
    // prod experiment ids
    'Lf6oo0XiYnyal4',
  ],

  settlement_v3_revamp: [
    // beta experiment ids
    'LaOawyVhmVnwnL',
    // prod experiment ids
    'LaOXeB0tkQtvIc',
  ],

  account_settings_revamp: [
    // beta experiment ids
    'L2orMNISKsZShh',
    // prod experiment ids
    'L2p1FFt2dWFOPd',
  ],
  bank_account_update_revamp: [
    // beta experiment ids
    'LEPe4jOAsG7UJ6',
    // prod experiment ids
    'LDbgxp2vBKgkRO',
  ],
  contact_details_revamp: [
    // beta experiment ids
    'LqorEX2cpIwzdP',
    // prod experiment ids
    'LqopA89qO1J3sF',
  ],
  user_name_update: [
    // beta experiment ids
    'M2UYltF2bOH663',
    // prod experiment ids
    'M2UVcW1ULZijIu',
  ],
  show_payroll_widget_exp: [
    //beta experiment ids
    'KmA4axC7yCGMZe',

    //prod experiment ids,
    'KmBlZ2iUatsoAB',
  ],
  international_enablement_revamp: [
    // beta experiment ids
    'LIZAA7NOWltkGx',
    // prod experiment ids
    'LIZ2hJsAfXmES6',
  ],
  show_affordability_widget_exp: [
    //beta experiment ids
    'Kp0QoSovVgTwkh',

    //prod experiment ids,
    'KpKKcFI3d29nAv',
  ],
  show_aff_widget_shopify_wait_list: [
    //beta experiment ids
    'L3aVOvsChLiuPm',

    //prod experiment ids,
    'L3abQRPY3iDdGf',
  ],
  show_aff_widget_wooc_wait_list: [
    //beta experiment ids
    'L4jTBkRsvWCFZJ',

    //prod experiment ids,
    'L4jWAHTviz9Tbu',
  ],
  show_segregated_credit_emi_methods: [
    //beta experiment ids
    'L7aXE1B8IJ8E3Q',

    //prod experiment ids,
    'L7uAKxhCggh6Na',
  ],
  get_ticket_migration: [
    // beta
    'KoBY1gZStTu3ic',
    // prod
    'KoBZA5MpwqlYfD',
  ],
  fetch_tickets_migration: [
    // beta
    'L0t2ATJrlpbk0f',
    // prod
    'L0zqe6gmKHp9xf',
  ],
  partnership_capital: [
    // stage
    'L01gPBm1R1OpJG',

    // prod
    'L0rynez0HhIXHb',
  ],
  revoke_application: [
    // stage
    'LlAzuMZf9ki7ao',

    // prod
    'Lla1DOCQiKDgM8',
  ],
  enable_easy_dashboard_nc: [
    // beta
    'L3AHj3UNHRhUXQ',
    // prod
    'L3DutoiWP8H6Zn',
  ],
  partnerships_combined_contact_filter: [
    // beta
    'M7GGcZW6UqtnPU',
    // prod
    'M7GKFjFdXexw0Q',
  ],
  partnerships_invite_flow: [
    // beta
    'LyzXsFptQEsxOe',
    // prod
    'LyzRiHbeLOmhM2',
  ],
  bundle_pricing: [
    // beta
    'LEgIE3J0zaDwz1',

    // prod
    'LJ70vtwLTYmo6I',
  ],
  partnership_for_phantom: [
    // stage
    'Kx02jtoxYeQxtk',
    // prod
    'KxZesk2B03J9ar',
  ],
  settlement_dashboard_visibility: [
    // stage
    'L55TvxKxYtYueE',
    //prod
    'L55JOo4EAhZC93',
  ],
  onboarding_ftux: [
    // beta experiment ids
    'L5840z96U9qWxT',
    // prod experiment ids
    'L580akR73oKnIr',
  ],
  payment_handle_onboarding: [
    // beta experiment ids
    'LO8m2hKgYegroc',
    // prod experiment ids
    'LOB7u4XmWc6PDd',
  ],
  pp_ecommerce: [
    // beta experiment ids
    'LHNwKf5m7I71uI',
    // prod experiment ids
    'LHOp2ELA9vyTW0',
  ],
  show_international_payments_button_ab: [
    // stage experiment ids
    'LGGuDDOTgodMkG',
    // prod experiment ids
    'LGeU9L1JfrImWM',
  ],
  COLLECT_MSME_CERTIFICATE_PROPRIETORSHIP: [
    // stage experiment ids
    'LcsX6qhx42WkE0',
    // prod experiment ids
    'LctS8LI0b15D6w',
  ],
  ecosystem_downtimes: [
    // stage experiment ids
    'LZJASxGazKW6oH',
    // prod experiment ids
    'LZi2wmeOZSCjtX',
  ],
  success_rate_admin: [
    // stage experiment ids
    'M4UtH6eWYiFiuA',
    // prod experiment ids
    'M4rtQihG8luU57',
  ],
  magic_cod_engine: [
    // stage experiment ids
    'Lep0tkId1tRQvA',
    // prod experiment ids
    'LfEZDFJoAnktiZ',
  ],
  magic_order_analytics: [
    'Llo1U1DSWpyLdp',
    //prod
    'LpVdcs5lXhlpdV',
  ],
  magic_order_analytics_cr: [
    'LwV4IlXplBdQ4g',
    //prod
    'LwV678pnF1EDwq',
  ],
  show_resume_onboarding: [
    // stage
    'LklQDqgOLE7NNi',
    // prod
    'LklMvyKSFn6ETp',
  ],
  show_terminal_status_banner: [
    // stage experiment ids
    'LfxVvG2agqRTco',
    // prod experiment ids
    'LfxXJXWfj87JAx',
  ],
  capital_isplusplus_splitz: [
    // stage
    'LvXOifgSOmvctO',
    //prod
    'LvPX2scr7KxnuZ',
  ],
  n_exponent_support: [
    // stage experiment
    'LszehANj9C6W0x',
    //prod experiment
    'Lspxsu4DTcptHe',
  ],
  sodexo_instrument: [
    // beta experiment ids
    'L66ZbgMCeW3l0N',
    // prod experiment ids
    'Lrb3FnfRl2FVmQ',
  ],
  checkout_analytics: [
    // beta experiment ids
    'M0Z76ari6rZz6Z',
    // prod experiment ids
    'M0ZCjxNJMZqa98',
  ],
  search_v2_phase_1: [
    // stage env id
    'M2B7MsOjDv2RCd',
    // production env id
    'M2B4FXLdVgc3wj',
  ],
  partnership_phantom_pure_platform: [
    // stage
    'LoGdTEB7Wo0UuW',
    // prod
    'LoGggyN9DVhO7A',
  ],
  recurring_card_multi_frequency: [
    //prod
    'MA6kWlvm3jWXqO',
    //beta
    'MARWDeA9lsGzbl',
  ],

  recurring_debit_pattern: [
    //prod
    'MBCrzgvu3t9plQ',
    //beta
    'MBCm7pcuzSKwfw',
  ],
};
