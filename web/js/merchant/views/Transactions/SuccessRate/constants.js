export const DEFAULT_ACTIVE_TAB = 'Overall';
export const DEFAULT_PRESET = 1;
export const DEFAULT_INTERVAL = 1440;

export const DATE_RANGE_PRESETS = [
  ['Last 6 Hours', -6, 'hours'],
  ['Last 24 Hours', -24, 'hours'],
  ['Last 7 Days', -7, 'days'],
  ['Last 14 Days', -14, 'days'],
  ['Last 30 Days', -30, 'days'],
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
  UPI: 'upi_provider',
  Card: 'network',
  Netbanking: 'bank',
};

/**************************************** Graph Widget Variables ****************************************/

export const tabsOrder = ['Overall', 'UPI', 'Card', 'Netbanking'];

export const tabsTitleMap = {
  Overall: 'Overall Success Rate',
  UPI: 'UPI Success Rate',
  Card: 'Card Success Rate',
  Netbanking: 'Netbanking Success Rate',
};

export const tabsHelpTextMap = {
  Overall: '',
  UPI: '',
  Card: '',
  Netbanking: '',
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
  selectedInterval: 'daily',
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
    isEnabled: false,
    title: 'Hourly',
    disabledText: 'Only available for minimum 6 hours till maximum 1 day (24 hours) duration',
  },
  daily: {
    value: 'daily',
    isEnabled: true,
    title: 'Daily',
    disabledText: 'Only available minimum 2 days to maximum 24 days duration',
  },
  weekly: {
    value: 'weekly',
    isEnabled: false,
    title: 'Weekly',
    disabledText: 'Available for minimum 14 days duration',
  },
  monthly: {
    value: 'monthly',
    isEnabled: false,
    title: 'Monthly',
    disabledText: 'Available for minimum 60 days duration',
  },
};

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

export const defaultChartStyle = {
  fill: false,
  borderWidth: 2,
  lineTension: 0.1,
  borderColor: namedColors['black.500'],
};

export const defaultLineStyle = {
  ...defaultChartStyle,
  borderColor: namedColors['black.500'],
};

export const chartStyle = [
  {
    ...defaultChartStyle,
    borderColor: namedColors['black.500'],
    borderDash: [10, 2],
  },
  {
    ...defaultChartStyle,
    borderColor: namedColors['orange.500'],
  },
  {
    ...defaultChartStyle,
    borderColor: namedColors['blue.500'],
  },
  {
    ...defaultChartStyle,
    borderColor: namedColors['green.500'],
  },
  {
    ...defaultChartStyle,
    borderColor: namedColors['pink.500'],
  },
];

/*********************************************************************************************************/

/**************************************** Failure Error Widget Variables ****************************************/

export const ERROR_CATEGORIES = {
  CUSTOMER: 'customer',
  OTHER: 'others',
  BUSINESS: 'business',
  BANKING: 'bank',
};

export const ERROR_CATEGORIES_VS_DISPLAY_TEXT = {
  [ERROR_CATEGORIES.CUSTOMER]: 'Customer drop-offs',
  [ERROR_CATEGORIES.OTHER]: 'Other failures',
  [ERROR_CATEGORIES.BUSINESS]: 'Business failures',
  [ERROR_CATEGORIES.BANKING]: 'Banking failures',
};

export const TOOLTIP_TEXT_VS_ERROR_CATEGORIES = {
  [ERROR_CATEGORIES.CUSTOMER]:
    "Customer drop-offs are failures that occur from the customer's side, like customer cancellations, incorrect CVV, insufficient funds etc.",
  [ERROR_CATEGORIES.OTHER]:
    'Other failures include errors due to fraud detection, internal provider issues etc.',
  [ERROR_CATEGORIES.BUSINESS]:
    'Business failures occur due to the non-activation of payment methods, international payments etc.',
  [ERROR_CATEGORIES.BANKING]:
    'Banking failures occur due to issues at the customer’s bank, UPI app, wallets, etc.',
};

/******************************************************************************************/
