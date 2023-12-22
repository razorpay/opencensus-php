const FORM_ITEMS_WITH_LATE_PAYMENT = [
  {
    item: { name: 'Amount' },
    min_amount: 1,
    settings: { position: 0 },
    mandatory: true,
  },
  {
    name: 'pri__ref__id',
    title: 'Primary Reference ID',
    required: true,
    type: 'string',
    pattern: 'alphanumeric',
    options: {},
    settings: { position: 1 },
  },
  {
    name: 'sec__ref__id_1',
    title: 'Secondary Reference ID',
    required: true,
    type: 'string',
    pattern: 'alphanumeric',
    options: {},
    settings: { position: 2 },
  },
  {
    name: 'email',
    required: true,
    title: 'Email',
    type: 'string',
    pattern: 'email',
    settings: { position: 3 },
  },
  {
    name: 'phone',
    title: 'Phone',
    required: true,
    type: 'number',
    pattern: 'phone',
    minLength: '8',
    options: {},
    settings: { position: 4 },
  },
  {
    item: { name: 'Late Payment Charges' },
    settings: {
      late_fee_config: '{"late_fee_order":1,"late_fee_type":"flat_late_fee"}',
      position: 5,
    },
    mandatory: false,
  },
];
const FORM_ITEMS_WITH_LATE_PAYMENT_AND_DUE_DATE = [
  {
    item: { name: 'Amount' },
    min_amount: 1,
    settings: { position: 0 },
    mandatory: true,
  },
  {
    name: 'pri__ref__id',
    title: 'Primary Reference ID',
    required: true,
    type: 'string',
    pattern: 'alphanumeric',
    options: {},
    settings: { position: 1 },
  },
  {
    name: 'sec__ref__id_1',
    title: 'Secondary Reference ID',
    required: true,
    type: 'string',
    pattern: 'alphanumeric',
    options: {},
    settings: { position: 2 },
  },
  {
    name: 'email',
    required: true,
    title: 'Email',
    type: 'string',
    pattern: 'email',
    settings: { position: 3 },
  },
  {
    name: 'phone',
    title: 'Phone',
    required: true,
    type: 'number',
    pattern: 'phone',
    minLength: '8',
    options: {},
    settings: { position: 4 },
  },
  {
    item: { name: 'Late Payment Charges' },
    settings: {
      late_fee_config: '{"late_fee_order":1,"late_fee_type":"flat_late_fee"}',
      position: 5,
    },
    mandatory: false,
  },
  {
    name: 'late__fee__due__date_1',
    title: 'Late Payment Due Date',
    required: false,
    type: 'string',
    pattern: 'date',
    options: { cmp: 'date' },
  },
];

const FORM_ITEMS_WITH_OUT_LATE_PAYMENT = [
  {
    item: {
      name: 'Amount',
    },
    min_amount: 1,
    settings: {
      position: 0,
    },
    mandatory: true,
  },
  {
    name: 'pri__ref__id',
    title: 'Primary Reference ID',
    required: true,
    type: 'string',
    pattern: 'alphanumeric',
    options: {},
    settings: {
      position: 1,
    },
  },
  {
    name: 'sec__ref__id_1',
    title: 'Secondary Reference ID',
    required: true,
    type: 'string',
    pattern: 'alphanumeric',
    options: {},
    settings: {
      position: 2,
    },
  },
  {
    name: 'email',
    required: true,
    title: 'Email',
    type: 'string',
    pattern: 'email',
    settings: {
      position: 3,
    },
  },
  {
    name: 'phone',
    title: 'Phone',
    required: true,
    type: 'number',
    pattern: 'phone',
    minLength: '8',
    options: {},
    settings: {
      position: 4,
    },
  },
];

export {
  FORM_ITEMS_WITH_LATE_PAYMENT,
  FORM_ITEMS_WITH_LATE_PAYMENT_AND_DUE_DATE,
  FORM_ITEMS_WITH_OUT_LATE_PAYMENT,
};
