export const createPaymentLinkResponse = {
  status_code: 200,
  success: true,
  data: {
    accept_partial: false,
    amount: 100,
    amount_paid: 0,
    cancelled_at: 0,
    created_at: 1706463667,
    currency: 'INR',
    customer: {
      contact: '9999999999',
    },
    description: '',
    expire_by: 1706549819,
    expired_at: 0,
    first_min_partial_amount: 0,
    id: 'plink_NU7P1BwY6R8hfY',
    notes: null,
    notify: {
      email: false,
      sms: true,
      whatsapp: false,
    },
    payments: null,
    reference_id: '',
    reminder_enable: false,
    reminders: [],
    short_url: 'https://stage.rzp.io/i/QqpsPRHTl',
    status: 'created',
    updated_at: 1706463667,
    upi_link: false,
    user_id: 'FjCOc8wjLfxJOz',
    whatsapp_link: false,
  },
};

export const paymentLinkFailureResponse = {
  status_code: 200,
  success: false,
  errors: ['Something went wrong', 'Status Code: 404'],
};

export const merchantMethodsResponse = {
  status_code: 200,
  success: true,
  data: {
    cardless_emi: {
      walnut369: true,
      liquiloans: true,
      tvsc: true,
      cshe: true,
      hdfc: true,
      icic: true,
      idfb: true,
      krbe: true,
      earlysalary: true,
    },
    emi_options: {
      HDFC: [
        {
          duration: 9,
          interest: 0.12,
          subvention: 'customer',
          min_amount: 1250,
          merchant_payback: '0.05',
          processing_fee_plan: {
            type: 'fixed',
            amount: 19900,
          },
        },
        {
          duration: 3,
          interest: 0.12,
          subvention: 'customer',
          min_amount: 1000,
          merchant_payback: '0.02',
          processing_fee_plan: {
            type: 'fixed',
            amount: 19900,
          },
        },
      ],
      SCBL: [
        {
          duration: 3,
          interest: 0.68999999999999995,
          subvention: 'customer',
          min_amount: 1250,
          merchant_payback: '12.50',
        },
      ],
      BAJAJ: [
        {
          duration: 3,
          interest: 0.12,
          subvention: 'customer',
          min_amount: 1000,
          merchant_payback: '0.02',
        },
        {
          duration: 2,
          interest: 12.99,
          subvention: 'customer',
          min_amount: 100000,
          merchant_payback: '12.99',
        },
      ],
      KKBK: [
        {
          duration: 18,
          interest: 12.5,
          subvention: 'customer',
          min_amount: 300000,
          merchant_payback: '9.24',
          processing_fee_plan: {
            type: 'fixed',
            amount: 19900,
          },
        },
      ],
      AMEX: [
        {
          duration: 3,
          interest: 12.5,
          subvention: 'customer',
          min_amount: 1000,
          merchant_payback: '2.05',
          processing_fee_plan: {
            type: 'fixed',
            amount: 19900,
          },
        },
      ],
      SBIN: [
        {
          duration: 3,
          interest: 16.5,
          subvention: 'customer',
          min_amount: 100000,
          merchant_payback: '2.69',
        },
        {
          duration: 6,
          interest: 15,
          subvention: 'customer',
          min_amount: 100000,
          merchant_payback: '4.23',
        },
        {
          duration: 9,
          interest: 15,
          subvention: 'customer',
          min_amount: 100000,
          merchant_payback: '5.97',
        },
        {
          duration: 12,
          interest: 15,
          subvention: 'customer',
          min_amount: 100000,
          merchant_payback: '7.67',
        },
        {
          duration: 18,
          interest: 16,
          subvention: 'customer',
          min_amount: 100000,
          merchant_payback: '11.62',
        },
        {
          duration: 24,
          interest: 16,
          subvention: 'customer',
          min_amount: 100000,
          merchant_payback: '14.90',
        },
      ],
      BARB: [
        {
          duration: 12,
          interest: 5,
          subvention: 'customer',
          min_amount: 1223,
          merchant_payback: '12.23',
        },
        {
          duration: 9,
          interest: 12.5,
          subvention: 'customer',
          min_amount: 300000,
          merchant_payback: '12.50',
        },
      ],
      INDB: [
        {
          duration: 12,
          interest: 34.549999999999997,
          subvention: 'customer',
          min_amount: 3455,
          merchant_payback: '34.55',
          processing_fee_plan: {
            type: 'fixed',
            amount: 24900,
          },
        },
      ],
      ICIC: [
        {
          duration: 3,
          interest: 12.5,
          subvention: 'customer',
          min_amount: 300000,
          merchant_payback: '12.50',
          processing_fee_plan: {
            type: 'fixed',
            amount: 19900,
          },
        },
      ],
      HDFC_DC: [
        {
          duration: 9,
          interest: 0.12,
          subvention: 'customer',
          min_amount: 1250,
          merchant_payback: '0.05',
          processing_fee_plan: {
            type: 'fixed',
            amount: 19900,
          },
        },
        {
          duration: 3,
          interest: 12.5,
          subvention: 'customer',
          min_amount: 300000,
          merchant_payback: '2.05',
          processing_fee_plan: {
            type: 'fixed',
            amount: 19900,
          },
        },
      ],
      INDB_DC: [
        {
          duration: 3,
          interest: 15,
          subvention: 'customer',
          min_amount: 10000,
          merchant_payback: '2.45',
          processing_fee_plan: {
            type: 'fixed',
            amount: 19900,
          },
        },
      ],
      ICIC_DC: [
        {
          duration: 3,
          interest: 12.5,
          subvention: 'customer',
          min_amount: 1000,
          merchant_payback: '2.05',
        },
        {
          duration: 6,
          interest: 12.5,
          subvention: 'customer',
          min_amount: 300000,
          merchant_payback: '3.55',
        },
        {
          duration: 12,
          interest: 12.5,
          subvention: 'customer',
          min_amount: 300000,
          merchant_payback: '6.45',
        },
      ],
      onecard: [
        {
          duration: 3,
          interest: 0.12,
          subvention: 'customer',
          min_amount: 1000000,
          merchant_payback: '0.02',
        },
        {
          duration: 12,
          interest: 0.12,
          subvention: 'customer',
          min_amount: 1000000000,
          merchant_payback: '0.06',
        },
      ],
    },
    upi_config: {
      in_app: {
        payer_account_type: {
          credit_card: false,
          bank_account: true,
        },
      },
    },
    in_app: true,
    upi_intent: true,
  },
};

