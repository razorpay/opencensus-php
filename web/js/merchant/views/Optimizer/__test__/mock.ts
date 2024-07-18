export const RULE_GROUP = {
  id: 'OTHJ2cyULxjn3T',
  name: 'test_prajwal_ELi8nocD30pFkb',
  description: 'custom_ident',
  outcome_type: '',
  strategy: 'default',
  mandatory_attributes: [],
  additional_attributes: [
    {
      name: 'rule_mode',
      type: 'string',
      values: ['live'],
    },
  ],
  precondition: {
    type: 'logical',
    value: '&&',
    operands: [
      {
        type: 'comparator',
        value: 'in',
        operands: [
          {
            type: 'variable',
            value: '$payment.navigator_method',
            operands: null,
          },
          {
            type: 'array',
            value: 'upi_intent,upi_collect',
            operands: null,
          },
        ],
      },
      {
        type: 'comparator',
        value: '==',
        operands: [
          {
            type: 'variable',
            value: '$payment.optimizer_identifier_1',
            operands: null,
          },
          {
            type: 'string',
            value: 'GIFT',
            operands: null,
          },
        ],
      },
      {
        type: 'comparator',
        value: '>',
        operands: [
          {
            type: 'variable',
            value: '$payment.navigator_amount',
            operands: null,
          },
          {
            type: 'numeric',
            value: '300',
            operands: null,
          },
        ],
      },
    ],
  },
  rules: [
    {
      id: 'OTJp8Lx7DO4RKh',
      name: '39512154504_test_test_prajwal_ELi8nocD30pFkb',
      description: '',
      default_expression: null,
      expression: {
        type: 'logical',
        value: '&&',
        operands: [
          {
            type: 'comparator',
            value: '==',
            operands: [
              {
                type: 'variable',
                value: '$provider.id',
                operands: null,
              },
              {
                type: 'string',
                value: 'paytm_LKEPFajki3Mdj0',
                operands: null,
              },
            ],
          },
          {
            type: 'comparator',
            value: 'in',
            operands: [
              {
                type: 'string',
                value: 'live',
                operands: null,
              },
              {
                type: 'variable',
                value: '$payment.rule_mode',
                operands: null,
              },
            ],
          },
          {
            type: 'comparator',
            value: '==',
            operands: [
              {
                type: 'variable',
                value: '$merchant.id',
                operands: null,
              },
              {
                type: 'string',
                value: 'ELi8nocD30pFkb',
                operands: null,
              },
            ],
          },
        ],
      },
      score: 1,
      skip_on_failure: false,
      created_by: '',
      created_at: '2024-07-01T09:29:15Z',
      updated_at: '2024-07-12T12:47:58Z',
      additional_attribute: [
        {
          name: 'provider_priority',
          value: '1',
        },
        {
          name: 'load',
          value: '100',
        },
      ],
      indexable: true,
      mode: null,
      canary: {
        use_canary: false,
        rule: null,
        ramp_percent: 100,
      },
    },
  ],
  created_by: 'test.qa@razorpay.com',
  created_at: '2024-07-01T07:01:29Z',
  updated_at: '2024-07-01T09:29:15Z',
};
