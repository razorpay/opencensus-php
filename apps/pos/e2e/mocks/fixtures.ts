import {
  additionalDetailsMock,
  incompleteAdditionalDetailsAggregatorModelMock,
  incompleteAdditionalDetailsDirectModelMock,
} from 'apps/pos/e2e/mocks/additionalDetails';

import {
  agreementSigningMock,
  incompleteAgreementSigningMock,
} from 'apps/pos/e2e/mocks/agreementSigningMock';

import {
  deviceSelectionStepMock,
  incompleteDeviceSelectionStepMock,
} from 'apps/pos/e2e/mocks/deviceSelection';
import {
  paymentMethodsAndServiceSelectionMock,
  incompletePricingStepAggregatorModelMock,
  incompletePricingStepDirectModelMock,
} from 'apps/pos/e2e/mocks/paymentMethodAndService';
import {
  PendingAgreementSigningStep,
  AgreementOnlineCompleteStateMockSteps,
  AgreementOnlineMutationStateSteps,
} from './agreementSigning';
import { AgreementOfflineCompleteStateMockSteps } from './agreementSigning/agreementOfflineCompleteStateMock';
export const salesOnboardedMerchantsMock = {
  __typename: 'SalesOnboardedMerchants',
  limit: 10,
  offset: 0,
  total: 33,
  hasMore: true,
  totalMerchantsOnboarded: 33,
  statusCounts: {
    activated: 1,
    rejected: 2,
    needsClarification: 3,
    kycQualifiedStb: 4,
    pending: 10,
    underReview: 6,
    pricingNeedsClarification: 7,
  },
  merchants: [
    {
      createdAt: '1725340683',
      merchantId: 'OsZjP3fjbIskDI',
      merchantName: 'INFOPRIVATELIMITED',
      merchantMobile: '',
      progressCompletion: '0',
      status: 'PENDING',
      pricingNcStatus: '',
    },
    {
      createdAt: '1725340679',
      merchantId: 'OsZjKQdNlLffFv',
      merchantName: 'CHIZRINZ INFOWAY PRIVATE LIMITED',
      merchantMobile: '',
      progressCompletion: '0',
      status: 'PENDING',
      pricingNcStatus: '',
    },
    {
      createdAt: '1725340674',
      merchantId: 'OsZjEdjUnxCuyu',
      merchantName: 'Razorpay pvt',
      merchantMobile: '',
      progressCompletion: '0',
      status: 'PENDING',
      pricingNcStatus: '',
    },
    {
      createdAt: '1725340668',
      merchantId: 'OsZj7mow3GcMTb',
      merchantName: '',
      merchantMobile: '',
      progressCompletion: '0',
      status: 'UNDER_REVIEW',
      pricingNcStatus: 'pending_agent_action',
    },
    {
      createdAt: '1725340663',
      merchantId: 'OsZj3MVRlXmCmr',
      merchantName: '',
      merchantMobile: '',
      progressCompletion: '0',
      status: 'PENDING',
      pricingNcStatus: '',
    },
    {
      createdAt: '1725340659',
      merchantId: 'OsZiyOjxxxRReP',
      merchantName: '',
      merchantMobile: '',
      progressCompletion: '0',
      status: 'PENDING',
      pricingNcStatus: '',
    },
    {
      createdAt: '1725340653',
      merchantId: 'OsZiqv69rAX93d',
      merchantName: '',
      merchantMobile: '',
      progressCompletion: '0',
      status: 'PENDING',
      pricingNcStatus: '',
    },
    {
      createdAt: '1725340649',
      merchantId: 'OsZimzLgKuksyl',
      merchantName: '',
      merchantMobile: '',
      progressCompletion: '0',
      status: 'PENDING',
      pricingNcStatus: '',
    },
    {
      createdAt: '1725340646',
      merchantId: 'OsZiezQv15o1KL',
      merchantName: '',
      merchantMobile: '',
      progressCompletion: '0',
      status: 'PENDING',
      pricingNcStatus: '',
    },
    {
      createdAt: '1725340633',
      merchantId: 'OsZiSroU8P8Ws0',
      merchantName: '',
      merchantMobile: '',
      progressCompletion: '0',
      status: 'PENDING',
      pricingNcStatus: '',
    },
  ],
};

