import {
  additionalDetailsMock,
  incompleteAdditionalDetailsAggregatorModelMock,
  incompleteAdditionalDetailsDirectModelMock,
} from 'apps/pos/e2e/suites/landing-sales-agent/mocks/additionalDetails';
import {
  agreementSigningMock,
  incompleteAgreementSigningMock,
} from 'apps/pos/e2e/suites/landing-sales-agent/mocks/agreementSigningMock';
import {
  deviceSelectionStepMock,
  incompleteDeviceSelectionStepMock,
} from 'apps/pos/e2e/suites/landing-sales-agent/mocks/deviceSelectionMock';
import {
  paymentMethodsAndServiceSelectionMock,
  incompletePricingStepAggregatorModelMock,
  incompletePricingStepDirectModelMock,
} from 'apps/pos/e2e/suites/landing-sales-agent/mocks/paymentMethodAndServiceMock';

export const salesOnboardedMerchantsMock = {
  __typename: 'SalesOnboardedMerchants',
  limit: 10,
  offset: 0,
  total: 50,
  hasMore: true,
  totalMerchantsOnboarded: 50,
  statusCounts: {
    activated: 0,
    rejected: 0,
    needsClarification: 0,
    kycQualifiedStb: 0,
    pending: 10,
    underReview: 0,
  },
  merchants: [
    {
      createdAt: '1725340683',
      merchantId: 'OsZjP3fjbIskDI',
      merchantName: '',
      merchantMobile: '',
      progressCompletion: '0',
      status: 'PENDING',
    },
    {
      createdAt: '1725340679',
      merchantId: 'OsZjKQdNlLffFv',
      merchantName: '',
      merchantMobile: '',
      progressCompletion: '0',
      status: 'PENDING',
    },
    {
      createdAt: '1725340674',
      merchantId: 'OsZjEdjUnxCuyu',
      merchantName: '',
      merchantMobile: '',
      progressCompletion: '0',
      status: 'PENDING',
    },
    {
      createdAt: '1725340668',
      merchantId: 'OsZj7mow3GcMTb',
      merchantName: '',
      merchantMobile: '',
      progressCompletion: '0',
      status: 'PENDING',
    },
    {
      createdAt: '1725340663',
      merchantId: 'OsZj3MVRlXmCmr',
      merchantName: '',
      merchantMobile: '',
      progressCompletion: '0',
      status: 'PENDING',
    },
    {
      createdAt: '1725340659',
      merchantId: 'OsZiyOjxxxRReP',
      merchantName: '',
      merchantMobile: '',
      progressCompletion: '0',
      status: 'PENDING',
    },
    {
      createdAt: '1725340653',
      merchantId: 'OsZiqv69rAX93d',
      merchantName: '',
      merchantMobile: '',
      progressCompletion: '0',
      status: 'PENDING',
    },
    {
      createdAt: '1725340649',
      merchantId: 'OsZimzLgKuksyl',
      merchantName: '',
      merchantMobile: '',
      progressCompletion: '0',
      status: 'PENDING',
    },
    {
      createdAt: '1725340646',
      merchantId: 'OsZiezQv15o1KL',
      merchantName: '',
      merchantMobile: '',
      progressCompletion: '0',
      status: 'PENDING',
    },
    {
      createdAt: '1725340633',
      merchantId: 'OsZiSroU8P8Ws0',
      merchantName: '',
      merchantMobile: '',
      progressCompletion: '0',
      status: 'PENDING',
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
    posActivationStatus: 'KYC_QUALIFIED_STB',
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
