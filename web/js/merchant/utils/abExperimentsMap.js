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
  partnership_for_razorpayx: [
    // beta experiment ids
    'HNHi7nieMQCDeh',

    // prod experiment ids
    'HbwBfwuYGuM2Xx',
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

  growth_service_rollout_experiment: [
    // prod
    'HjnC4NvQhMA1u3',

    // beta
    'HjnDnqN0s8GXnV',
    'HjnHKyoeyjvoRJ',
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
    'HmbfCtIa68aQcC',
  ],
  edx_app_integration_banner: [
    // beta experiment ids
    'HpQN5BGbQ793ir',

    // prod experiment ids
    'HpmhmqFPLZ3TTw',
  ],
};