export const salesActivatedMerchantsMock = {
  __typename: 'SalesOnboardedMerchants',
  limit: 10,
  offset: 0,
  total: 33,
  hasMore: true,
  totalMerchantsOnboarded: 33,
  statusCounts: {
    activated: 2,
    rejected: 2,
    needsClarification: 3,
    kycQualifiedStb: 4,
    pending: 10,
    underReview: 6,
    pricingNeedsClarification: 7,
  },
  merchants: [
    {
      createdAt: '1725340683',
      merchantId: 'OsZjP3fjbIskDI',
      merchantName: '',
      merchantMobile: '',
      progressCompletion: '0',
      status: 'ACTIVATED',
      pricingNcStatus: '',
    },
    {
      createdAt: '1725340668',
      merchantId: 'OsZj7mow3GcMTb',
      merchantName: '',
      merchantMobile: '',
      progressCompletion: '0',
      status: 'ACTIVATED',
      pricingNcStatus: '',
    },
  ],
};

export const PastSevenDaysMerchantsMock = {
  __typename: 'SalesOnboardedMerchants',
  limit: 10,
  offset: 0,
  total: 33,
  hasMore: true,
  totalMerchantsOnboarded: 33,
  statusCounts: {
    activated: 1,
    rejected: 2,
    needsClarification: 3,
    kycQualifiedStb: 4,
    pending: 10,
    underReview: 6,
    pricingNeedsClarification: 7,
  },
  merchants: [
    {
      createdAt: '1725340683',
      merchantId: 'OsZjP3fjbIsxyz',
      merchantName: '',
      merchantMobile: '',
      progressCompletion: '0',
      status: 'ACTIVATED',
      pricingNcStatus: '',
    },
  ],
};

export const emptySalesOnboardedMerchantsMock = {
  __typename: 'SalesOnboardedMerchants',
  limit: 10,
  offset: 0,
  total: 0,
  hasMore: true,
  totalMerchantsOnboarded: 0,
  statusCounts: {
    activated: 0,
    rejected: 0,
    needsClarification: 0,
    kycQualifiedStb: 0,
    pending: 0,
    underReview: 0,
  },
  merchants: [],
};

export const merchantModularOnboardingDetailsAsSalesMock = {
  __typename: 'merchantModularOnboardingDetailsSuccessResponse',
  success: true,
  workflowData: {
    id: 'OsZirE2foWT2uY',
    progress: 100,
    status: 'completed',
    milestones: [
      {
        canSubmit: false,
        name: 'sales_milestone',
        status: 'executed',
        steps: [
          deviceSelectionStepMock,
          paymentMethodsAndServiceSelectionMock,
          additionalDetailsMock,
          agreementSigningMock,
        ],
      },
    ],
  },
  onboardingState: {
    milestones: [],
    modularComponents: [],
    steps: [],
  },
  countryCode: 'IN',
  onboardingType: 'DEFAULT_ONBOARDING',
  merchantType: 'Pos Payments',
};

export const MerchantByIdMock = {
  createdAt: '2024-09-03T05:17:16.000Z',
  id: 'OsZiezQv15o1KL',
  activation: {
    posActivationStatus: 'ACTIVATED',
    posActivationFlow: 'WHITELIST',
    status: null,
    isFormSubmitted: true,
    milestone: 'L2_COMPLETED',
    isPgosMerchant: true,
  },
  name: {
    display: null,
  },
  contactPerson: {
    name: {
      value: 'Danny',
    },
    email: {
      value: 'kakarla.vasanthi+03sep20242@razorpay.com',
    },
    phone: {
      value: {
        number: '+914677452266',
      },
    },
  },
  business: {
    type: {
      value: 'PROPRIETORSHIP',
    },
    address: {
      registered: {
        city: {
          value: 'Bengaluru',
        },
        country: {
          value: null,
        },
        district: {
          value: null,
        },
        line1: {
          value: 'hsr layout',
        },
        line2: {
          value: null,
        },
        state: {
          value: 'KA',
        },
        zipCode: {
          value: '560102',
        },
      },
      operation: {
        city: {
          value: 'Bengaluru',
        },
        country: {
          value: null,
        },
        district: {
          value: null,
        },
        line1: {
          value: 'hsr layout',
        },
        line2: {
          value: null,
        },
        state: {
          value: 'KA',
        },
        zipCode: {
          value: '560102',
        },
      },
    },
    paymentAcceptanceChannels: {
      websites: {
        urls: [
          {
            value: null,
          },
        ],
        accept: false,
        complianceConsent: null,
      },
      ios: {
        urls: [
          {
            value: '',
          },
        ],
        accept: false,
      },
      android: {
        urls: [
          {
            value: '',
          },
        ],
        accept: false,
      },
      offlineStore: {
        accept: true,
      },
      socialMedia: {
        accept: false,
        socialMediaUrls: [],
      },
      whatsappSmsEmail: {
        accept: false,
      },
      others: {
        accept: false,
        value: '',
      },
    },
  },
  document: {
    shopFront: {
      values: [
        {
          id: 'Ov5NLo8i6S5CcV',
          fileName: null,
        },
        {
          id: 'Ov5NiLZg0ZlNL4',
          fileName: null,
        },
      ],
    },
    shopInterior: {
      values: [
        {
          id: 'Ov5NuXfg9TPluv',
          fileName: null,
        },
        {
          id: 'Ov5OBxXaXPBoF4',
          fileName: null,
        },
      ],
    },
  },
};

