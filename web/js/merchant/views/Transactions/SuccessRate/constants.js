export const DEFAULT_PRESET = 0; // Last 6 Hours
export const DEFAULT_INTERVAL = 60; // default 60 minutes
export const DEFAULT_ACTIVE_TAB = 'Overall';
export const INITIAL_SELECTED_CARD_TYPE = 'credit';
export const OPTIMIZER_FAQ_DOC_LINK = 'https://razorpay.com/docs/payments/optimizer/success-rate';

// Graph type
export const SCATTER = 'scatter';

// Tab names
export const OVERALL = 'Overall';
export const UPI = 'UPI';
export const CARD = 'Card';
export const NETBANKING = 'Netbanking';
export const EMANDATE = 'Emandate';
export const UPI_AUTOPAY = 'upi_autopay';
export const CARD_RECURRING = 'card_recurring';

// Graph axis ids for downtimes and sr
export const SR_X = 'sr_x';
export const SR_Y = 'sr_y';
export const DOWNTIME_X = 'downtime_x';
export const DOWNTIME_Y = 'downtime_y';

export const PRESETS = [
  { label: 'Last 6 Hours', name: '6h', value: 6, unit: 'hours' },
  { label: 'Last 24 Hours', name: '24h', value: 24, unit: 'hours' },
  { label: 'Last 7 Days', name: '7d', value: 7, unit: 'days' },
  { label: 'Last 14 Days', name: '14d', value: 14, unit: 'days' },
  { label: 'Last 30 Days', name: '30d', value: 30, unit: 'days' },
  { label: 'Last 60 Days', name: '60d', value: 60, unit: 'days' },
  { label: 'Last 90 Days', name: '90d', value: 90, unit: 'days' },
  { label: 'Custom Range', name: 'custom', value: 0, unit: '' },
];

export const DEFAULT_METHOD = {
  Overall: [
    {
      method: 'card',
      optimizerEnabled: true,
    },
    {
      method: 'upi',
      optimizerEnabled: true,
    },
    {
      method: 'netbanking',
      optimizerEnabled: true,
    },
    {
      method: 'emandate',
      optimizerEnabled: false,
    },
    {
      method: 'upi_autopay',
      optimizerEnabled: false,
    },
    {
      method: 'card_recurring',
      optimizerEnabled: false,
    },
  ],
  UPI: [
    {
      method: 'upi',
      optimizerEnabled: true,
    },
  ],
  Card: [
    {
      method: 'card',
      optimizerEnabled: true,
    },
  ],
  Netbanking: [
    {
      method: 'netbanking',
      optimizerEnabled: true,
    },
  ],
  Emandate: [
    {
      method: 'emandate',
      optimizerEnabled: false,
    },
  ],
  upi_autopay: [
    {
      method: 'upi_autopay',
      optimizerEnabled: false,
    },
  ],
  card_recurring: [
    {
      method: 'card_recurring',
      optimizerEnabled: false,
    },
  ],
};

export const DEFAULT_GROUP_BY = {
  Overall: 'method',
  UPI: 'upi_type',
  Card: 'network',
  Netbanking: 'bank',
  Emandate: 'bank',
  upi_autopay: 'amount_split',
  card_recurring: 'amount_split',
};

export const TAG_MAP = {
  upi: 'UPI',
  card: 'Cards',
  netbanking: 'Netbanking',
  emandate: 'Emandate',
  collect: 'Collect',
  intent: 'Intent',
  credit: 'Credit',
  debit: 'Debit',
  prepaid: 'Prepaid',
  others: 'Others',
  MasterCard: 'Mastercard',
  upi_autopay: 'UPI AutoPay',
  card_recurring: 'Cards Recurring',
};

export const TAG_OVERALL_MAP = {
  method: 'All payment methods',
  upi_provider: 'All apps',
  upi_type: 'All flows',
  network: 'All networks',
  issuer: 'All banks',
  type: 'All cards types',
  bank: 'All banks',
  international: 'Overall',
  amount_split: 'Overall',
};

/**************************************** Graph Widget Variables ****************************************/

