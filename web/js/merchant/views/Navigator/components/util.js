import { Operand } from '../models/Operand';

export const operators = [
  {
    name: 'One Of',
    description: 'You can select multiple comparing value',
    id: 1,
    type: 'comparator',
    input_type: 'list',
    value: 'in',
  },
  {
    name: 'Equal to',
    description: 'You can select only one comparing value',
    id: 2,
    input_type: 'input',
    value: '==',
    type: 'comparator',
  },
  {
    name: 'Not equal to',
    description: 'You can select only one comparing value',
    input_type: 'input',
    id: 3,
    value: '!=',
    type: 'comparator',
  },
  {
    name: 'Between',
    description: 'You can select only one comparing value',
    id: 4,
    input_type: 'between-input',
    value: 'between',
    type: 'comparator',
  },
  {
    name: 'Starting With',
    description: 'You can select only one comparing value',
    id: 5,
    input_type: 'input',
    value: 'starting_with',
    type: 'comparator',
  },
  {
    name: 'Ending With',
    description: 'You can select only one comparing value',
    id: 6,
    input_type: 'input',
    value: 'ending_with',
    type: 'comparator',
  },
  {
    name: 'Less Than',
    description: 'You can select only one comparing value',
    input_type: 'input',
    id: 7,
    value: '<',
    type: 'comparator',
  },
  {
    name: 'Greater Than',
    description: 'You can select only one comparing value',
    input_type: 'input',
    id: 8,
    value: '>',
    type: 'comparator',
  },
  {
    name: 'Contains',
    description: 'You can give only one comparing value',
    id: 9,
    input_type: 'input',
    value: 'contains',
    type: 'comparator',
  },
  {
    name: 'Greater Than Equal',
    description: 'You can select only one comparing value',
    input_type: 'input',
    id: 10,
    value: '>=',
    type: 'comparator',
  },
  {
    name: 'Less Than Equal',
    description: 'You can select only one comparing value',
    input_type: 'input',
    id: 11,
    value: '<=',
    type: 'comparator',
  },
];

const customIdentifierParameter = [
  {
    name: 'Custom Identifier 1',
    value: '$payment.optimizer_identifier_1',
    description: 'Custom Identifier',
    id: 10,
    values: [],
    operators: {
      '==': {
        number: false,
        type: 'input',
      },
      '!=': {
        number: false,
        type: 'input',
      },
      in: {
        number: false,
        type: 'input',
        multiple: true,
      },
    },
    type: 'string',
  },
  {
    name: 'Custom Identifier 2',
    value: '$payment.optimizer_identifier_2',
    description: 'Custom Identifier',
    id: 11,
    values: [],
    operators: {
      '==': {
        number: false,
        type: 'input',
      },
      '!=': {
        number: false,
        type: 'input',
      },
      in: {
        number: false,
        type: 'input',
        multiple: true,
      },
    },
    type: 'string',
  },
  {
    name: 'Custom Identifier 3',
    value: '$payment.optimizer_identifier_3',
    description: 'Custom Identifier',
    id: 12,
    values: [],
    operators: {
      '==': {
        number: false,
        type: 'input',
      },
      '!=': {
        number: false,
        type: 'input',
      },
      in: {
        number: false,
        type: 'input',
        multiple: true,
      },
    },
    type: 'string',
  },
];

