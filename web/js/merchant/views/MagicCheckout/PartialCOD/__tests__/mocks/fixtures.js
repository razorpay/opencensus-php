export const FETCH_PARTIAL_COD_NEW_USER_RESP = {
  status_code: 200,
  success: true,
  data: {
    enabled: true,
    configs: null,
  },
};

export const MOCKED_FETCH_PARTIAL_COD_BASIC_CATEGORY_1_FLAT = {
  status_code: 200,
  success: true,
  data: {
    enabled: true,
    configs: {
      type: 'basic',
      prepaid_payment_amount: [
        {
          type: 'flat',
          value: 20,
          rules: {
            min_order_amount: 0,
            customer_risk_category: ['low', 'medium', 'high'],
          },
        },
      ],
    },
  },
};

export const MOCKED_FETCH_PARTIAL_COD_BASIC_CATEGORY_1_PERCT = {
  status_code: 200,
  success: true,
  data: {
    enabled: true,
    configs: {
      type: 'basic',
      prepaid_payment_amount: [
        {
          type: 'percentage',
          value: 5,
          rules: {
            min_order_amount: 0,
            customer_risk_category: ['low', 'medium', 'high'],
          },
        },
      ],
    },
  },
};

export const MOCKED_FETCH_PARTIAL_COD_BASIC_CATEGORY_2_FLAT = {
  status_code: 200,
  success: true,
  data: {
    enabled: true,
    configs: {
      type: 'basic',
      prepaid_payment_amount: [
        {
          type: 'flat',
          value: 5,
          rules: {
            min_order_amount: 0,
            customer_risk_category: ['low', 'medium'],
          },
        },
        {
          type: 'flat',
          value: 6,
          rules: {
            min_order_amount: 0,
            customer_risk_category: ['high'],
          },
        },
      ],
    },
  },
};

export const MOCKED_FETCH_PARTIAL_COD_BASIC_CATEGORY_2_PERCT = {
  status_code: 200,
  success: true,
  data: {
    enabled: true,
    configs: {
      type: 'basic',
      prepaid_payment_amount: [
        {
          type: 'percentage',
          value: 5,
          rules: {
            min_order_amount: 0,
            customer_risk_category: ['low', 'medium'],
          },
        },
        {
          type: 'percentage',
          value: 6,
          rules: {
            min_order_amount: 0,
            customer_risk_category: ['high'],
          },
        },
      ],
    },
  },
};

export const FETCH_PARTIAL_COD_NEW_USER_STATE = {
  isLoading: false,
  error: null,
  partialCODConfigs: {},
  isPartialCODEnabled: false,
};
