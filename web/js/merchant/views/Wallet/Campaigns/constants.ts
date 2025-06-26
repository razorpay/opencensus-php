import { AttributeOperatorOption, PrimitiveAttributeTypes } from './types';

export const COMPARISON_OPERATORS = {
  EQUAL_TO: 'equal_to',
  GREATER_THAN: 'greater_than',
  GREATER_THAN_OR_EQUAL: 'greater_than_or_equal',
  LESS_THAN: 'less_than',
  LESS_THAN_OR_EQUAL: 'less_than_or_equal',
  IS_BETWEEN: 'is_between',
  CONTAINS: 'contains',
  CONTAINS_ALL: 'contains_all',
  CONTAINS_ANY: 'contains_any',
  IS: 'is',
};

export const ATTRIBUTE_TYPE_OPERATORS: Record<PrimitiveAttributeTypes, AttributeOperatorOption[]> =
  {
    string: [
      { value: 'equal_to', label: 'is equal to', rule: '=' },
      { value: 'contains', label: 'contains', rule: 'in' },
    ],
    int: [
      { value: 'equal_to', label: 'is equal to', rule: '=' },
      { value: 'greater_than', label: 'is greater than', rule: '>' },
      { value: 'greater_than_or_equal', label: 'is greater than or equal to', rule: '>=' },
      { value: 'less_than', label: 'is less than', rule: '<' },
      { value: 'less_than_or_equal', label: 'is less than or equal to', rule: '<=' },
      { value: 'is_between', label: 'is between', rule: '>= && <=' },
    ],
    int64: [
      { value: 'equal_to', label: 'is equal to', rule: '=' },
      { value: 'greater_than', label: 'is greater than', rule: '>' },
      { value: 'greater_than_or_equal', label: 'is greater than or equal to', rule: '>=' },
      { value: 'less_than', label: 'is less than', rule: '<' },
      { value: 'less_than_or_equal', label: 'is less than or equal to', rule: '<=' },
      { value: 'is_between', label: 'is between', rule: '>= && <=' },
    ],
    uint: [
      { value: 'equal_to', label: 'is equal to', rule: '=' },
      { value: 'greater_than', label: 'is greater than', rule: '>' },
      { value: 'greater_than_or_equal', label: 'is greater than or equal to', rule: '>=' },
      { value: 'less_than', label: 'is less than', rule: '<' },
      { value: 'less_than_or_equal', label: 'is less than or equal to', rule: '<=' },
      { value: 'is_between', label: 'is between', rule: '>= && <=' },
    ],
    uint64: [
      { value: 'equal_to', label: 'is equal to', rule: '=' },
      { value: 'greater_than', label: 'is greater than', rule: '>' },
      { value: 'greater_than_or_equal', label: 'is greater than or equal to', rule: '>=' },
      { value: 'less_than', label: 'is less than', rule: '<' },
      { value: 'less_than_or_equal', label: 'is less than or equal to', rule: '<=' },
      { value: 'is_between', label: 'is between', rule: '>= && <=' },
    ],
    float: [
      { value: 'equal_to', label: 'is equal to', rule: '=' },
      { value: 'greater_than', label: 'is greater than', rule: '>' },
      { value: 'greater_than_or_equal', label: 'is greater than or equal to', rule: '>=' },
      { value: 'less_than', label: 'is less than', rule: '<' },
      { value: 'less_than_or_equal', label: 'is less than or equal to', rule: '<=' },
      { value: 'is_between', label: 'is between', rule: '>= && <=' },
    ],
    boolean: [{ value: 'is', label: 'is', rule: '=' }],
    array: [
      { value: 'contains_all', label: 'contains all', rule: 'contains_all' },
      { value: 'contains_all', label: 'contains any', rule: 'contains_all' },
    ],
  };

export const ATTRIBUTE_HELP_TEXT_MAPPING = {
  [COMPARISON_OPERATORS.CONTAINS_ALL]: 'Use commas to enter multiple values',
  [COMPARISON_OPERATORS.CONTAINS_ANY]: 'Use commas to enter multiple values',
  [COMPARISON_OPERATORS.CONTAINS]: 'Use commas to enter multiple values',
};

