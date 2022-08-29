export const DEFAULT_PRESET = 0; // Last 6 Hours
export const DEFAULT_INTERVAL = 60; // default 60 minutes
export const DEFAULT_ACTIVE_TAB = 'Overall';

export const DATE_RANGE_PRESETS = [
  ['Last 6 Hours', -6, 'hours'],
  ['Last 24 Hours', -24, 'hours'],
  ['Last 7 Days', -7, 'days'],
  ['Last 14 Days', -14, 'days'],
  ['Last 30 Days', -30, 'days'],
  ['Last 60 Days', -60, 'days'],
  ['Last 90 Days', -90, 'days'],
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
  Card: 'network',
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
};

export const TAG_OVERALL_MAP = {
  method: 'All payment methods',
  upi_provider: 'All apps',
  upi_type: 'All flows',
  network: 'All networks',
  issuer: 'All banks',
  type: 'All cards type',
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

export const tabsHelpTextMap = {
  Overall:
    'The success rate of a transaction is measured as a percentage based on the number of successful transactions divided by the total number of transactions.',
  UPI:
    'The success rate of a transaction is measured as a percentage based on the number of successful transactions divided by the total number of transactions.',
  Card:
    'The success rate of a transaction is measured as a percentage based on the number of successful transactions divided by the total number of transactions.',
  Netbanking:
    'The success rate of a transaction is measured as a percentage based on the number of successful transactions divided by the total number of transactions.',
};

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
    disabledText: 'Only available for minimum 6 hours till maximum 1 day (24 hours) duration',
    isEnabled: (startDate, endDate) => endDate.diff(startDate, 'days') <= 1,
  },
  daily: {
    value: 'daily',
    title: 'Daily',
    disabledText: 'Only available minimum 2 days to maximum 24 days duration',
    isEnabled: (startDate, endDate) => {
      const diff = endDate.diff(startDate, 'days');
      return diff > 1 && diff <= 14;
    },
  },
  weekly: {
    value: 'weekly',
    title: 'Weekly',
    disabledText: 'Available for minimum 14 days duration',
    isEnabled: (startDate, endDate) => {
      const diff = endDate.diff(startDate, 'days');
      return diff >= 14;
    },
  },
  monthly: {
    value: 'monthly',
    isEnabled: (startDate, endDate) => endDate.diff(startDate, 'days') >= 60,
    title: 'Monthly',
    disabledText: 'Available for minimum 60 days duration',
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
};

/**************************************** Graph methods ****************************************/

export const defaultTagStyle = {
  borderColor: namedColors['black.500'],
  backgroundColor: namedColors['black.400'],
};

export const tagStyles = [
  {
    borderColor: namedColors['black.500'],
    backgroundColor: namedColors['black.400'],
    borderStyle: 'dashed',
    borderWidth: '2px',
  },
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
    borderDash: [5, 5],
    pointBackgroundColor: namedColors['black.500'],
    pointBorderColor: namedColors['black.500'],
    pointHoverBackgroundColor: namedColors['black.500'],
    pointHoverBorderColor: namedColors['black.500'],
  },
  {
    ...defaultChartStyle,
    borderColor: namedColors['orange.500'],
    pointBackgroundColor: namedColors['orange.500'],
    pointBorderColor: namedColors['orange.500'],
    pointHoverBackgroundColor: namedColors['orange.500'],
    pointHoverBorderColor: namedColors['orange.500'],
  },
  {
    ...defaultChartStyle,
    borderColor: namedColors['blue.500'],
    pointBackgroundColor: namedColors['blue.500'],
    pointBorderColor: namedColors['blue.500'],
    pointHoverBackgroundColor: namedColors['blue.500'],
    pointHoverBorderColor: namedColors['blue.500'],
  },
  {
    ...defaultChartStyle,
    borderColor: namedColors['green.500'],
    pointBackgroundColor: namedColors['green.500'],
    pointBorderColor: namedColors['green.500'],
    pointHoverBackgroundColor: namedColors['green.500'],
    pointHoverBorderColor: namedColors['green.500'],
  },
  {
    ...defaultChartStyle,
    borderColor: namedColors['pink.500'],
    pointBackgroundColor: namedColors['pink.500'],
    pointBorderColor: namedColors['pink.500'],
    pointHoverBackgroundColor: namedColors['pink.500'],
    pointHoverBorderColor: namedColors['pink.500'],
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
    "Customer-related failures occur from the customer's side, like customer cancellations, incorrect CVV, insufficient funds.",
  [ERROR_CATEGORIES.BANKING]:
    'Banking-related failures occur due to issues at the customer’s bank, UPI app, wallets.',
  [ERROR_CATEGORIES.BUSINESS]:
    'Business-related failures occur due to the non-activation of payment methods, international payments.',
  [ERROR_CATEGORIES.OTHER]:
    'Other failures include errors due to fraud detection, internal provider issues.',
};

/******************************************************************************************/

/**************************************** TAB Filters Variables ****************************************/

export const CARD_GROUPING_DATA = [
  {
    value: 'type',
    text: 'Card Type',
  },
  {
    value: 'network',
    text: 'Card Networks',
  },
  {
    value: 'issuer',
    text: 'Banks',
  },
];

export const SR_FILTERS = {
  UPI: [],
  Card: [CARD_GROUPING_DATA],
  Netbanking: [],
};

/******************************************************************************************/
