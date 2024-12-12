import { SpiltzContextState } from 'common/splitz/types';
import { isExperimentEnabled } from 'common/splitz/utils';
import { Parameter, Operator, LogicalOperator, Rule, RuleGroup, Provider } from './types';

export const OPERATORS: Operator[] = [
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

export const LOGICAL_OPERATORS: LogicalOperator[] = [
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

export const PARAMETERS: Parameter[] = [
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
      {
        value: 'emandate',
      },
      {
        value: 'paylater',
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
    id: 3,
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
    id: 4,
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
      {
        value: 'pluxee',
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
    id: 5,
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
    id: 6,
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
    id: 10,
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
    name: 'Custom Identifier 1',
    value: '$payment.optimizer_identifier_1',
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
    name: 'Custom Identifier 2',
    value: '$payment.optimizer_identifier_2',
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
  {
    name: 'Custom Identifier 3',
    value: '$payment.optimizer_identifier_3',
    description: 'Custom Identifier',
    id: 13,
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
    name: 'EMI Duration',
    value: '$payment.optimizer_emi_duration',
    description: 'In months',
    id: 14,
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
  {
    name: 'Wallets',
    value: '$payment.optimizer_wallet',
    description: 'Freecharge, olamoney, mobikwik',
    id: 15,
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
  {
    name: 'International',
    value: '$payment.optimizer_international',
    description: 'International payment',
    id: 16,
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
    name: 'Currency',
    value: '$payment.optimizer_currency',
    description: 'INR, USD',
    id: 17,
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
  {
    name: 'Emandate Authentication Type',
    value: '$payment.optimizer_token_auth_type',
    description: 'Netbanking, Debit Card, Aadhaar',
    id: 18,
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
  {
    name: 'Paylater Provider',
    value: '$payment.optimizer_wallet',
    description: 'Simpl',
    id: 19,
    values: [
      {
        value: 'getsimpl',
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

export const SMART_ROUTER = 'smart_router';
export const RZP_GATEWAYS = ['razorpay', 'smart_router'];

export const DEFAULT_RULE = 'Default Rule';
export const TOTAL_RULE_LIMIT = 25;

export const OPTIMIZER_SVGS = {
  checkCircle: require('assets/optimizer/check_circle.svg'),
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
  phonepe: require('assets/optimizer/phonepe.svg'),
  getsimpl_optimizer: getLogoPath('simpl', 'svg'),
  netbanking_icici: getLogoPath('icici'),
  netbanking_hdfc: getLogoPath('hdfc'),
  zaakpay: getLogoPath('zaakpay', 'svg'),
};

export const getValue = (type, value, paramValue = '') => {
  let r;
  if (type == 'parameter') {
    r = PARAMETERS;
  }
  if (type == 'operator') {
    r = OPERATORS;
  }
  if (paramValue?.includes('getsimpl')) {
    return r.find((p) => p.value === value && p.name === 'Paylater Provider') || { name: '' };
  }
  return r.find((p) => p.value == value) || { name: '' };
};

export const getUnique = () => Math.floor(Math.random() * 100000000000);

export const createMappedProviders = (terminalProviders) => {
  const mappedProviders: {
    id: string;
    name: string;
    value: string;
  }[] = [];
  terminalProviders.forEach((provider) => {
    if (RZP_GATEWAYS.includes(provider.Gateway)) {
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

export const isExpressionValid = (expression) => {
  return (
    expression.operands &&
    expression.operands[0] &&
    expression.operands[0].value &&
    expression.operands[1].value &&
    expression.value
  );
};

export const mapRulesArrayToObject = (rules: Rule[]): { [key: string]: Rule[] } => {
  const rulesObj: { [key: string]: Rule[] } = {};
  rules.forEach((r) => {
    const provider_priority = r.additional_attribute[0].value;
    if (!rulesObj[provider_priority]) {
      rulesObj[provider_priority] = [];
    }
    rulesObj[provider_priority].push(r);
  });
  return rulesObj;
};

export const mapRulesObjectToArray = (rulesObj: { [key: string]: Rule[] }): Rule[] => {
  const rules: Rule[] = [];
  Object.keys(rulesObj).forEach((key) => {
    rules.push(...rulesObj[key]);
  });
  return rules;
};

export const appendMid = (value: string): string => {
  return `${value}_${window.rzp_user.current}`;
};

export const removeMid = (value: string): string => {
  const temp = value.split('_');
  temp.pop();
  return temp.join('_');
};

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

export const getRuleStatus = (ruleGroup: RuleGroup): string => {
  const status = ruleGroup.additional_attributes[0].values[0] || 'test';
  return status;
};

export const liveRulesList = (rules: RuleGroup[]): RuleGroup[] => {
  return rules.filter((r) => getRuleStatus(r) === 'live');
};

export const uniqueArray = (arr) => {
  const o = {};
  const a: any[] = [];
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

export const findProviderName = (providers: Provider[], id: string): string => {
  if (id === 'razorpay') {
    return 'razorpay';
  }
  const provider = providers?.filter((p) => p.Terminal_id === id);
  return provider.length > 0 ? provider[0].Provider_name : id;
};

// This helps adding ticket into Mission Integrations queue
export const FD_TICKET_GROUP_ID = 82000661025;

export const isSMEOnboardingEnabled = (splitz: SpiltzContextState): boolean => {
  const { abExperiments } = splitz || { abExperiments: { opti_sme_onboarding: undefined } };
  if (!abExperiments?.opti_sme_onboarding) return false;
  return isExperimentEnabled(abExperiments.opti_sme_onboarding);
};