export const eligibilityResponse = {
  status_code: 200,
  success: true,
  data: {
    amount: '10000',
    customer: {
      contact: '+919999999999',
    },
    instruments: [
      {
        method: 'paylater',
        provider: 'getsimpl',
        eligibility_req_id: 'elig_NUNYUpupZ1xQM1',
        eligibility: {
          status: 'failed',
          error: {
            code: 'SERVER_ERROR',
            description:
              'We are facing some trouble completing your request at the moment. Please try again shortly.',
            source: 'server',
            step: 'inquiry',
            reason: 'server_error',
          },
        },
      },
      {
        method: 'cardless_emi',
        provider: 'icic',
        eligibility: {
          status: 'ineligible',
          error: {
            code: 'GATEWAY_ERROR',
            description:
              'The order amount is less than the minimum transaction amount for this provider. You may try with a higher order amount or try another payment option.',
            source: 'business',
            step: 'inquiry',
            reason: 'min_amt_required',
          },
        },
      },
      {
        method: 'cardless_emi',
        provider: 'idfb',
        eligibility: {
          status: 'ineligible',
          error: {
            code: 'GATEWAY_ERROR',
            description:
              'The order amount is less than the minimum transaction amount for this provider. You may try with a higher order amount or try another payment option.',
            source: 'business',
            step: 'inquiry',
            reason: 'min_amt_required',
          },
        },
      },
      {
        method: 'cardless_emi',
        provider: 'liquiloans',
        eligibility: {
          status: 'ineligible',
          error: {
            code: 'GATEWAY_ERROR',
            description:
              'The order amount is less than the minimum transaction amount for this provider. You may try with a higher order amount or try another payment option.',
            source: 'business',
            step: 'inquiry',
            reason: 'min_amt_required',
          },
        },
      },
      {
        method: 'cardless_emi',
        provider: 'walnut369',
        eligibility: {
          status: 'ineligible',
          error: {
            code: 'GATEWAY_ERROR',
            description:
              'The order amount is less than the minimum transaction amount for this provider. You may try with a higher order amount or try another payment option.',
            source: 'business',
            step: 'inquiry',
            reason: 'min_amt_required',
          },
        },
      },
      {
        method: 'cardless_emi',
        provider: 'hdfc',
        eligibility: {
          status: 'ineligible',
          error: {
            code: 'GATEWAY_ERROR',
            description:
              'The order amount is less than the minimum transaction amount for this provider. You may try with a higher order amount or try another payment option.',
            source: 'business',
            step: 'inquiry',
            reason: 'min_amt_required',
          },
        },
      },
      {
        method: 'emi',
        issuer: 'HDFC',
        type: 'debit',
        eligibility: {
          status: 'ineligible',
          error: {
            code: 'GATEWAY_ERROR',
            description:
              'The order amount is less than the minimum transaction amount for this provider. You may try with a higher order amount or try another payment option.',
            source: 'business',
            step: 'inquiry',
            reason: 'min_amt_required',
          },
        },
      },
    ],
  },
};