const walletPrameter = [
  {
    name: 'Wallets',
    value: '$payment.optimizer_wallet',
    description: 'Freecharge, olamoney, mobikwik',
    id: 14,
    values: [
      {
        value: 'airtelmoney',
      },
      {
        value: 'amazonpay',
      },
      {
        value: 'citrus',
      },
      {
        value: 'freecharge',
      },
      {
        value: 'jiomoney',
      },
      {
        value: 'mobikwik',
      },
      {
        value: 'olamoney',
      },
      {
        value: 'paypal',
      },
      {
        value: 'paytm',
      },
      {
        value: 'payumoney',
      },
      {
        value: 'payzapp',
      },
      {
        value: 'phonepe',
      },
      {
        value: 'sbibuddy',
      },
      {
        value: 'zeta',
      },
      {
        value: 'citibankrewards',
      },
      {
        value: 'itzcash',
      },
      {
        value: 'paycash',
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
  },
];

const emiDurationParameter = [
  {
    name: 'EMI Duration',
    value: '$payment.optimizer_emi_duration',
    description: 'In months',
    id: 13,
    values: [
      {
        value: '3',
      },
      {
        value: '6',
      },
      {
        value: '9',
      },
      {
        value: '12',
      },
      {
        value: '15',
      },
      {
        value: '18',
      },
      {
        value: '24',
      },
      {
        value: '36',
      },
    ],
    operators: {
      '==': {
        multiple: false,
        type: 'dropdown',
      },
      '>': {
        multiple: false,
        type: 'dropdown',
      },
      '<': {
        multiple: false,
        type: 'dropdown',
      },
      '>=': {
        multiple: false,
        type: 'dropdown',
      },
      '<=': {
        multiple: false,
        type: 'dropdown',
      },
    },
    type: 'numeric',
  },
];

const internationalParameter = [
  {
    name: 'International',
    value: '$payment.optimizer_international',
    description: 'International payment',
    id: 15,
    values: [
      {
        value: 'true',
      },
      {
        value: 'false',
      },
    ],
    operators: {
      '==': {
        multiple: false,
        type: 'dropdown',
      },
      '!=': {
        multiple: false,
        type: 'dropdown',
      },
    },
    type: 'boolean',
  },
];

const currencyParameter = [
  {
    name: 'Currency',
    value: '$payment.optimizer_currency',
    description: 'INR, USD',
    id: 16,
    values: [],
    operators: {
      '==': {
        multiple: false,
        type: 'dropdown',
      },
      '!=': {
        multiple: false,
        type: 'dropdown',
      },
      in: {
        multiple: true,
        type: 'dropdown',
      },
    },
    type: 'string',
  },
];

export const parameters = [
  {
    name: 'Channels',
    value: '$payment.navigator_channel',
    values: [
      {
        value: 'website',
      },
      {
        value: 'android',
      },
      {
        value: 'ios',
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
    description: 'Website, Android, iOS',
    type: 'string',
    id: 1,
  },
  {
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
  },
  {
    name: 'BIN Number',
    value: '$payment.navigator_bin_number',
    description: 'Card IIN number',
    id: 4,
    values: [],
    operators: {
      '==': {
        type: 'input',
        number: true,
      },
      in: {
        multiple: true,
        type: 'input',
        number: true,
      },
      starting_with: {
        type: 'input',
        number: true,
      },
      ending_with: {
        type: 'input',
        number: true,
      },
    },
    type: 'numeric',
  },
  {
    name: 'Card Type',
    value: '$payment.navigator_card_type',
    description: 'Debit, Credit, Prepaid, Corporate',
    id: 5,
    values: [
      {
        value: 'debit',
      },
      {
        value: 'credit',
      },
      {
        value: 'prepaid',
      },
      {
        value: 'corporate',
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
  },
  {
    name: 'Card Brand',
    value: '$payment.navigator_card_brand',
    description: 'American Express,Diners Club,Discover',
    id: 6,
    values: [
      {
        value: 'AMEX',
      },
      {
        value: 'DICL',
      },
      {
        value: 'DISC',
      },
      {
        value: 'JCB',
      },
      {
        value: 'MAES',
      },
      {
        value: 'MC',
      },
      {
        value: 'RUPAY',
      },
      {
        value: 'UNP',
      },
      {
        value: 'VISA',
      },
      {
        value: 'BAJAJ',
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
  },
  {
    name: 'Card Issuer',
    value: '$payment.navigator_card_issuer',
    description: 'SBIN,HDFC,ICIC,UTIB,KKBK',
    id: 7,
    values: [
      {
        value: 'SBIN',
      },
      {
        value: 'HDFC',
      },
      {
        value: 'ICIC',
      },
      {
        value: 'UTIB',
      },
      {
        value: 'KKBK',
      },
      {
        value: 'BARB_R',
      },
      {
        value: 'BKID',
      },
      {
        value: 'CNRB',
      },
      {
        value: 'PUNB_R',
      },
      {
        value: 'UBIN',
      },
      {
        value: 'IDIB',
      },
      {
        value: 'ALLA',
      },
      {
        value: 'CBIN',
      },
      {
        value: 'IOBA',
      },
      {
        value: 'IBKL',
      },
      {
        value: 'YESB',
      },
      {
        value: 'ANDB',
      },
      {
        value: 'AIRP',
      },
      {
        value: 'FDRL',
      },
      {
        value: 'MAHB',
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
  },
  {
    name: 'Banks',
    value: '$payment.navigator_bank',
    description: 'SBIN,HDFC,ICIC,UTIB',
    id: 8,
    values: [
      {
        value: 'SBIN',
      },
      {
        value: 'HDFC',
      },
      {
        value: 'ICIC',
      },
      {
        value: 'UTIB',
      },
      {
        value: 'KKBK',
      },
      {
        value: 'BARB_R',
      },
      {
        value: 'BKID',
      },
      {
        value: 'CNRB',
      },
      {
        value: 'PUNB_R',
      },
      {
        value: 'UBIN',
      },
      {
        value: 'IDIB',
      },
      {
        value: 'ALLA',
      },
      {
        value: 'CBIN',
      },
      {
        value: 'IOBA',
      },
      {
        value: 'IBKL',
      },
      {
        value: 'YESB',
      },
      {
        value: 'ANDB',
      },
      {
        value: 'AIRP',
      },
      {
        value: 'FDRL',
      },
      {
        value: 'MAHB',
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
  },
  {
    name: 'Amount',
    value: '$payment.navigator_amount',
    description: 'In Rupees',
    id: 9,
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
    ],
    operators: {
      '==': {
        number: true,
        type: 'input',
      },
      '>': {
        number: true,
        type: 'input',
      },
      '<': {
        number: true,
        type: 'input',
      },
      '>=': {
        number: true,
        type: 'input',
      },
      '<=': {
        number: true,
        type: 'input',
      },
      between: {
        number: true,
        between: true,
        type: 'input',
      },
    },
    type: 'numeric',
  },
  ...customIdentifierParameter,
  ...emiDurationParameter,
  ...walletPrameter,
  ...internationalParameter,
  ...currencyParameter,
];
export const PROVIDERS = [
  { name: 'Smart Router1', id: 1, value: 'smartrouter' },
  { name: 'Razorpay', id: 2, value: 'razorpay' },
  { name: 'Smart Router2', id: 3, value: 'smartrouter2' },
];

export const logical_operators = [
  {
    name: 'AND',
    description: 'All conditions must match',
    value: '&&',
    id: 1,
  },
  {
    name: 'OR',
    description: 'At least one condition must match',
    value: '||',
    id: 2,
  },
];

export const getValue = (type, value) => {
  let r;
  if (type == 'parameter') {
    r = parameters;
  }
  if (type == 'operator') {
    r = operators;
  }
  return r.find((p) => p.value == value) || '';
};

export const isExpressionValid = (expression) => {
  return (
    expression.operands &&
    expression.operands[0] &&
    expression.operands[0].value &&
    expression.operands[1].value &&
    expression.value
  );
};

export const getConditionOn = (precondition) => {
  let val;
  if (precondition.type == 'logical') {
    val = precondition.operands
      .map((o) => getValue('parameter', o.operands[0].value).name)
      .join(', ');
  } else {
    val = getValue('parameter', precondition.operands[0].value).name;
  }
  return val;
};

export const namespace = {
  name: 'Navigator',
  description: 'Routing as a service',
  domain_model: {
    entities: [
      {
        name: 'payment',
        attributes: [
          {
            name: 'navigator_amount',
            type: 'numeric',
            score: 0,
            primary: false,
            sub_type: '',
            eventable: false,
            indexable: false,
            attributes: null,
            default_value: '',
            entity_values: null,
            dependent_attribute: '',
          },
          {
            name: 'navigator_method',
            type: 'string',
            score: 0,
            primary: false,
            sub_type: '',
            eventable: false,
            indexable: false,
            attributes: null,
            default_value: '',
            entity_values: null,
            dependent_attribute: '',
          },
          {
            name: 'rule_mode',
            type: 'list',
            score: 0,
            primary: false,
            sub_type: 'string',
            eventable: false,
            indexable: false,
            attributes: null,
            default_value: '',
            entity_values: null,
            dependent_attribute: '',
          },
          {
            name: 'id',
            type: 'string',
            score: 0,
            primary: true,
            sub_type: '',
            eventable: false,
            indexable: false,
            attributes: null,
            default_value: '',
            entity_values: null,
            dependent_attribute: '',
          },
        ],
      },
      {
        name: 'merchant',
        attributes: [
          {
            name: 'id',
            type: 'string',
            score: 0,
            primary: false,
            sub_type: '',
            eventable: false,
            indexable: false,
            attributes: null,
            default_value: '',
            entity_values: null,
            dependent_attribute: '',
          },
          {
            name: 'features',
            type: 'list',
            score: 0,
            primary: false,
            sub_type: 'string',
            eventable: false,
            indexable: false,
            attributes: null,
            default_value: '',
            entity_values: null,
            dependent_attribute: '',
          },
        ],
      },
    ],
    evaluating_entity: {
      name: 'provider',
      type: 'list',
      sub_type: 'object',
      attributes: [
        {
          name: 'id',
          type: 'string',
          score: 0,
          primary: false,
          sub_type: '',
          eventable: false,
          indexable: false,
          attributes: null,
          default_value: '',
          entity_values: null,
          dependent_attribute: '',
        },
      ],
    },
  },
  created_by: 'Raas',
};

export const getRuleScore = (rule) => {
  const score = rule.rules.length ? rule.rules[0].score : null;
  return score;
};

export const rule = {
  name: '',
  description: '',
  created_by: '',
  last_edited_by: '',
  outcome_type: '',
  strategy: 'default',
  mandatory_attributes: [],
  additional_attributes: [{ name: 'rule_mode', type: 'string', values: [''] }],
  precondition: {
    operands: [new Operand(), new Operand()],
    value: null,
    type: null,
  },
  rules: [],
};

const LOGO_PATH = 'static/assets/merchant-dash/providers';

export const gatewayLogos = {
  razorpay: `${window.cdnBaseUrl}/${LOGO_PATH}/razorpay.png`,
  smart_router: `${window.cdnBaseUrl}/${LOGO_PATH}/razorpay.png`,
  payu: `${window.cdnBaseUrl}/${LOGO_PATH}/payu.png`,
  paytm: `${window.cdnBaseUrl}/${LOGO_PATH}/paytm.png`,
  billdesk_optimizer: `${window.cdnBaseUrl}/${LOGO_PATH}/bill-desk.png`,
  atom: `${window.cdnBaseUrl}/${LOGO_PATH}/atom.png`,
  fss: `${window.cdnBaseUrl}/${LOGO_PATH}/fss.png`,
  cybersource: `${window.cdnBaseUrl}/${LOGO_PATH}/cybersource.png`,
  cashfree: `${window.cdnBaseUrl}/${LOGO_PATH}/cashfree.svg`,
  ccavenue: `${window.cdnBaseUrl}/${LOGO_PATH}/ccavenue.svg`,
  upi_mindgate: `${window.cdnBaseUrl}/${LOGO_PATH}/hdfc.png`,
  pinelabs: `${window.cdnBaseUrl}/${LOGO_PATH}/pinelabs.png`,
  ingenico: `${window.cdnBaseUrl}/${LOGO_PATH}/ingenico.png`,
  axis_migs: `${window.cdnBaseUrl}/${LOGO_PATH}/axis.png`,
  upi_axis: `${window.cdnBaseUrl}/${LOGO_PATH}/axis.png`,
};

export const popularGateways = ['payu'];

export const mapRulesObjectToArray = (e) => {
  const rules = [];
  Object.keys(e).forEach((key) => {
    rules.push(...e[key]);
  });
  return rules;
};

export const mapRulesArrayToObject = (RULES) => {
  const rules = {};
  RULES.forEach((r) => {
    const provider_priority = r.additional_attribute[0].value;
    if (!rules[provider_priority]) {
      rules[provider_priority] = [];
    }
    rules[provider_priority].push(r);
  });
  return rules;
};

export const appendMid = (value) => {
  return `${value}_${window.rzp_user.current}`;
};

export const removeMid = (value) => {
  const temp = value.split('_');
  temp.pop();
  return temp.join('_');
  // here we remove mid at the end one
};

export const DEFAULT_RULE = 'Default Rule';
export const TOTAL_RULE_LIMIT = 15;

const setRuleModeOperand = (rule, mode) => {
  rule.expression?.operands?.forEach((o) => {
    if (o.operands && o.operands.length > 1) {
      if (o.operands[1]?.type === 'variable' && o.operands[1]?.value === '$payment.rule_mode') {
        o.operands[0].value = mode;
      } else if (
        o.operands[0]?.type === 'variable' &&
        o.operands[0]?.value === '$payment.rule_mode'
      ) {
        o.operands[1].value = mode;
      }
    }
  });
};

export const setRuleMode = (rules, mode) => {
  rules.forEach((r) => {
    setRuleModeOperand(r, mode);
  });
  return rules;
};

export const getRuleStatus = (r) => {
  const status = r.additional_attributes[0].values[0] || 'test';
  return status;
};

export const total_live_rules = (rules) => {
  return rules.filter((r) => getRuleStatus(r) === 'live');
};

export const get_unique = () => Math.floor(Math.random() * 100000000000);

export const uniqueArray = (arr) => {
  const o = {};
  const a = [];
  let i, e;
  for (i = 0; (e = arr[i]); i++) {
    o[e] = 1;
  }
  for (e in o) {
    if (o.hasOwnProperty(e)) {
      a.push(e);
    }
  }
  return a;
};

export const findProviderName = (providers, id) => {
  if (id === 'razorpay') {
    return 'razorpay';
  }
  const provider = providers?.filter((p) => p.Terminal_id === id);
  return provider.length > 0 ? provider[0].Provider_name : id;
};

export const SMART_ROUTER = 'smart_router';

export const rzpGateways = ['razorpay', 'smart_router'];

const DASHBOARD_PATH = 'static/assets/merchant-dash/provider-dashboard';

export const gatewayDetailsMapping = {
  payu: {
    dashboardImg: `${window.cdnBaseUrl}/${DASHBOARD_PATH}/payu_dashboard.png`,
    dashboardUrl: 'https://onboarding.payu.in',
    dashboardUrlLabel: 'onboarding.payu.in',
  },
  cashfree: {
    dashboardImg: `${window.cdnBaseUrl}/${DASHBOARD_PATH}/cashfree_dashboard.png`,
    dashboardUrl: 'https://merchant.cashfree.com',
    dashboardUrlLabel: 'merchant.cashfree.com',
  },
  ccavenue: {
    dashboardImg: `${window.cdnBaseUrl}/${DASHBOARD_PATH}/ccavenue_dashboard.png`,
    dashboardUrl: 'https://dashboard.ccavenue.com',
    dashboardUrlLabel: 'dashboard.ccavenue.com',
  },
  paytm: {
    dashboardImg: `${window.cdnBaseUrl}/${DASHBOARD_PATH}/paytm_dashboard.png`,
    dashboardUrl: 'https://dashboard.paytm.com',
    dashboardUrlLabel: 'dashboard.paytm.com',
  },
  atom: {
    dashboardImg: null,
    dashboardUrl: 'https://pgreports.atomtech.in/titan_merchant_console/home',
    dashboardUrlLabel: 'pgreports.atomtech.in',
  },
  upi_mindgate: {
    dashboardImg: null,
    dashboardUrl: 'https://www.mindgate.in/our-offerings/payment-gateway-corporate/',
    dashboardUrlLabel: 'mindgate.in',
  },
  pinelabs: {
    dashboardImg: `${window.cdnBaseUrl}/${DASHBOARD_PATH}/pinelabs_dashboard.png`,
    dashboardUrl: 'https://www.pinelabs.com/online-payment-gateway',
    dashboardUrlLabel: 'pinelabs.com',
  },
  ingenico: {
    dashboardImg: null,
    dashboardUrl: 'https://www.techprocess.co.in/product-offerings/payment-gateway',
    dashboardUrlLabel: 'techprocess.co.in',
  },
  billdesk_optimizer: {
    dashboardImg: null,
    dashboardUrl: 'https://services.billdesk.com/console/',
    dashboardUrlLabel: 'services.billdesk.com',
  },
  axis_migs: {
    dashboardImg: null,
    dashboardUrl:
      'https://www.axisbank.com/business-banking/collection-solutions/internet-payment-gateway-solutions/overview',
    dashboardUrlLabel: 'axisbank.com',
  },
  upi_axis: {
    dashboardImg: null,
    dashboardUrl:
      'https://www.axisbank.com/business-banking/collection-solutions/internet-payment-gateway-solutions/overview',
    dashboardUrlLabel: 'axisbank.com',
  },
};

export const createMappedProviders = (terminalProviders) => {
  let MAPPED_PROVIDERS = [];
  MAPPED_PROVIDERS = terminalProviders.map((p) => {
    if (rzpGateways.includes(p.Gateway)) {
      return {
        id: p.Gateway,
        name: p.Provider_name,
        value: p.Gateway,
      };
    }
    return {
      id: `${p.Gateway}_${p.Terminal_id}`,
      name: p.Provider_name || p.Gateway,
      value: `${p.Gateway}_${p.Terminal_id}`,
    };
  });
  return MAPPED_PROVIDERS;
};
