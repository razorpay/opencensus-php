export const INIT_STATE = {
  magicPrepayCODConfigs: {
    isLoading: false,
    isPrepayCODEnabled: false,
    prepayCODConfigs: {},
    error: null,
  },
};

export const ERROR_STATE = {
  magicPrepayCODConfigs: {
    isLoading: false,
    isPrepayCODEnabled: false,
    prepayCODConfigs: { enabled: false, configs: { risk_category: ['high'] } },
    error: 'some error occured',
  },
};

export const MOCKED_UNSAVE_CONFIGS_PAYLOAD = {
  success: true,
  status_code: 200,
  data: {
    enabled: false,
    configs: null,
  },
};

export const MOCKED_SAVED_CONFIGS_PAYLOAD = {
  success: true,
  status_code: 200,
  data: {
    enabled: true,
    configs: {
      discount: {
        type: 'percentage',
        discount_percentage: 10,
        max_discount: 10000,
        minimum_order_value: 20000,
      },
      risk_category: ['high'],
      communication: {
        expire_seconds: 3000,
        methods: ['whatsapp'],
      },
    },
  },
};

export const MOCKED_ERROR_FETCH_PAYLOAD = {
  success: true,
  status_code: 400,
  errors: ['test error'],
};

export const UTILS_DUMMY = [
  {
    configs: {
      risk_category: ['high', 'medium', 'low'],
      discount: {
        type: 'percentage',
        max_discount: 10000,
        minimum_order_value: 20000,
        discount_percentage: 10,
      },
      communication: {
        expire_seconds: 7200,
        methods: ['whatsapp'],
      },
    },
    riskResponse: 'All COD orders',
    discountResponse: '₹10% off upto ₹100 on minimum order of ₹200',
    expireTimeResponse: '2 hours',
    conversionPlatformResponse: 'WhatsApp message',
  },
  {
    configs: {
      risk_category: ['high', 'medium'],
      discount: {
        type: 'flat',
        max_discount: 10000,
        minimum_order_value: 20000,
        discount_percentage: 0,
      },
      communication: {
        expire_seconds: 9000,
        methods: ['whatsapp'],
      },
    },
    riskResponse: 'High and Medium RTO risk COD orders',
    discountResponse: '₹100 on minimum order of ₹200',
    expireTimeResponse: '2 hours 30 minutes',
    conversionPlatformResponse: 'WhatsApp message',
  },
  {
    configs: {
      risk_category: ['high'],
      discount: { type: 'zero', max_discount: 0, minimum_order_value: 0, discount_percentage: 0 },
      communication: {
        expire_seconds: 1800,
        methods: ['whatsapp'],
      },
    },
    riskResponse: 'High RTO risk COD order',
    discountResponse: 'Disabled',
    expireTimeResponse: '30 minutes',
    conversionPlatformResponse: 'WhatsApp message',
  },
  {
    configs: {
      discount: {
        type: 'percentage',
        max_discount: 0,
        minimum_order_value: 10000,
        discount_percentage: 10,
      },
      communication: {
        expire_seconds: 3660,
      },
    },
    riskResponse: ' RTO risk COD order',
    discountResponse: '₹10% off on minimum order of ₹100',
    expireTimeResponse: '1 hour 1 minute',
    conversionPlatformResponse: '',
  },
];

export const SAVED_CONFIGS_VALUES = [
  {
    durationVal: {
      hours: 1,
      mins: 30,
    },
    configs: {
      isManualReviewOpted: false,
      riskCategory: 'all',
    },
    durationResponse: 5400,
    riskResponse: ['high', 'medium', 'low'],
  },
  {
    durationVal: {
      hours: 0,
      mins: 30,
    },
    configs: {
      isManualReviewOpted: true,
      riskCategory: 'highMedium',
    },
    durationResponse: 1800,
    riskResponse: ['high', 'medium'],
  },
  {
    durationVal: {
      hours: 1,
      mins: 0,
    },
    configs: {
      isManualReviewOpted: true,
      riskCategory: 'high',
    },
    durationResponse: 3600,
    riskResponse: ['high'],
  },
];

export const VALID_DURATION_VALUES = [
  {
    hours: 20,
    mins: 30,
    response: true,
  },
  {
    hours: 48,
    mins: 10,
    response: false,
  },
  {
    hours: 10,
    mins: '',
    response: false,
  },
];

export const SAVED_CONFIGS_VIEW_PROPS = {
  setIsConfigSaved: jest.fn(() => 'edit clicked'),
  prepayCODConfigs: {
    discount: {
      type: 'percentage',
      discount_percentage: 10,
      max_discount: 10000,
      minimum_order_value: 20000,
    },
    risk_category: ['high'],
    communication: {
      expire_seconds: 3000,
      methods: ['whatsapp'],
    },
  },
  isManualReviewOpted: true,
};

export const PREPAY_TOGGLE_PROPS = {
  isPrepayCODEnabled: true,
  setIsPrepayCODEnabled: jest.fn(),
  closeModal: jest.fn(),
  updateConfigs: jest.fn(),
  showNotification: jest.fn(),
  shopId: 'test',
  platform: 'shopify',
};

export const LINK_VALIDITY_PROPS = {
  validity: 'custom',
  setValidity: jest.fn(),
  durationVal: {
    hours: 0,
    mins: 0,
    error: {
      hours: null,
      mins: null,
    },
  },
  setDurationVal: jest.fn(),
};

export const COD_PREPAID_CONFIGS_PROPS = {
  setIsConfigSaved: jest.fn(),
  closeModal: jest.fn(),
  showPrepayCODToggle: true,
  isPrepayCODEnabled: true,
  prepayCODConfigs: {},
  updateConfigs: jest.fn(),
  showNotification: jest.fn(),
  isManualReviewOpted: true,
  platform: 'shopify',
  shopId: 'test',
};

export const DROPDOWN_INPUTS = [
  {
    roleType: 'radio',
    fieldValue: 'Percentage discount',
  },
  {
    roleType: 'combobox',
    testId: 'link-validity',
    fieldValue: '30 minutes',
  },
  {
    roleType: 'combobox',
    testId: 'conversion-platform',
    fieldValue: 'WhatsApp message',
  },
  {
    roleType: 'combobox',
    testId: 'riskCategory',
    fieldValue: 'High RTO risk orders',
  },
];
