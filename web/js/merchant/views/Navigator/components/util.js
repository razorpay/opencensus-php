import cloneDeep from 'lodash/cloneDeep';
import isEmpty from 'lodash/isEmpty';

import { Operand } from 'merchant/views/Navigator/models/Operand';

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

const getExpStatus = (name) => {
  const user = window.rzp_user;
  return ((user?.experiments || {})[name] || {}).result === 'on';
};

const eMandatePaymentMethod = getExpStatus('optimizer_emandate') ? [{ value: 'emandate' }] : [];

const tokenAuthTypeParameter = getExpStatus('optimizer_emandate')
  ? [
      {
        name: 'Emandate Authentication Type',
        value: '$payment.optimizer_token_auth_type',
        description: 'Netbanking, Debit Card, Aadhaar',
        id: 2,
        values: [
          {
            value: 'netbanking',
          },
          {
            value: 'debitcard',
          },
          {
            value: 'aadhaar',
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
    ]
  : [];

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
      ...eMandatePaymentMethod,
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
    name: 'Card tokenised',
    value: '$payment.optimizer_card_tokenised',
    description: 'Tokenised card payment',
    id: 17,
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
  {
    name: 'Card Issuer',
    value: '$payment.navigator_card_issuer',
    description: 'SBIN,HDFC,ICIC,UTIB,KKBK',
    id: 7,
    values: [
      { value: 'ABPB' },
      { value: 'AIRP' },
      { value: 'ALLA' },
      { value: 'ANDB' },
      { value: 'ANDB_C' },
      { value: 'UTIB' },
      { value: 'BDBL' },
      { value: 'BBKM' },
      { value: 'BARB_R' },
      { value: 'BKID' },
      { value: 'MAHB' },
      { value: 'BACB' },
      { value: 'CNRB' },
      { value: 'CSBK' },
      { value: 'CBIN' },
      { value: 'CIUB' },
      { value: 'CORP' },
      { value: 'COSB' },
      { value: 'DCBL' },
      { value: 'BKDN' },
      { value: 'DEUT' },
      { value: 'DBSS' },
      { value: 'DLXB' },
      { value: 'DLXB_C' },
      { value: 'ESAF' },
      { value: 'ESFB' },
      { value: 'FDRL' },
      { value: 'HDFC' },
      { value: 'ICIC' },
      { value: 'IBKL' },
      { value: 'IBKL_C' },
      { value: 'IDFB' },
      { value: 'IDIB' },
      { value: 'IOBA' },
      { value: 'INDB' },
      { value: 'JAKA' },
      { value: 'JSBP' },
      { value: 'KCCB' },
      { value: 'KJSB' },
      { value: 'KARB' },
      { value: 'KVBL' },
      { value: 'KKBK' },
      { value: 'LAVB_C' },
      { value: 'LAVB_R' },
      { value: 'MSNU' },
      { value: 'NKGS' },
      { value: 'NESF' },
      { value: 'ORBC' },
      { value: 'UTBI' },
      { value: 'PSIB' },
      { value: 'PUNB_R' },
      { value: 'RATN' },
      { value: 'RATN_C' },
      { value: 'SRCB' },
      { value: 'SVCB_C' },
      { value: 'SVCB' },
      { value: 'SIBL' },
      { value: 'SCBL' },
      { value: 'SBBJ' },
      { value: 'SBHY' },
      { value: 'SBIN' },
      { value: 'SBMY' },
      { value: 'STBP' },
      { value: 'SBTR' },
      { value: 'SURY' },
      { value: 'SYNB' },
      { value: 'TMBL' },
      { value: 'TNSC' },
      { value: 'TBSB' },
      { value: 'TJSB' },
      { value: 'UCBA' },
      { value: 'UBIN' },
      { value: 'VARA' },
      { value: 'VIJB' },
      { value: 'YESB' },
      { value: 'YESB_C' },
      { value: 'ZCBL' },
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
        value: 'AIRP',
        label: 'Airtel Payments Bank',
      },
      {
        value: 'ALLA',
        label: 'Allahabad Bank',
      },
      {
        value: 'ANDB',
        label: 'Andhra Bank',
      },
      {
        value: 'AUBL',
        label: 'AU Small Finance Bank',
      },
      {
        value: 'BARB_R',
        label: 'Bank of Baroda - Retail Banking',
      },
      {
        value: 'BBKM',
        label: 'Bank of Bahrain and Kuwait',
      },
      {
        value: 'BCBM',
        label: 'Bharat Co-Operative Bank',
      },
      {
        value: 'BDBL',
        label: 'Bandhan Bank',
      },
      {
        value: 'BKDN',
        label: 'Dena Bank',
      },
      {
        value: 'BKID',
        label: 'Bank of India',
      },
      {
        value: 'CBIN',
        label: 'Central Bank of India',
      },
      {
        value: 'CIUB',
        label: 'City Union Bank',
      },
      {
        value: 'CNRB',
        label: 'Canara Bank',
      },
      {
        value: 'CORP',
        label: 'Corporation Bank',
      },
      {
        value: 'COSB',
        label: 'Cosmos Co-operative Bank',
      },
      {
        value: 'CSBK',
        label: 'Catholic Syrian Bank',
      },
      {
        value: 'DBSS',
        label: 'Development Bank of Singapore',
      },
      {
        value: 'DCBL',
        label: 'DCB Bank',
      },
      {
        value: 'DEUT',
        label: 'Deutsche Bank',
      },
      {
        value: 'DLXB',
        label: 'Dhanlaxmi Bank',
      },
      {
        value: 'ESAF',
        label: 'ESAF Small Finance Bank',
      },
      {
        value: 'ESFB',
        label: 'Equitas Small Finance Bank',
      },
      {
        value: 'FDRL',
        label: 'Federal Bank',
      },
      {
        value: 'FSFB',
        label: 'Fincare Small Finance Bank',
      },
      {
        value: 'HDFC',
        label: 'HDFC Bank',
      },
      {
        value: 'HSBC',
        label: 'HSBC',
      },
      {
        value: 'IBKL',
        label: 'IDBI',
      },
      {
        value: 'ICIC',
        label: 'ICICI Bank',
      },
      {
        value: 'IDFB',
        label: 'IDFC FIRST Bank',
      },
      {
        value: 'IDIB',
        label: 'Indian Bank',
      },
      {
        value: 'INDB',
        label: 'Indusind Bank',
      },
      {
        value: 'IOBA',
        label: 'Indian Overseas Bank',
      },
      {
        value: 'JAKA',
        label: 'Jammu and Kashmir Bank',
      },
      {
        value: 'JSBP',
        label: 'Janata Sahakari Bank (Pune)',
      },
      {
        value: 'JSFB',
        label: 'Jana Small Finance Bank',
      },
      {
        value: 'KARB',
        label: 'Karnataka Bank',
      },
      {
        value: 'KCCB',
        label: 'The Kalupur Commercial Co-Operative Bank',
      },
      {
        value: 'KJSB',
        label: 'Kalyan Janata Sahakari Bank',
      },
      {
        value: 'KKBK',
        label: 'Kotak Mahindra Bank',
      },
      {
        value: 'KVBL',
        label: 'Karur Vysya Bank',
      },
      {
        value: 'LAVB_R',
        label: 'Lakshmi Vilas Bank - Retail Banking',
      },
      {
        value: 'MAHB',
        label: 'Bank of Maharashtra',
      },
      {
        value: 'MSNU',
        label: 'Mehsana Urban Bank',
      },
      {
        value: 'NESF',
        label: 'North East Small Finance Bank',
      },
      {
        value: 'NKGS',
        label: 'NKGSB Co-operative Bank',
      },
      {
        value: 'NSPB',
        label: 'NSDL Payments Bank',
      },
      {
        value: 'ORBC',
        label: 'Oriental Bank of Commerce',
      },
      {
        value: 'PMCB',
        label: 'Punjab & Maharashtra Co-operative Bank',
      },
      {
        value: 'PSIB',
        label: 'Punjab & Sind Bank',
      },
      {
        value: 'PUNB_R',
        label: 'Punjab National Bank - Retail Banking',
      },
      {
        value: 'RATN',
        label: 'RBL Bank',
      },
      {
        value: 'SRCB',
        label: 'Saraswat Co-operative Bank',
      },
      {
        value: 'SBBJ',
        label: 'State Bank of Bikaner and Jaipur',
      },
      {
        value: 'SBHY',
        label: 'State Bank of Hyderabad',
      },
      {
        value: 'SBIN',
        label: 'State Bank of India',
      },
      {
        value: 'SBMY',
        label: 'State Bank of Mysore',
      },
      {
        value: 'SBTR',
        label: 'State Bank of Travancore',
      },
      {
        value: 'SCBL',
        label: 'Standard Chartered Bank',
      },
      {
        value: 'SIBL',
        label: 'South Indian Bank',
      },
      {
        value: 'STBP',
        label: 'State Bank of Patiala',
      },
      {
        value: 'SURY',
        label: 'Suryoday Small Finance Bank',
      },
      {
        value: 'SVCB',
        label: 'Shamrao Vithal Co-operative Bank',
      },
      {
        value: 'SYNB',
        label: 'Syndicate Bank',
      },
      {
        value: 'TJSB',
        label: 'Thane Janata Sahakari Bank',
      },
      {
        value: 'TMBL',
        label: 'Tamilnadu Mercantile Bank',
      },
      {
        value: 'TNSC',
        label: 'Tamilnadu State Apex Co-operative Bank',
      },
      {
        value: 'UBIN',
        label: 'Union Bank of India',
      },
      {
        value: 'UCBA',
        label: 'UCO Bank',
      },
      {
        value: 'UTBI',
        label: 'United Bank of India',
      },
      {
        value: 'UTIB',
        label: 'Axis Bank',
      },
      {
        value: 'VARA',
        label: 'Varachha Co-operative Bank Limited',
      },
      {
        value: 'YESB',
        label: 'Yes Bank',
      },
      { value: 'ABPB', label: 'Aditya Birla Idea Payments Bank' },
      { value: 'ANDB_C', label: 'Andhra Bank Corporate Banking' },
      { value: 'BACB', label: 'Bassein Catholic Co-operative Bank' },
      { value: 'DLXB_C', label: 'Dhanlaxmi Bank Corporate Banking' },
      { value: 'IBKL_C', label: 'IDBI Corporate Banking' },
      { value: 'LAVB_C', label: 'Lakshmi Vilas Bank Corporate Banking' },
      { value: 'RATN_C', label: 'RBL Bank Corporate Banking' },
      { value: 'SVCB_C', label: 'Shamrao Vithal Bank Corporate Banking' },
      { value: 'TBSB', label: 'Thane Bharat Sahakari Bank' },
      { value: 'VIJB', label: 'Vijaya Bank' },
      { value: 'YESB_C', label: 'Yes Bank Corporate Banking' },
      { value: 'ZCBL', label: 'Zoroastrian Co-operative Bank' },
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
  {
    name: 'Recurring',
    value: '$payment.recurring',
    description: 'Card Recurring',
    id: 11,
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
  ...customIdentifierParameter,
  ...emiDurationParameter,
  ...walletPrameter,
  ...internationalParameter,
  ...currencyParameter,
  ...tokenAuthTypeParameter,
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
  return r.find((p) => p.value == value) || { name: '' };
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

const getLogoPath = (logoName, extension = 'png') =>
  `${window.cdnBaseUrl}/${LOGO_PATH}/${logoName}.${extension}`;

export const gatewayLogos = {
  razorpay: getLogoPath('razorpay'),
  smart_router: getLogoPath('razorpay'),
  optimizer_razorpay: getLogoPath('razorpay'),
  payu: getLogoPath('payu'),
  paytm: getLogoPath('paytm'),
  billdesk_optimizer: getLogoPath('bill-desk'),
  atom: require('assets/optimizer/atom.png'),
  fss: getLogoPath('fss'),
  cybersource: getLogoPath('cybersource'),
  cybersource_hdfc: getLogoPath('cybersource'),
  cybersource_axis: getLogoPath('cybersource'),
  cashfree: getLogoPath('cashfree', 'svg'),
  ccavenue: getLogoPath('ccavenue', 'svg'),
  upi_mindgate: getLogoPath('hdfc'),
  pinelabs: getLogoPath('pinelabs'),
  ingenico: require('assets/optimizer/ingenico.png'),
  axis_migs: getLogoPath('axis'),
  upi_axis: getLogoPath('axis'),
  hdfc: getLogoPath('hdfc'),
  upi_icici: getLogoPath('icici'),
  netbanking_axis: getLogoPath('axis'),
  checkout_dot_com_optimizer: require('assets/optimizer/cko.png'),
  easebuzz_optimizer: require('assets/optimizer/easebuzz_optimizer.png'),
  wallet_payzapp: require('assets/optimizer/payzapp.png'),
  pay10: require('assets/optimizer/pay10.png'),
};

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
export const TOTAL_RULE_LIMIT = 25;

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
  hdfc: {
    dashboardImg: null,
    dashboardUrl:
      'https://www.hdfcbank.com/wholesale/financial-institutions-and-trusts/payment-gateway',
    dashboardUrlLabel: 'hdfcbank.com',
  },
  cybersource_hdfc: {
    dashboardImg: null,
    dashboardUrl: 'https://businesscenter.in.cybersource.com/ebc2/',
    dashboardUrlLabel: 'businesscenter.in.cybersource.com',
  },
  cybersource_axis: {
    dashboardImg: null,
    dashboardUrl: 'https://businesscenter.in.cybersource.com/ebc2/',
    dashboardUrlLabel: 'businesscenter.in.cybersource.com',
  },
  upi_icici: {
    dashboardImg: null,
    dashboardUrl: 'https://merchants.fiserv.com/india/',
    dashboardUrlLabel: 'merchants.fiserv.com',
  },
  netbanking_axis: {
    dashboardImg: null,
    dashboardUrl:
      'https://www.axisbank.com/business-banking/collection-solutions/internet-payment-gateway-solutions/overview',
    dashboardUrlLabel: 'axisbank.com',
  },
  optimizer_razorpay: {
    dashboardImg: require('assets/optimizer/razorpay-dashboard.png'),
    dashboardUrl: null,
    dashboardUrlLabel: null,
  },
  wallet_payzapp: {
    dashboardImg: null,
    dashboardUrl: 'https://www.hdfcbank.com/sme/pay/payments-and-collections/payzapp-for-business',
    dashboardUrlLabel: 'payzapp-for-business',
  },
  pay10: {
    dashboardImg: null,
    dashboardUrl: 'https://www.pay10.com/contact-us.php',
    dashboardUrlLabel: 'pay10.com',
  },
};

export const createMappedProviders = (terminalProviders) => {
  const mappedProviders = [];
  terminalProviders.forEach((provider) => {
    if (rzpGateways.includes(provider.Gateway)) {
      mappedProviders.push({
        id: provider.Gateway,
        name: provider.Provider_name,
        value: provider.Gateway,
      });
    } else if (provider?.Status === 'activated') {
      mappedProviders.push({
        id: `${provider.Gateway}_${provider.Terminal_id}`,
        name: provider.Provider_name || provider.Gateway,
        value: `${provider.Gateway}_${provider.Terminal_id}`,
      });
    }
  });
  return mappedProviders;
};

export const getSelectedProviderWithAcquirer = ({ providers, selectedProvider, provider }) => {
  if (selectedProvider) {
    return isEmpty(providers) || providers?.[selectedProvider]
      ? selectedProvider
      : `${provider?.Gateway}_${provider?.Gateway_acquirer}`;
  }
  return selectedProvider;
};

export const getOptimizerOnboardingStorageKey = (user) =>
  `optimizer-onboarding-banner-${user.current}`;

export const shouldShowRules = (user) => user?.isOptimizerEnabled;

export const WalletLabels = {
  itzcash: 'ItzCash',
  payzapp: 'PayZapp',
  olamoney: 'OlaMoney',
  jiomoney: 'JioMoney',
  amazonpay: 'Amazon Pay',
  phonepe: 'PhonePe',
  airtelmoney: 'Airtel Money',
  amexeasyclick: 'AMEX ezeClick',
  paycash: 'PayCash',
  citibankrewards: 'Citi Bank Reward Points',
  paytm: 'Paytm',
  mobikwik: 'MobiKwik',
  freecharge: 'Freecharge',
  oxigen: 'Oxigen',
};

export const filterProvidersByExperiment = (providers = {}, abExperiments) => {
  const result = {};

  for (const [gatewayKey, gatewayDetails] of Object.entries(providers)) {
    const experimentKey = `${gatewayKey}_gateway`;
    const experimentResult = abExperiments?.[experimentKey]?.variables?.result;

    if (experimentResult !== 'off') {
      result[gatewayKey] = cloneDeep(gatewayDetails);
    }
  }

  return result;
};
