import { rest, graphql } from 'msw';

const ActivationData = {
  contact_name: 'Rohan',
  contact_email: 'vivekshindhe96@gmail.com',
  contact_mobile: '8073945689',
  contact_landline: '',
  business_type: '5',
  business_name: 'Testing',
  business_description: null,
  business_dba: 'Social',
  business_website: 'https://www.google.com',
  business_international: true,
  business_paymentdetails: 'B2B',
  business_registered_address: '1st Floor, 22, SJR Cyber,\nLaskar Hosur Road, Adugodi',
  business_registered_address_l2: null,
  business_registered_country: null,
  business_registered_state: 'KA',
  business_registered_city: 'Bengaluru',
  business_registered_district: null,
  business_registered_pin: '560030',
  business_operation_address: '1st Floor, 22, SJR Cyber,\nLaskar Hosur Road, Adugodi',
  business_operation_address_l2: null,
  business_operation_country: null,
  business_operation_state: 'KA',
  business_operation_city: 'Bengaluru',
  business_operation_district: null,
  business_operation_pin: '560030',
  promoter_pan: 'CFZPM4099M',
  promoter_pan_name: 'TEST',
  business_doe: '',
  gstin: 'Qwoquiouytyuiip',
  p_gstin: '12ABABAB1234YYY',
  company_cin: 'K79807HN8900PGH809707',
  company_pan: 'AAAAA1234A',
  company_pan_name: '',
  business_category: 'social',
  business_subcategory: 'social_network',
  business_model: '',
  transaction_volume: 5,
  transaction_value: 0,
  website_about: 'https://www.razorpay.com/about',
  website_contact: 'https://www.razorpay.com/contact',
  website_privacy: 'https://www.razorpay.com/privacy',
  website_terms: 'https://www.razorpay.com/terms',
  website_refund: 'https://www.razorpay.com/cancellation',
  website_pricing: 'https://www.razorpay.com/pricing',
  website_login: '',
  steps_finished: '[]',
  activation_progress: 100,
  locked: true,
  activation_status: 'under_review',
  bank_details_verification_status: null,
  poa_verification_status: null,
  poi_verification_status: 'incorrect_details',
  clarification_mode: 'email',
  archived: 0,
  allowed_next_activation_statuses: ['needs_clarification', 'activated', 'rejected'],
  marketplace_activation_status: null,
  virtual_accounts_activation_status: null,
  subscriptions_activation_status: null,
  submitted: true,
  submitted_at: 1535546649,
  transaction_report_email: 'test@test.com',
  bank_account_number: '102938',
  bank_account_name: 'random name',
  bank_account_type: '',
  bank_branch: '',
  bank_branch_ifsc: 'HDFC0000007',
  bank_beneficiary_address1: '',
  bank_beneficiary_address2: '',
  bank_beneficiary_address3: '',
  bank_beneficiary_city: '',
  bank_beneficiary_state: '',
  bank_beneficiary_pin: '',
  role: '3',
  department: '5',
  created_at: 0,
  updated_at: 1601790865,
  activation_flow: 'greylist',
  international_activation_flow: 'greylist',
  live_transaction_done: 0,
  kyc_clarification_reasons: null,
  kyc_additional_details: null,
  additional_websites: null,
  estd_year: null,
  authorized_signatory_residential_address: null,
  authorized_signatory_dob: null,
  platform: null,
  fund_account_validation_id: null,
  gstin_verification_status: null,
  date_of_establishment: null,
  company_pan_verification_status: 'incorrect_details',
  cin_verification_status: null,
  documents: {
    aadhar_front: [
      {
        id: 'Eqej7mRSvihK7T',
        file_store_id: 'Eqej8sPR7fs8e1',
        merchant_id: '10000000000000',
      },
    ],
    aadhar_back: [
      {
        id: 'EqejEYqD991JzH',
        file_store_id: 'EqejFeB3Bn56cN',
        merchant_id: '10000000000000',
      },
    ],
    business_proof_url: [
      {
        id: 'EqejIW63xYj9qh',
        file_store_id: 'EqejJft3gKZpRu',
        merchant_id: '10000000000000',
      },
    ],
    business_pan_url: [
      {
        id: 'EqejN9CWyyRZdY',
        file_store_id: 'EqejOLnTRejEet',
        merchant_id: '10000000000000',
      },
    ],
    board_resolution: [
      {
        id: 'EqejVO2guRMyMt',
        file_store_id: 'EqejWSqKeNUx4I',
        merchant_id: '10000000000000',
      },
    ],
    memorandum_of_association: [
      {
        id: 'EqejYuq7tnqgb4',
        file_store_id: 'Eqeja5L2lRQVyR',
        merchant_id: '10000000000000',
      },
    ],
    article_of_association: [
      {
        id: 'EqejkipD9WCxXq',
        file_store_id: 'Eqejlo3YeRwfVz',
        merchant_id: '10000000000000',
      },
    ],
  },
  verification: {
    status: 'disabled',
    disabled_reason: 'required_fields',
    required_fields: [],
    optional_fields: [],
    activation_progress: 100,
  },
  can_submit: false,
  activated: 0,
  live: false,
  international: true,
  merchant: {
    id: '10000000000000',
    entity: 'merchant',
    name: 'Testing',
    email: 'test@razorpay.com',
    activated: false,
    activated_at: null,
    live: false,
    hold_funds: true,
    pricing_plan_id: 'DRSDeazCXt6A2n',
    parent_id: null,
    website: '',
    category: '8641',
    category2: 'others',
    international: true,
    linked_account_kyc: false,
    has_key_access: true,
    fee_bearer: 'platform',
    fee_model: 'prepaid',
    refund_source: 'balance',
    billing_label: null,
    receipt_email_enabled: false,
    receipt_email_trigger_event: 'authorized',
    transaction_report_email: ['qa+dashboard@razorpay.com'],
    invoice_label_field: 'business_dba',
    channel: 'axis2',
    convert_currency: false,
    max_payment_amount: 100000000000,
    auto_refund_delay: 432000,
    auto_capture_late_auth: false,
    brand_color: '#0E817D',
    handle: 'TEST',
    risk_rating: 3,
    risk_threshold: 5,
    partner_type: 'aggregator',
    created_at: 1423573020,
    updated_at: 1599645676,
    suspended_at: null,
    archived_at: null,
    icon_url: null,
    logo_url: 'https://cdn.razorpay.com/logos/DMmeJ5GKXwq5cb_original.png',
    org_id: '100000razorpay',
    notes: [],
    whitelisted_ips_live: null,
    whitelisted_ips_test: [],
    whitelisted_domains: [],
    fee_credits_threshold: null,
    display_name: 'RZP Test',
    activation_source: null,
    business_banking: true,
    second_factor_auth: false,
    restricted: false,
    default_refund_speed: 'optimum',
    partnership_url: null,
    external_id: null,
    product_international: '1111000000',
    signup_source: null,
  },
  credit_balance: [],
};

export const handlers = [
  // Handles a "Login" mutation
  graphql.mutation('Login', (req, res, ctx) => {
    const { username } = req.variables;
    sessionStorage.setItem('is-authenticated', username);

    return res(
      ctx.data({
        login: {
          username,
        },
      }),
    );
  }),

  // Handles a "GetUserInfo" query
  graphql.query('GetUserInfo', (req, res, ctx) => {
    const authenticatedUser = sessionStorage.getItem('is-authenticated');

    if (!authenticatedUser) {
      // When not authenticated, respond with an error
      return res(
        ctx.errors([
          {
            message: 'Not authenticated',
            errorType: 'AuthenticationError',
          },
        ]),
      );
    }

    // When authenticated, respond with a query payload
    return res(
      ctx.data({
        user: {
          username: authenticatedUser,
          firstName: 'John',
        },
      }),
    );
  }),

  rest.get('http://localhost:6006/activation', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.delay(500),
      ctx.json({
        status_code: 200,
        success: true,
        data: ActivationData,
      }),
    );
  }),

  rest.post('http://localhost:6006/activation', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.delay(500),
      ctx.json({
        status_code: 200,
        data: {
          ...ActivationData,
          contact_name: 'updated',
        },
      }),
    );
  }),
];
