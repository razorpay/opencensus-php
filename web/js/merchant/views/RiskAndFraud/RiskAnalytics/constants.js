export const ASSETS_PATH = '/dist/css/assets/risk-analytics';

export const FRAUD = 'fraud';
export const DISPUTES = 'disputes';
export const RISK_DECLINED = 'risk_declined';

export const ENTITY_HEADER = {
  [FRAUD]: {
    title: 'Frauds',
    description: 'Analyze fraudulent transactions and identify patterns',
    popoverText: 'How is fraud-to-sales ratio calculated?',
    popoverTitle: 'Fraud-to-sales ratio',
    popoverContent:
      'A fraud is said to occur when an unauthorized transaction is made with a lost, stolen, compromised or counterfeit card/number. It is calculated as:',
    popoverImage: `${ASSETS_PATH}/fraud-to-sales-ratio.png`,
    imageAlt: 'fraud-to-sales-ratio',
  },
  [DISPUTES]: {
    title: 'Disputes',
    description: 'Analyze disputes and identify patterns.',
    popoverText: 'How is dispute-to-sales ratio calculated?',
    popoverTitle: 'Fraud-to-sales ratio',
    popoverContent:
      'Fraud transactions reported by card networks as a % of total captured transactions in a given time period',
    popoverImage: `${ASSETS_PATH}/risk-decline-ratio.png`,
    imageAlt: 'risk-decline-ratio',
  },
  [RISK_DECLINED]: {
    title: 'Risk Declines',
    description: 'Analyze risk declines and identify patterns.',
    popoverText: 'How is risk decline rate calculated',
    popoverTitle: 'Fraud-to-sales ratio',
    popoverContent:
      'Fraud transactions reported by card networks as a % of total captured transactions in a given time period',
    popoverImage: `${ASSETS_PATH}/disputes-to-sales-ratio.png`,
    imageAlt: 'disputes-to-sales-ratio',
  },
};

export const PRESETS = [
  { label: 'Last 2 weeks', value: '14d', duration: 14, unit: 'days' },
  { label: 'Last 1 month', value: '30d', duration: 30, unit: 'days' },
  { label: 'Last 2 months', value: '60d', duration: 60, unit: 'days' },
  { label: 'Last 3 months', value: '90d', duration: 90, unit: 'days' },
  { label: 'Last 6 months', value: '6m', duration: 6, unit: 'months' },
  { label: 'Last 1 year', value: '12m', duration: 12, unit: 'months' },
  { label: 'Last 2 years', value: '24m', duration: 24, unit: 'months' },
];

export const RISK_DECLINED_PRESETS = [
  { label: 'Last 1 week', value: '7d', duration: 7, unit: 'days' },
  { label: 'Last 2 weeks', value: '14d', duration: 14, unit: 'days' },
  { label: 'Last 1 month', value: '30d', duration: 30, unit: 'days' },
  { label: 'Last 2 months', value: '60d', duration: 60, unit: 'days' },
  { label: 'Last 3 months', value: '90d', duration: 90, unit: 'days' },
  { label: 'Last 6 months', value: '6m', duration: 6, unit: 'months' },
];

export const ENTITY_PRESETS = {
  [FRAUD]: PRESETS,
  [DISPUTES]: PRESETS,
  [RISK_DECLINED]: RISK_DECLINED_PRESETS,
};

export const DEFAULT_PRESET = { [FRAUD]: '14d', [DISPUTES]: '14d', [RISK_DECLINED]: '7d' };

export const METRIC_COUNT = 'count';
export const METRIC_VALUE = 'amount';
export const DEFAULT_METRIC = METRIC_VALUE;

export const METRIC_OPTIONS = [
  { label: 'absolute count', value: 'count' },
  { label: 'value (in ₹)', value: 'amount' },
];

export const TOTAL_SALES_VALUE = 'total_sales';
export const VALUE_OF_REPORTED_ENTITY = 'entity';
export const ENTITY_RATIO = 'entity_ratio';
export const CHART_ORDER = [TOTAL_SALES_VALUE, VALUE_OF_REPORTED_ENTITY, ENTITY_RATIO];

export const DEFAULT_CHART_OPTIONS = {
  [FRAUD]: [VALUE_OF_REPORTED_ENTITY, ENTITY_RATIO],
  [DISPUTES]: [VALUE_OF_REPORTED_ENTITY, ENTITY_RATIO],
  [RISK_DECLINED]: [ENTITY_RATIO],
};

const FRAUD_VALUE_OPTIONS = [
  { label: 'Total sales value', value: 'total_sales' },
  { label: 'Value of reported frauds', value: 'entity' },
  { label: 'Fraud-to-sales ratio', value: 'entity_ratio' },
];

const FRAUD_COUNT_OPTIONS = [
  { label: 'Number of transactions', value: 'total_sales' },
  { label: 'Number of reported frauds', value: 'entity' },
  { label: 'Fraud-to-sales ratio', value: 'entity_ratio' },
];

const DISPUTES_VALUE_OPTIONS = [
  { label: 'Total sales value', value: 'total_sales' },
  { label: 'Value of reported disputes', value: 'entity' },
  { label: 'Disputes-to-sales ratio', value: 'entity_ratio' },
];

const DISPUTES_COUNT_OPTIONS = [
  { label: 'Number of transactions', value: 'total_sales' },
  { label: 'Number of reported disputes', value: 'entity' },
  { label: 'Disputes-to-sales ratio', value: 'entity_ratio' },
];

const RISK_DECLINED_OPTIONS = [{ label: 'Risk decline rate', value: 'entity_ratio' }];

export const CHART_OPTIONS_MAPPING = {
  [FRAUD]: {
    [METRIC_COUNT]: FRAUD_COUNT_OPTIONS,
    [METRIC_VALUE]: FRAUD_VALUE_OPTIONS,
  },
  [DISPUTES]: {
    [METRIC_COUNT]: DISPUTES_COUNT_OPTIONS,
    [METRIC_VALUE]: DISPUTES_VALUE_OPTIONS,
  },
  [RISK_DECLINED]: {
    [METRIC_COUNT]: RISK_DECLINED_OPTIONS,
    [METRIC_VALUE]: RISK_DECLINED_OPTIONS,
  },
};