export const TRIGGER_ACTIONS = {
  WALLET_CREDIT: { label: 'Credit Wallet' },
};

export const DURATION_PRESETS = [
  { label: 'Days', value: 'days' },
  { label: 'Months', value: 'months' },
  { label: 'Years', value: 'years' },
];

export const USAGE_LIMIT_DURATION_PRESETS = [
  { label: 'Daily', value: 'DAILY' },
  { label: 'Weekly', value: 'CALENDAR_WEEKLY' },
  { label: 'Monthly', value: 'CALENDAR_MONTHLY' },
  { label: 'Yearly', value: 'CALENDAR_YEARLY' },
  { label: 'Till Campaign Ends', value: 'TOTAL' },
];

export const TIME_PRESETS = [
  {
    label: '12:00 AM',
    value: '00:00',
  },
  {
    label: '12:30 AM',
    value: '00:30',
  },
  {
    label: '01:00 AM',
    value: '01:00',
  },
  {
    label: '01:30 AM',
    value: '01:30',
  },
  {
    label: '02:00 AM',
    value: '02:00',
  },
  {
    label: '02:30 AM',
    value: '02:30',
  },
  {
    label: '03:00 AM',
    value: '03:00',
  },
  {
    label: '03:30 AM',
    value: '03:30',
  },
  {
    label: '04:00 AM',
    value: '04:00',
  },
  {
    label: '04:30 AM',
    value: '04:30',
  },
  {
    label: '05:00 AM',
    value: '05:00',
  },
  {
    label: '05:30 AM',
    value: '05:30',
  },
  {
    label: '06:00 AM',
    value: '06:00',
  },
  {
    label: '06:30 AM',
    value: '06:30',
  },
  {
    label: '07:00 AM',
    value: '07:00',
  },
  {
    label: '07:30 AM',
    value: '07:30',
  },
  {
    label: '08:00 AM',
    value: '08:00',
  },
  {
    label: '08:30 AM',
    value: '08:30',
  },
  {
    label: '09:00 AM',
    value: '09:00',
  },
  {
    label: '09:30 AM',
    value: '09:30',
  },
  {
    label: '10:00 AM',
    value: '10:00',
  },
  {
    label: '10:30 AM',
    value: '10:30',
  },
  {
    label: '11:00 AM',
    value: '11:00',
  },
  {
    label: '11:30 AM',
    value: '11:30',
  },
  {
    label: '12:00 PM',
    value: '12:00',
  },
  {
    label: '12:30 PM',
    value: '12:30',
  },
  {
    label: '01:00 PM',
    value: '13:00',
  },
  {
    label: '01:30 PM',
    value: '13:30',
  },
  {
    label: '02:00 PM',
    value: '14:00',
  },
  {
    label: '02:30 PM',
    value: '14:30',
  },
  {
    label: '03:00 PM',
    value: '15:00',
  },
  {
    label: '03:30 PM',
    value: '15:30',
  },
  {
    label: '04:00 PM',
    value: '16:00',
  },
  {
    label: '04:30 PM',
    value: '16:30',
  },
  {
    label: '05:00 PM',
    value: '17:00',
  },
  {
    label: '05:30 PM',
    value: '17:30',
  },
  {
    label: '06:00 PM',
    value: '18:00',
  },
  {
    label: '06:30 PM',
    value: '18:30',
  },
  {
    label: '07:00 PM',
    value: '19:00',
  },
  {
    label: '07:30 PM',
    value: '19:30',
  },
  {
    label: '08:00 PM',
    value: '20:00',
  },
  {
    label: '08:30 PM',
    value: '20:30',
  },
  {
    label: '09:00 PM',
    value: '21:00',
  },
  {
    label: '09:30 PM',
    value: '21:30',
  },
  {
    label: '10:00 PM',
    value: '22:00',
  },
  {
    label: '10:30 PM',
    value: '22:30',
  },
  {
    label: '11:00 PM',
    value: '23:00',
  },
  {
    label: '11:30 PM',
    value: '23:30',
  },
];