export const tabsOrder = [
  {
    tab: 'Overall',
    optimizerEnabled: true,
  },
  {
    tab: 'UPI',
    optimizerEnabled: true,
  },
  {
    tab: 'Card',
    optimizerEnabled: true,
  },
  {
    tab: 'Netbanking',
    optimizerEnabled: true,
  },
  {
    tab: 'Emandate',
    optimizerEnabled: false,
  },
  {
    tab: 'upi_autopay',
    optimizerEnabled: false,
  },
  {
    tab: 'card_recurring',
    optimizerEnabled: false,
  },
];

export const tabsTitleMap = {
  Overall: 'All payment methods',
  UPI: 'UPI',
  Card: 'Cards',
  Netbanking: 'Netbanking',
  Emandate: 'Emandate',
  upi_autopay: 'UPI AutoPay',
  card_recurring: 'Cards Recurring',
};

export const METHOD_HELP_TEXT =
  'The percentage of attempted transactions that ended with a successful payment transfer for all payment methods. Calculated as successful payments/total attempted transactions.';

export const PAYMENT_METHOD_VS_CALLOUT_DISPLAY_TEXT = {
  Overall: 'any payment method',
  Card: 'Cards',
  UPI: 'UPI',
  Netbanking: 'Netbanking',
};

export const tabMeta = {
  name: '',
  data: null,
  error: null,
  fetched: false,
  lastUpdatedAt: null,
  histogram: { labels: [], datasets: [] },
  tags: [],
  group_by: [],
  selectedInterval: 'hourly',
  dropdownFilterOptions: [],
  selectedDropdownFilterOptions: [],
  selectedTags: [],
  downtimes: { resolved: [], ongoing: [] },
  failureReasonType: 'default',
  merchantErrors: {},
  selectedMethodType: null,
};

export const metricsCard = {
  name: '',
  title: '',
  helpText: '',
  sr: 0,
  successful: 0,
  total: 0,
  overviewHistogram: { labels: [], datasets: [] },
};

export const graphIntervals = {
  hourly: {
    value: 'hourly',
    title: 'Hourly',
    disabledText: 'Hourly data is available only if selected date range is less than 1 day',
    isEnabled: (startDate, endDate) => endDate.diff(startDate, 'days') <= 1,
  },
  daily: {
    value: 'daily',
    title: 'Daily',
    disabledText: 'Daily data is available only if selected date range is between 2 and 14 days',
    isEnabled: (startDate, endDate) => {
      const diff = endDate.diff(startDate, 'days');
      return diff > 1 && diff <= 14;
    },
  },
  weekly: {
    value: 'weekly',
    title: 'Weekly',
    disabledText: ' Weekly data is available only if selected date range is between 14 and 90 days',
    isEnabled: (startDate, endDate) => {
      const diff = endDate.diff(startDate, 'days');
      return diff >= 14;
    },
  },
  monthly: {
    value: 'monthly',
    isEnabled: (startDate, endDate) => endDate.diff(startDate, 'days') >= 60,
    title: 'Monthly',
    disabledText: ' Monthly data is available only if selected date range is more than 2 months',
  },
};

export const breakdownInterval = {
  hourly: 60, // 1hour ie., 60 mins
  daily: 24 * 60, // 1 day ie., 24 hours * 60 minutes
  weekly: 7 * 24 * 60, // 1 week ie., 7 days * 24 hours * 60 minutes
  monthly: 4 * 7 * 24 * 60, // 1 month ie., 4 weeks * 7 days * 24 hours * 60 minutes
};

/**************************************** color variables ****************************************/

export const gridLineColor = '#f0f3f7';
export const chartFontColor = '#2d303380';

export const namedColors = {
  'black.400': '#13264426',
  'black.500': '#132644',
  'orange.400': '#df870017',
  'orange.500': '#BD7A03',
  'blue.400': '#1566f117',
  'blue.500': '#2A86F3',
  'green.400': '#009c5c17',
  'green.500': '#01B358',
  'pink.400': '#ff00a826',
  'pink.500': '#FF00A8',
  'purple.400': '#EEDCFE',
  'purple.500': '#690392',
  'grey.800': '#818EA3',
};

/**************************************** Graph methods ****************************************/

export const defaultTagStyle = {
  color: namedColors['black.500'],
  backgroundColor: namedColors['black.400'],
};

