import { getValue, removeMid, getRuleStatus, uniqueArray, findProviderName } from '../utils';

const RULE_GROUP = {
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

const TERMINAL_PROVIDERS = [
  {
    Provider_name: 'payu test',
    Description: 'testing n',
    Gateway: 'payu',
    Gateway_details: {
      Key: 'hujyb3123',
      'Payment Methods': ['card', 'emi', 'netbanking', 'upi'],
      Recurring: true,
      Salt: '',
      Sodexo: false,
      optimizer_seamless_disabled: false,
    },
    Currency: ['INR'],
    Gateway_acquirer: 'payu',
    Terminal_id: 'HdvEjdKKJMBX89',
    Status: 'activated',
    created_at: 1627381704,
    updated_at: 1720676799,
  },
];

describe('Optimizer Utils > getValue', () => {
  it('should return the value of parameter', () => {
    const result = getValue('parameter', '$payment.navigator_method');
    expect(result).toEqual({
      name: 'Payment Method',
      value: '$payment.navigator_method',
      description: 'Card, Netbanking, UPI Intent, UPI Collect',
      id: 2,
      values: [
        {
          value: 'card',
        },
        {
          value: 'netbanking',
        },
        {
          value: 'upi_intent',
        },
        {
          value: 'upi_collect',
        },
        {
          value: 'wallet',
        },
        {
          value: 'emi',
        },
        {
          value: 'emandate',
        },
      ],
      operators: {
        '==': {
          multiple: false,
          type: 'dropdown',
        },
        in: {
          multiple: true,
          type: 'dropdown',
        },
        '!=': {
          multiple: false,
          type: 'dropdown',
        },
      },
      type: 'string',
    });
  });

  it('should return the value of operator', () => {
    const result = getValue('operator', '==');
    expect(result).toEqual({
      name: 'Equal to',
      description: 'You can select only one comparing value',
      id: 2,
      input_type: 'input',
      value: '==',
      type: 'comparator',
    });
  });
});

describe('Optimizer Utils > removeMid', () => {
  it('should remove the mid text from the string', () => {
    const result = removeMid('test_card_rule_ELi8nocD30pFkb');
    expect(result).toEqual('test_card_rule');
  });
});

describe('Optimizer Utils > getRuleStatus', () => {
  it('should return the status of the rule', () => {
    const result = getRuleStatus(RULE_GROUP);
    expect(result).toEqual('live');
  });
});

describe('Optimizer Utils > uniqueArray', () => {
  it('should return the unique array', () => {
    const result = uniqueArray(['upi_intent', 'upi_collect', 'upi_intent']);
    expect(result).toEqual(['upi_intent', 'upi_collect']);
  });
});

describe('Optimizer Utils > findProviderName', () => {
  it('should return the provider name', () => {
    const result = findProviderName(TERMINAL_PROVIDERS, 'HdvEjdKKJMBX89');
    expect(result).toEqual('payu test');
  });
});
