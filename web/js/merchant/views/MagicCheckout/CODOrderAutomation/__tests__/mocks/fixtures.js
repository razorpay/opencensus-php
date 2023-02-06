export const MOCK_FETCH_PAYLOAD = {
  success: true,
  status_code: 200,
  data: {
    rule_config: [
      {
        type: 'rto_risk',
        data: [
          {
            rule_value: 'high',
            rule_action: 'cancel',
          },
          {
            rule_value: 'medium',
            rule_action: 'hold',
          },
        ],
      },
    ],
  },
};

export const MOCK_FETCH_ERROR_PAYLOAD = {
  success: false,
  status_code: 500,
  errors: ['Internal server error'],
};

export const NO_CONFIG_SET_STATE = {
  magicCODOrdersAutomation: {
    error: null,
    isLoading: false,
    ruleConfigs: {
      type: 'rto_risk',
    },
    isPending: false,
  },
};

export const NULL_RULE_CONFIG_STATE = {
  magicCODOrdersAutomation: {
    error: null,
    isLoading: false,
    ruleConfigs: null,
    isPending: false,
  },
};

export const INIT_STATE = {
  magicCODOrdersAutomation: {
    error: null,
    isLoading: false,
    ruleConfigs: {
      type: 'rto_risk',
      data: [
        {
          rule_value: 'high',
          rule_action: 'cancel',
        },
        {
          rule_value: 'medium',
          rule_action: 'hold',
        },
      ],
    },
    isPending: false,
  },
};

export const ALL_CONFIGS_SET_STATE = {
  magicCODOrdersAutomation: {
    error: null,
    isLoading: false,
    ruleConfigs: {
      type: 'rto_risk',
      data: [
        {
          rule_value: 'high',
          rule_action: 'cancel',
        },
        {
          rule_value: 'medium',
          rule_action: 'hold',
        },
        {
          rule_value: 'low',
          rule_action: 'approve',
        },
      ],
    },
    isPending: false,
  },
};

export const DROPDOWN_INPUTS = [
  {
    name: 'type',
    type: 'select',
    changed_value: 'high',
    value: 'High risk',
    testId: 'type-combobox',
  },
  {
    name: 'action',
    type: 'select',
    changed_value: 'cancel',
    value: 'Cancel the order',
    testId: 'action-combobox',
  },
];
