import { RULE_GROUP } from 'merchant/views/Optimizer/__test__/mock';
import {
  getValue,
  removeMid,
  getRuleStatus,
  uniqueArray,
  findProviderName,
  createMappedProviders,
  setRuleMode,
  liveRulesList,
} from '../utils';

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
          label: 'Card',
          value: 'card',
        },
        {
          label: 'Netbanking',
          value: 'netbanking',
        },
        {
          label: 'UPI Intent',
          value: 'upi_intent',
        },
        {
          label: 'UPI Collect',
          value: 'upi_collect',
        },
        {
          label: 'Wallet',
          value: 'wallet',
        },
        {
          label: 'EMI',
          value: 'emi',
        },
        {
          label: 'Cardless EMI',
          value: 'cardless_emi',
        },
        {
          label: 'Emandate',
          value: 'emandate',
        },
        {
          label: 'Paylater',
          value: 'paylater',
        },
        {
          label: 'Bank Transfer',
          value: 'bank_transfer',
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

describe('Optimizer Utils > createMappedProviders', () => {
  it('should return the mapped providers', () => {
    const RAZORPAY = [
      {
        Provider_name: 'razorpay',
        Description: 'Razorpay provider',
        Gateway: 'razorpay',
        Gateway_details: {
          'Payment Methods': ['card', 'netbanking', 'upi', 'wallet'],
          wallet_metadata: {
            wallets: [
              'mpesa',
              'mobikwik',
              'phonepeswitch',
              'sbibuddy',
              'phonepe',
              'paypal',
              'jiomoney',
              'amazonpay',
              'freecharge',
              'bajajpay',
              'olamoney',
              'openwallet',
              'razorpaywallet',
              'payumoney',
              'airtelmoney',
              'paytm',
              'mcash',
              'boost',
              'touchngo',
              'grabpay',
              'payzapp',
            ],
          },
        },
        Currency: ['INR'],
        Gateway_acquirer: 'razorpay',
        Status: '',
        created_at: 0,
        updated_at: 0,
      },
    ];
    const result = createMappedProviders([...TERMINAL_PROVIDERS, ...RAZORPAY]);
    expect(result).toEqual([
      {
        id: 'payu_HdvEjdKKJMBX89',
        name: 'payu test',
        value: 'payu_HdvEjdKKJMBX89',
      },
      {
        id: 'razorpay',
        name: 'razorpay',
        value: 'razorpay',
      },
    ]);
  });
});

describe('Optimizer Utils > setRuleMode', () => {
  it('should update rule mode', () => {
    const result = setRuleMode(RULE_GROUP.rules, 'test');
    expect(result[0].expression.operands[1].operands[0].value).toEqual('test');
  });
});

describe('Optimizer Utils > liveRulesList', () => {
  it('should return the live rules list', () => {
    const result = liveRulesList([RULE_GROUP]);
    expect(result).toEqual([RULE_GROUP]);
  });
});
