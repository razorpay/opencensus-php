export const DEFAULT_PRESET = 0; // Last 6 Hours
export const DEFAULT_INTERVAL = 60; // default 60 minutes
export const DEFAULT_ACTIVE_TAB = 'Overall';

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
  Overall: ['card', 'upi', 'netbanking'],
  UPI: ['upi'],
  Card: ['card'],
  Netbanking: ['netbanking'],
};

export const DEFAULT_GROUP_BY = {
  Overall: 'method',
  UPI: 'upi_type',
  Card: 'type',
  Netbanking: 'bank',
};

export const TAG_MAP = {
  upi: 'UPI',
  card: 'Cards',
  netbanking: 'Netbanking',
  collect: 'Collect',
  intent: 'Intent',
  credit: 'Credit',
  debit: 'Debit',
  prepaid: 'Prepaid',
  others: 'Others',
  MasterCard: 'Mastercard',
};

export const TAG_OVERALL_MAP = {
  method: 'All payment methods',
  upi_provider: 'All apps',
  upi_type: 'All flows',
  network: 'All networks',
  issuer: 'All banks',
  type: 'All cards types',
  bank: 'All banks',
};

/**************************************** Graph Widget Variables ****************************************/

export const tabsOrder = ['Overall', 'UPI', 'Card', 'Netbanking'];

export const tabsTitleMap = {
  Overall: 'All payment methods',
  UPI: 'UPI',
  Card: 'Cards',
  Netbanking: 'Netbanking',
};

export const METHOD_HELP_TEXT =
  'The percentage of attempted transactions that ended with a successful payment transfer for all payment methods. Calculated as successful payments/total attempted transactions.';

export const tabMeta = {
  name: '',
  data: null,
  error: null,
  fetched: false,
  lastUpdated: null,
  histogram: { labels: [], datasets: [] },
  tags: [],
  group_by: [],
  selectedInterval: 'hourly',
  dropdownFilterOptions: [],
  selectedDropdownFilterOptions: [],
  selectedTags: [],
};

export const metricsCard = {
  name: '',
  title: '',
  helpText: '',
  sr: '',
  successful: '',
  total: '',
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

/**************************************** Volume pie chart ****************************************/

export const defaultPieChartStyle = {
  borderColor: namedColors['black.500'],
  backgroundColor: namedColors['black.400'],
};

export const pieChartStyle = [
  {
    borderColor: namedColors['orange.500'],
    backgroundColor: namedColors['orange.400'],
  },
  {
    borderColor: namedColors['blue.500'],
    backgroundColor: namedColors['blue.400'],
  },
  {
    borderColor: namedColors['green.500'],
    backgroundColor: namedColors['green.400'],
  },
  {
    borderColor: namedColors['pink.500'],
    backgroundColor: namedColors['pink.400'],
  },
  {
    borderColor: namedColors['purple.500'],
    backgroundColor: namedColors['purple.400'],
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

/******************************************************************************************/

/**************************************** TAB Filters Variables ****************************************/

export const CARD_GROUPING_DATA = [
  {
    value: 'type',
    text: 'Card Types',
    query: 'filter',
  },
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

export const SR_FILTERS = {
  UPI: [],
  Card: [CARD_GROUPING_DATA],
  Netbanking: [],
};

export const TABS_WITH_OPTIMIZER_DROPDOWN_FILTERS = ['UPI', 'Card', 'Netbanking'];

export const TABS_VS_OPTIMIZER_GROUP_BY = {
  Card: ['type', 'network', 'issuer'],
  Netbanking: ['bank'],
  UPI: ['upi_type'],
};

export const FILTERS_VS_DISPLAY_NAMES = {
  intent: 'Intent Only',
  collect: 'Collect Only',
};

/******************************************************************************************/