export const CAMPAIGN_STATUS = {
  ACTIVE: {
    label: 'Active',
    value: 'ACTIVE',
    color: 'positive',
  },
  INACTIVE: {
    label: 'Paused',
    value: 'INACTIVE',
    color: 'neutral',
  },
  TERMINATED: {
    label: 'Campaign Ended',
    value: 'TERMINATED',
    color: 'neutral',
  },
  /**Frontend only states */
  SCHEDULED: {
    label: 'Scheduled',
    value: 'SCHEDULED',
    color: 'information',
  },
  COMPLETED: {
    label: 'Completed',
    value: 'COMPLETED',
    color: 'neutral',
  },
} as const;

export const NUMBER_TYPES = [
  'int',
  'int64',
  'uint',
  'float',
  'float64',
  'float32',
  'uint64',
  'uint32',
  'int32',
  'int8',
  'uint8',
  'int16',
  'uint16',
  'double',
];
export const BOOLEAN_TYPES = ['boolean'];

export const CONSTANT_ACTION_CONFIGS = {
  CREDIT_WALLET: {
    type: {
      type: 'constant',
      value: 'credit',
    },
    notes: {
      type: 'dynamic',
      value: 'notes',
    },
    action: {
      type: 'constant',
      value: 'load',
    },
    method: {
      type: 'constant',
      value: 'wallet',
    },
    user_id: {
      type: 'dynamic',
      value: 'user_id',
    },
    currency: {
      type: 'constant',
      value: 'INR',
    },
    description: {
      type: 'constant',
      value: '',
    },
  },
  ORDER_PLACED: {
    user_id: {
      type: 'dynamic',
      value: 'order.customer_details.id',
    },
    mobile: {
      type: 'dynamic',
      value: 'order.customer_details.contact',
    },
    email: {
      type: 'dynamic',
      value: 'order.customer_details.email',
    },
    partner_user_id: {
      type: 'dynamic',
      value: 'order.customer_details.external_customer_id',
    },
    currency: { type: 'constant', value: 'INR' },
    type: { type: 'constant', value: 'credit' },
    action: { type: 'constant', value: 'load' },
    method: { type: 'constant', value: 'wallet' },
  },
  ORDER_FULFILLED: {
    type: {
      type: 'constant',
      value: 'credit',
      is_points_field: false,
    },
    email: {
      type: 'dynamic',
      value: 'shopify.order.customer_details.email',
      is_points_field: false,
    },
    action: {
      type: 'constant',
      value: 'load',
      is_points_field: false,
    },
    method: {
      type: 'constant',
      value: 'wallet',
      is_points_field: false,
    },
    mobile: {
      type: 'dynamic',
      value: 'shopify.order.customer_details.contact',
      is_points_field: false,
    },
    user_id: {
      type: 'dynamic',
      value: 'shopify.order.customer_details.id',
      is_points_field: false,
    },
    currency: {
      type: 'constant',
      value: 'INR',
      is_points_field: false,
    },
    partner_user_id: {
      type: 'dynamic',
      value: 'shopify.order.customer_details.external_customer_id',
      is_points_field: false,
    },
  },
};

export const CAMPAIGN_STATUS_CONFIRMATION_MESSAGES = {
  [CAMPAIGN_STATUS.INACTIVE.value]: {
    title: 'Would you like to pause this campaign?',
    message:
      'This will stop crediting points to wallets. You can resume the campaign at a later time.',
    action: 'Pause Campaign',
  },
  [CAMPAIGN_STATUS.TERMINATED.value]: {
    title: 'Would you like to end this campaign?',
    message: 'This will stop crediting points to wallets. This action cannot be undone.',
    action: 'End Campaign',
  },
  [CAMPAIGN_STATUS.ACTIVE.value]: {
    title: 'Would you like to resume this campaign?',
    message: 'Once resumed, your customers will start earning points in their wallets',
    action: 'Resume Campaign',
  },
};

export const AMOUNT_ATTRIBUTES = [
  'credit',
  'debit',
  'amount',
  'balance',
  'line_items_total',
  'price',
  'offer_price',
  'tax_amount',
];