export const tagStyles = [
  {
    color: namedColors['black.500'],
    backgroundColor: namedColors['black.400'],
    borderStyle: 'dashed',
    borderWidth: '2px',
  },
  {
    color: namedColors['orange.500'],
    backgroundColor: namedColors['orange.400'],
  },
  {
    color: namedColors['blue.500'],
    backgroundColor: namedColors['blue.400'],
  },
  {
    color: namedColors['green.500'],
    backgroundColor: namedColors['green.400'],
  },
  {
    color: namedColors['pink.500'],
    backgroundColor: namedColors['pink.400'],
  },
  {
    color: namedColors['purple.500'],
    backgroundColor: namedColors['purple.400'],
  },
];

/**************************************** Graph line chart ****************************************/

export const defaultChartStyle = {
  fill: false,
  borderWidth: 2,
  borderColor: namedColors['black.500'],
};

export const chartStyle = [
  {
    ...defaultChartStyle,
    borderColor: namedColors['black.500'],
    backgroundColor: namedColors['black.500'],
    borderDash: [5, 5],
  },
  {
    ...defaultChartStyle,
    borderColor: namedColors['orange.500'],
    backgroundColor: namedColors['orange.500'],
  },
  {
    ...defaultChartStyle,
    borderColor: namedColors['blue.500'],
    backgroundColor: namedColors['blue.500'],
  },
  {
    ...defaultChartStyle,
    borderColor: namedColors['green.500'],
    backgroundColor: namedColors['green.500'],
  },
  {
    ...defaultChartStyle,
    borderColor: namedColors['pink.500'],
    backgroundColor: namedColors['pink.500'],
  },
  {
    ...defaultChartStyle,
    borderColor: namedColors['purple.500'],
    backgroundColor: namedColors['purple.500'],
  },
];

/**************************************** Failure Error Widget Variables ****************************************/

export const ERROR_CATEGORIES = {
  CUSTOMER: 'customer',
  BANKING: 'bank',
  BUSINESS: 'business',
  OTHER: 'others',
};

export const ERROR_CATEGORIES_VS_DISPLAY_TEXT = {
  [ERROR_CATEGORIES.CUSTOMER]: 'Customer-related',
  [ERROR_CATEGORIES.BANKING]: 'Banking-related',
  [ERROR_CATEGORIES.BUSINESS]: 'Business-related',
  [ERROR_CATEGORIES.OTHER]: 'Other',
};

export const TOOLTIP_TEXT_VS_ERROR_CATEGORIES = {
  [ERROR_CATEGORIES.CUSTOMER]:
    'These payment failures may happen due to incorrect card details or OTP, insufficient bank balance, or payment cancellation by the customer.',
  [ERROR_CATEGORIES.BANKING]:
    'These failures happen due to technical or system issues at the customer’s bank end.',
  [ERROR_CATEGORIES.BUSINESS]:
    'These failures may happen due to technical issues from your end such as non-activation of a payment method, or international payments.',
  [ERROR_CATEGORIES.OTHER]:
    'These failures may happen due to provider or security issues such as fraud detections.',
};

export const CUSTOM_ERROR_TYPES = {
  Card: {
    key: 'international',
    name: 'International payments only',
    additionalCondition: (filters = []) => filters.some(({ value }) => value === 'international'),
    fetchOptions: {
      filters: {
        international: ['1'],
      },
      groupBy: {
        keys: ['international'],
      },
    },
  },
};

/******************************************************************************************/

/**************************************** TAB Filters Variables ****************************************/

export const CARD_GROUPING_DATA = [
  {
    value: 'network',
    text: 'Card Networks',
    query: 'filter',
  },
  {
    value: 'issuer',
    text: 'Banks',
    query: 'filter',
  },
  {
    value: 'international',
    text: 'Source',
    query: 'filter',
  },
];

export const DEFAULT_OPTIMIZER_FILTERS = {
  issuer: [
    {
      value: 'all_issuer',
      text: 'All banks',
      query: 'issuer',
    },
  ],
  network: [
    {
      value: 'all_network',
      text: 'All card networks',
      query: 'network',
    },
  ],
  type: [
    {
      value: 'all_type',
      text: 'All card types',
      query: 'type',
    },
  ],
  upi_type: [
    {
      value: 'all_upi_type',
      text: 'Intent and Collect',
      query: 'upi_type',
    },
  ],
  bank: [
    {
      value: 'all_bank',
      text: 'All banks',
      query: 'bank',
    },
  ],
};