export const MockPaymentsMethods = [
  {
    method: 'cardless_emi',
    title: 'Liquiloans Cardless EMI',
    name: 'Pay using LIQUILOANS',
    provider: 'liquiloans',
    image: 'https://localhost:8080/public/dist/images/liquiloans.df4797acedf634bf.svg',
    notEligible: false,
  },
  {
    method: 'emi',
    title: 'UTIB Credit Card EMI',
    name: 'Pay using UTIB Credit Card',
    image: 'https://localhost:8080/public/dist/images/axis.7ff000fefeaeed82.png',
    provider: 'utib',
    type: 'credit',
    notEligible: false,
    emiPlan: [
      {
        emiPlan: '₹ 6,091.40 x 3 m',
        interest: '₹ 374.20 (12.5%)',
        totalPayable: '₹ 18,274.20',
      },
      {
        emiPlan: '₹ 3,115.20 x 6 m',
        interest: '₹ 791.20 (15%)',
        totalPayable: '₹ 18,691.20',
      },
      {
        emiPlan: '₹ 2,141.01 x 9 m',
        interest: '₹ 1,369.09 (18%)',
        totalPayable: '₹ 19,269.09',
      },
      {
        emiPlan: '₹ 1,666.73 x 12 m',
        interest: '₹ 2,100.76 (21%)',
        totalPayable: '₹ 20,000.76',
      },
    ],
  },
  {
    method: 'emi',
    title: 'CITI Credit Card EMI',
    name: 'Pay using CITI Credit Card',
    image: 'https://localhost:8080/public/dist/images/citi.f84348057af33456.png',
    provider: 'citi',
    type: 'credit',
    notEligible: false,
    emiPlan: [
      {
        emiPlan: '₹ 6,016.45 x 3 m',
        interest: '₹ 149.35 (5%)',
        totalPayable: '₹ 18,049.35',
      },
    ],
  },
  {
    method: 'emi',
    title: 'UTIB Credit Card EMI',
    name: 'Pay using UTIB Credit Card',
    image: 'https://localhost:8080/public/dist/images/axis.7ff000fefeaeed82.png',
    provider: 'utib',
    type: 'credit',
    notEligible: true,
    emiPlan: [],
  },
];