export const incompleteSalesOnboardingDetailsMock = {
  __typename: 'merchantModularOnboardingDetailsSuccessResponse',
  success: true,
  workflowData: {
    id: 'PgAehad9Ffx4uj',
    progress: 38.541664,
    status: 'processing',
    milestones: [
      {
        canSubmit: false,
        name: 'sales_milestone',
        status: 'processing',
        steps: [
          incompleteDeviceSelectionStepMock,
          incompletePricingStepAggregatorModelMock,
          incompleteAdditionalDetailsAggregatorModelMock,
          incompleteAgreementSigningMock,
        ],
      },
    ],
  },
  onboardingState: {
    milestones: ['sales_milestone'],
    modularComponents: [],
    steps: [],
  },
  countryCode: 'IN',
  onboardingType: 'DEFAULT_ONBOARDING',
  merchantType: 'Pos Payments',
};

export const incompleteSalesOnboardingDetailsDirectModelMock = {
  __typename: 'merchantModularOnboardingDetailsSuccessResponse',
  success: true,
  workflowData: {
    id: 'PgAehad9Ffx4uj',
    progress: 38.541664,
    status: 'processing',
    milestones: [
      {
        canSubmit: false,
        name: 'sales_milestone',
        status: 'processing',
        steps: [
          incompleteDeviceSelectionStepMock,
          incompletePricingStepDirectModelMock,
          incompleteAdditionalDetailsDirectModelMock,
          incompleteAgreementSigningMock,
        ],
      },
    ],
  },
  onboardingState: {
    milestones: ['sales_milestone'],
    modularComponents: [],
    steps: [],
  },
  countryCode: 'IN',
  onboardingType: 'DEFAULT_ONBOARDING',
  merchantType: 'Pos Payments',
};

export const MerchantByIdStatusMock = {
  createdAt: '2025-01-15T06:03:49.000Z',
  id: 'Pjc5vONrnPt8WH',
  activation: {
    posActivationStatus: null,
    posActivationFlow: 'WHITELIST',
    status: null,
    isFormSubmitted: true,
    milestone: 'L1_COMPLETED',
    isPgosMerchant: true,
  },
  name: {
    display: null,
  },
  contactPerson: {
    name: {
      value: 'Test Pos',
    },
    email: {
      value: null,
    },
    phone: {
      value: {
        number: '+914325435323',
      },
    },
  },
  business: {
    type: {
      value: 'PROPRIETORSHIP',
    },
    address: {
      registered: {
        city: {
          value: 'Bengaluru',
        },
        country: {
          value: null,
        },
        district: {
          value: null,
        },
        line1: {
          value:
            '3rd, 4th and 5th Floor, Kothari Arena, 24, Hosur Rd, Chikku Lakshmaiah Layout, Koramangala',
        },
        line2: {
          value: null,
        },
        state: {
          value: 'KA',
        },
        zipCode: {
          value: '560029',
        },
      },
      operation: {
        city: {
          value: 'Bengaluru',
        },
        country: {
          value: null,
        },
        district: {
          value: null,
        },
        line1: {
          value:
            '3rd, 4th and 5th Floor, Kothari Arena, 24, Hosur Rd, Chikku Lakshmaiah Layout, Koramangala',
        },
        line2: {
          value: null,
        },
        state: {
          value: 'KA',
        },
        zipCode: {
          value: '560029',
        },
      },
    },
    paymentAcceptanceChannels: {
      websites: {
        urls: [
          {
            value: '',
          },
        ],
        accept: false,
        complianceConsent: null,
      },
      ios: {
        urls: [
          {
            value: '',
          },
        ],
        accept: false,
      },
      android: {
        urls: [
          {
            value: '',
          },
        ],
        accept: false,
      },
      offlineStore: {
        accept: true,
      },
      socialMedia: {
        accept: false,
        socialMediaUrls: [],
      },
      whatsappSmsEmail: {
        accept: false,
      },
      others: {
        accept: false,
        value: '',
      },
    },
  },
  document: {
    shopFront: {
      values: [
        {
          id: 'PjcdaPxucdbVDa',
          fileName: null,
        },
      ],
    },
    shopInterior: {
      values: [
        {
          id: 'PjcisRdWyuGe3L',
          fileName: null,
        },
      ],
    },
  },
};