const OPTIMIZER_CARD_TYPE_GROUPING_DATA = [
  {
    value: 'credit',
    text: 'Credit',
    query: 'type',
  },
  {
    value: 'debit',
    text: 'Debit',
    query: 'type',
  },
];

const OPTIMIZER_UPI_TYPE_GROUPING_DATA = [
  {
    value: 'intent',
    text: 'Intent Only',
    query: 'upi_type',
  },
  {
    value: 'collect',
    text: 'Collect Only',
    query: 'upi_type',
  },
];

export const STATIC_OPTIMIZER_FILTERS = {
  type: OPTIMIZER_CARD_TYPE_GROUPING_DATA,
  upi_type: OPTIMIZER_UPI_TYPE_GROUPING_DATA,
};

export const SR_FILTERS = {
  UPI: [],
  Card: [CARD_GROUPING_DATA],
  Netbanking: [],
};

export const TABS_WITH_OPTIMIZER_DROPDOWN_FILTERS = ['UPI', 'Card', 'Netbanking'];

export const TABS_VS_OPTIMIZER_GROUP_BY = {
  UPI: ['upi_type'],
  Card: ['type', 'network', 'issuer'],
  Netbanking: ['bank'],
};

export const FILTERS_VS_DISPLAY_NAMES = {
  intent: 'Intent Only',
  collect: 'Collect Only',
};

/******************************************************************************************/
export const DEFAULT_GROUP_BY_LIMIT = 4;

export const GROUP_BY_KEY_VS_LIMIT = {
  type: 3,
  upi_type: 2,
};

export const fetchDefaultReturn = {
  tags: [],
  selectedTags: [],
  histogram: { labels: [], datasets: [] },
};

export const METHOD_TYPES_MAP = {
  Card: {
    name: 'Card Types:',
    shouldRender: ({ user }) => !user.isOptimizerEnabled,
    defaultType: 'credit',
    defaultRecurringType: null,
    viewType: 'btn-group',
    types: [
      {
        name: 'Credit',
        value: 'credit',
        shouldRender: () => true,
      },
      {
        name: 'Debit',
        value: 'debit',
        shouldRender: () => true,
      },
      {
        name: 'Prepaid',
        value: 'prepaid',
        shouldRender: () => true,
      },
    ],
    recurringFilterName: null,
    recurringTypes: [],
  },
  upi_autopay: {
    name: 'Mandate Type:',
    shouldRender: ({ user }) => !user.isOptimizerEnabled,
    defaultType: null,
    defaultRecurringType: 'auto',
    viewType: 'btn-group',
    types: [],
    recurringFilterName: 'Mandate Type:',
    recurringTypes: [
      {
        name: 'Debit',
        value: 'auto',
        shouldRender: () => true,
      },
      {
        name: 'Creation',
        value: 'initial',
        shouldRender: () => true,
      },
    ],
  },
  card_recurring: {
    name: 'Card Types:',
    shouldRender: ({ user }) => !user.isOptimizerEnabled,
    defaultType: 'credit',
    defaultRecurringType: 'auto,initial',
    viewType: 'drop-down',
    types: [
      {
        name: 'Credit',
        value: 'credit',
        shouldRender: () => true,
      },
      {
        name: 'Debit',
        value: 'debit',
        shouldRender: () => true,
      },
    ],
    recurringFilterName: 'Mandate type:',
    recurringTypes: [
      {
        name: 'Creation and Auto Debit',
        value: 'auto,initial',
        shouldRender: () => true,
      },
      {
        name: 'Creation',
        value: 'initial',
        shouldRender: () => true,
      },
      {
        name: 'Auto Debit',
        value: 'auto',
        shouldRender: () => true,
      },
    ],
  },
};

export const CARD_NETWORKS = {
  AMEX: 'American Express',
  VISA: 'Visa',
  MC: 'Master Card',
  RUPAY: 'RuPay',
  DICL: 'Diners Club',
};
