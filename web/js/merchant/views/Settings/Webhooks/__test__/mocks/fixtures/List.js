export const initialState = {
  session: {
    user: {
      findTag: () => false,
      isOrgAllowedFunctionality: () => true,
      merchant: {
        id: 'K4NamuuWSvmcmX',
        entity: 'merchant',
        name: 'Roastee',
        email: null,
        activated: true,
        activated_at: 1660232973,
        live: true,
        hold_funds: true,
        pricing_plan_id: '1In3Yh5Mluj605',
        parent_id: null,
        website: null,
        category: '5814',
        category2: 'food_and_beverage',
        international: false,
        linked_account_kyc: false,
        has_key_access: false,
        fee_bearer: 'platform',
        fee_model: 'prepaid',
        refund_source: 'balance',
        billing_label: 'Roastee',
        receipt_email_enabled: true,
        receipt_email_trigger_event: 'authorized',
        transaction_report_email: [],
        invoice_label_field: null,
        channel: 'axis2',
        convert_currency: false,
        max_payment_amount: 50000000,
        max_international_payment_amount: 50000000,
        auto_refund_delay: null,
        auto_capture_late_auth: false,
        brand_color: null,
        handle: null,
        risk_rating: 3,
        risk_threshold: 8,
        partner_type: null,
        created_at: 1660232578,
        updated_at: 1660232973,
        suspended_at: null,
        archived_at: null,
        icon_url: null,
        logo_url: null,
        org_id: '100000razorpay',
        notes: [],
        whitelisted_ips_live: [],
        whitelisted_ips_test: [],
        whitelisted_domains: [],
        fee_credits_threshold: null,
        amount_credits_threshold: null,
        refund_credits_threshold: null,
        balance_threshold: null,
        display_name: null,
        activation_source: 'primary',
        business_banking: false,
        second_factor_auth: false,
        restricted: false,
        default_refund_speed: 'normal',
        partnership_url: null,
        external_id: null,
        product_international: '0000000000',
        signup_source: 'primary',
        purpose_code: null,
      },
    },
    org: {
      custom_code: 'rzp',
    },
    modeFormatted: 'Test',
  },
  webhooks: {
    count: 0,
    error: null,
    loadingAllWebhooks: false,
    loadingWebhook: false,
    stats: { loading: true, data: {}, error: false },
    webhooks: [],
  },
};

export const location = {
  hash: '',
  key: 'ulesr7',
  pathname: '/webhooks',
  search: '',
};

beforeAll(() => {
  window.rzpQ = {
    merchantActions: () => {
      return {
        initiated: jest.fn(),
      };
    },
    onbr: () => {
      return {
        initiated: jest.fn(),
      };
    },
  };

  window.rzpQ.component = jest.fn();
});
