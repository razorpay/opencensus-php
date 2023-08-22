export const DEFAULT_PRESET = 0; // Last 6 Hours
export const DEFAULT_INTERVAL = 'hourly'; // default hourly

// option for preset dropdown in date filter
export const PRESETS = [
  { label: 'Last 6 Hours', name: '6h', value: 6, unit: 'hours' },
  { label: 'Last 24 Hours', name: '24h', value: 24, unit: 'hours' },
  { label: 'Last 7 Days', name: '7d', value: 7, unit: 'days' },
  { label: 'Last 14 Days', name: '14d', value: 14, unit: 'days' },
  { label: 'Last 30 Days', name: '30d', value: 30, unit: 'days' },
  { label: 'Custom Range', name: 'custom', value: 0, unit: '' },
];

// intervals for graph
export const graphIntervals = {
  hourly: {
    value: 'hourly',
    title: 'Hourly',
    disabledText: 'Hourly data is available only if selected date range is less than 1 day',
    isEnabled: (startDate: moment.Moment, endDate: moment.Moment) =>
      endDate.diff(startDate, 'days') <= 1,
  },
  daily: {
    value: 'daily',
    title: 'Daily',
    disabledText: 'Daily data is available only if selected date range is between 2 and 14 days',
    isEnabled: (startDate: moment.Moment, endDate: moment.Moment) => {
      const diff = endDate.diff(startDate, 'days');
      return diff > 1 && diff <= 14;
    },
  },
  weekly: {
    value: 'weekly',
    title: 'Weekly',
    disabledText: ' Weekly data is available only if selected date range is between 14 and 30 days',
    isEnabled: (startDate: moment.Moment, endDate: moment.Moment) => {
      const diff = endDate.diff(startDate, 'days');
      return diff >= 14;
    },
  },
};

export const GRAPH_INTERVALS_MAP = {
  hourly: 'hourly',
  daily: 'daily',
  weekly: 'weekly',
};

export const DEFAULT_LOADING_ERROR_TITLE = 'No data available';
export const DEFAULT_LOADING_ERROR_DESCRIPTION =
  'Tip: You could try again by selecting a different filter or date range.';
// CHART RELATED CONSTANTS //

export const CHART_NAME_MAP = {
  OVERALL_CR: 'checkout_overall_cr',
  METHOD_LEVEL_CR: 'checkout_method_level_cr',
  INDUSTRY_OVERALL_CR: 'checkout_industry_level_cr',
};

export const CHART_INITIAL_DATA = {
  [CHART_NAME_MAP.OVERALL_CR]: { isLoading: false, error: '', datasets: [] },
  [CHART_NAME_MAP.METHOD_LEVEL_CR]: { isLoading: false, error: '', datasets: [] },
  [CHART_NAME_MAP.INDUSTRY_OVERALL_CR]: { isLoading: false, error: '', datasets: [] },
};

export const GRAPHS_DATA = {
  OVERALL_CR: {
    name: 'Overall CR',
    title: 'Overall Conversion rate',
    description:
      'The percentage of payments submitted out of all attempted payments as a trending line chart',
    xLabel: 'Time in',
    yLabel: 'Overall CR',
    xAxisID: 'overall_cr_x',
    yAxisID: 'overall_cr_y',
  },
  METHOD_LEVEL_CR: {
    name: 'Method Level CR',
    title: 'Method Level Conversion rate',
    description:
      'he percentage of payments submitted out of all attempted payments for each individual payment method (e.g., credit cards, digital wallets, UPI etc)',
    xLabel: 'Time in',
    yLabel: 'Method Level CR',
    xAxisID: 'method_level_cr_x',
    yAxisID: 'method_level_cr_y',
  },
  INDUSTRY_OVERALL_CR: {
    name: 'Industry Level Overall CR',
    title: 'Industry Level Conversion Rate',
    description:
      'The percentage of payments submitted out of all attempted payments within your specific industry',
    xLabel: 'Time in',
    yLabel: 'Overall CR',
    xAxisID: 'industry_overall_cr_x',
    yAxisID: 'industry_overall_cr_y',
  },
};

export const METHOD_LEVEL_CR = 'METHOD_LEVEL_CR';

export const momentDurationFuncMap = {
  hourly: 'asHours',
  daily: 'asDays',
  weekly: 'asWeeks',
  monthly: 'asMonths',
};
export const momentDurationMap = {
  hourly: 'hours',
  daily: 'days',
  weekly: 'weeks',
  monthly: 'months',
};

export const gridLineColor = '#f0f3f7';
export const chartFontColor = '#2d303380';

// CHART RELATED CONSTANTS END //

// COLORS FOR LINE CHART START //

export const namedColors = {
  'black.400': '#13264426',
  'black.500': '#132644',
};

export const defaultTagStyle = {
  color: 'rgb(91, 132, 199)',
  backgroundColor1: 'rgba(91, 132, 199 , 0.3)',
  backgroundColor2: 'rgba(91, 132, 199 , 0.05)',
};

export const tagStyles = [
  '#5B84C7',
  '#A8FFB9',
  '#B47A29',
  '#2C3E50',
  '#FF7448',
  '#DC9A9A',
  '#6473FF',
  '#FFF278',
  '#CF6AFE',
  '#A7FFFA',
  '#FCBA03',
  '#CF2B54',
  '#902BCF',
];

// COLORS FOR LINE CHART END //

// CHART CONFIG CONSTANTS START //

export const timeAxisUnit = {
  hourly: {
    unit: 'hour',
    stepSize: 1,
  },
  daily: {
    unit: 'day',
    stepSize: 1,
  },
  weekly: {
    unit: 'day',
    stepSize: 7,
  },
  monthly: {
    unit: 'month',
    stepSize: 1,
  },
};

export const WEEKS_MAP = [
  'Monday',
  'Tuesday',
  'Wednesday',
  'Thursday',
  'Friday',
  'Saturday',
  'Sunday',
];

// CHART CONFIG CONSTANT END //