export const MerchantVerifyMobileOtpResponse = {
  status_code: 200,
  success: true,
  data: {
    id: 'PpxO6gQxIaAU9N',
    name: '',
    email: null,
    contact_mobile: '3452234234',
    contact_mobile_verified: true,
    email_verified: false,
    second_factor_auth: false,
    second_factor_auth_enforced: false,
    second_factor_auth_setup: true,
    org_enforced_second_factor_auth: false,
    restricted: false,
    confirmed: false,
    account_locked: false,
    created_at: 1738306058,
    signup_via_email: 0,
    metadata: null,
    is_merchant_entities_empty: false,
    invitations: [],
    settings: {
      skip_contact_mobile_verify: '0',
    },
    merchants: [
      {
        id: 'PpxO6gR1OSkngz',
        name: '',
        billing_label: '',
        email: null,
        activated: false,
        activated_at: null,
        archived_at: null,
        suspended_at: null,
        has_key_access: false,
        logo_url: null,
        display_name: null,
        refund_source: 'balance',
        partner_type: null,
        restricted: false,
        created_at: 1738306058,
        updated_at: 1738306062,
        second_factor_auth: false,
        parent_id: null,
        parent_name: null,
        country_code: 'IN',
        role: 'owner',
        product: 'primary',
        banking_role: null,
        methods: {
          merchant_id: 'PpxO6gR1OSkngz',
          card: 1,
          card_networks: {},
          card_subtype: 3,
          netbanking: true,
          upi: false,
          emandate: false,
          nach: false,
          razorpaywallet: false,
          debit_emi_providers: {},
          paylater_providers: {},
          bajajpay: false,
          razorpay_giftcard: 0,
        },
      },
    ],
    signup_campaign: 'assisted_onboarding',
  },
};

// Fixtures for Agreement Signing Step
export const pendingStateAgreement = {
  __typename: 'merchantModularOnboardingDetailsSuccessResponse',
  success: true,
  workflowData: {
    id: 'OsZirE2foWT2uY',
    progress: 60,
    status: 'completed',
    milestones: [
      {
        canSubmit: false,
        name: 'sales_milestone',
        status: 'executed',
        steps: PendingAgreementSigningStep,
      },
    ],
  },
  onboardingState: {
    milestones: ['sales_milestone'],
    modularComponents: ['agreement_component', 'consent_component'],
    steps: ['agreement_step', 'consent_step'],
  },
  countryCode: 'IN',
  onboardingType: 'DEFAULT_ONBOARDING',
  merchantType: 'Pos Payments',
};

export const agreementOnlineCompleteStateMock = {
  __typename: 'merchantModularOnboardingDetailsSuccessResponse',
  success: true,
  workflowData: {
    id: 'OsZirE2foWT2uY',
    progress: 100,
    status: 'completed',
    milestones: [
      {
        canSubmit: false,
        name: 'sales_milestone',
        status: 'executed',
        steps: AgreementOnlineCompleteStateMockSteps,
      },
    ],
  },
  onboardingState: {
    milestones: [],
    modularComponents: [],
    steps: [],
  },
  countryCode: 'IN',
  onboardingType: 'DEFAULT_ONBOARDING',
  merchantType: 'Pos Payments',
};

export const agreementOnlineMutionStateMock = {
  __typename: 'merchantModularOnboardingDetailsSuccessResponse',
  success: true,
  workflowData: {
    id: 'PyFHEQSA635VPg',
    progress: 80,
    status: 'processing',
    milestones: [
      {
        canSubmit: false,
        name: 'sales_milestone',
        status: 'processing',
        steps: AgreementOnlineMutationStateSteps,
      },
    ],
  },
  onboardingState: {
    milestones: ['sales_milestone'],
    modularComponents: ['consent_component'],
    steps: ['consent_step'],
  },
  countryCode: 'IN',
  onboardingType: 'DEFAULT_ONBOARDING',
  merchantType: 'Pos Payments',
};

export const agreementOfflineCompleteStateMock = {
  __typename: 'merchantModularOnboardingDetailsSuccessResponse',
  success: true,
  workflowData: {
    id: 'OsZirE2foWT2uY',
    progress: 100,
    status: 'completed',
    milestones: [
      {
        canSubmit: false,
        name: 'sales_milestone',
        status: 'executed',
        steps: AgreementOfflineCompleteStateMockSteps,
      },
    ],
  },
};
