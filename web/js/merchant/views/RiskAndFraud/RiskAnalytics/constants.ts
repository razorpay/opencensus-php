import { Theme } from '@razorpay/blade/components';

import { AnalyticsEntity, DateRangePreset, MetricOptions, Ratios } from './types';

export const ASSETS_PATH = '/dist/css/assets/risk-analytics';

export const FRAUD: AnalyticsEntity = 'fraud';
export const DISPUTES: AnalyticsEntity = 'disputes';
export const RISK_DECLINED: AnalyticsEntity = 'risk_declined';

export const ENTITY_SECTIONS = [FRAUD, DISPUTES, RISK_DECLINED];

export const INITIAL_RATIOS = {
  fraud_to_sales_ratio: 0,
  disputes_to_sales_ratio: 0,
  risk_declined_to_sales_ratio: 0,
  industry_fraud_to_sales_ratio: 0,
  industry_disputes_to_sales_ratio: 0,
  industry_risk_declined_to_sales_ratio: 0,
} as Ratios;

export const ENTITY_HEADER = {
  [FRAUD]: {
    title: 'Frauds',
    description: 'Analyze fraudulent transactions and identify patterns.',
    popoverText: 'How is fraud-to-sales ratio calculated?',
    popoverTitle: 'Fraud-to-sales ratio',
    popoverContent:
      'A fraud is said to occur when an unauthorized transaction is made with a lost, stolen, compromised or counterfeit card/number. It is calculated as:',
    popoverImage: `${ASSETS_PATH}/fraud-to-sales-ratio.png`,
    imageAlt: 'fraud-to-sales-ratio',
    docLink:
      'https://_businessName_.com/docs/payments/payments/risk-visibility-dashboard/fraud-sales-ratio/#calculating-fraud-to-sales-ratio',
  },
  [DISPUTES]: {
    title: 'Disputes',
    description: 'Analyze disputes and identify patterns.',
    popoverText: 'How is dispute-to-sales ratio calculated?',
    popoverTitle: 'Dispute-to-sales ratio',
    popoverContent:
      'A dispute is said to occur when a cardholder questions your payment with their card issuer. It is calculated as:',
    popoverImage: `${ASSETS_PATH}/disputes-to-sales-ratio.png`,
    imageAlt: 'disputes-to-sales-ratio',
    docLink:
      'https://_businessName_.com/docs/payments/payments/risk-visibility-dashboard/dispute-sales-ratio/#calculating-disputes-to-sales-ratio',
  },
  [RISK_DECLINED]: {
    title: 'Risk Declines',
    description: 'Analyze risk declines and identify patterns.',
    popoverText: 'How is risk decline rate calculated?',
    popoverTitle: 'Risk decline rate',
    popoverContent:
      'A risk decline occurs when the algorithm (_businessName_, bank or network) declines or blocks risky transactions that might be potentially fraudulent or have high likelihood of being disputed. It is calculated as:',
    popoverImage: `${ASSETS_PATH}/risk-decline-ratio.png`,
    imageAlt: 'risk-decline-ratio',
    docLink:
      'https://_businessName_.com/docs/payments/payments/risk-visibility-dashboard/risk-decline-rate/#calculating-risk-decline-rate',
  },
};

export const CUSTOM = 'custom';
export const PRESETS = [
  { label: 'Last 2 weeks', value: '14d', duration: 14, unit: 'days' },
  { label: 'Last 1 month', value: '30d', duration: 30, unit: 'days' },
  { label: 'Last 2 months', value: '60d', duration: 60, unit: 'days' },
  { label: 'Last 3 months', value: '90d', duration: 90, unit: 'days' },
  { label: 'Last 6 months', value: '6m', duration: 6, unit: 'months' },
  { label: 'Last 1 year', value: '12m', duration: 12, unit: 'months' },
  { label: 'Last 2 years', value: '24m', duration: 24, unit: 'months' },
  { label: 'Custom', value: 'custom', duration: 14, unit: 'days' },
];

export const RISK_DECLINED_PRESETS = [
  { label: 'Last 1 week', value: '7d', duration: 7, unit: 'days' },
  { label: 'Last 2 weeks', value: '14d', duration: 14, unit: 'days' },
  { label: 'Last 1 month', value: '30d', duration: 30, unit: 'days' },
  { label: 'Last 2 months', value: '60d', duration: 60, unit: 'days' },
  { label: 'Last 3 months', value: '90d', duration: 90, unit: 'days' },
  { label: 'Last 6 months', value: '6m', duration: 6, unit: 'months' },
  { label: 'Custom', value: 'custom', duration: 14, unit: 'days' },
];

export const ENTITY_PRESETS = {
  [FRAUD]: PRESETS,
  [DISPUTES]: PRESETS,
  [RISK_DECLINED]: RISK_DECLINED_PRESETS,
};

export const DEFAULT_PRESET: { [entity: string]: DateRangePreset } = {
  [FRAUD]: { label: 'Last 2 weeks', value: '14d', duration: 14, unit: 'days' },
  [DISPUTES]: { label: 'Last 2 weeks', value: '14d', duration: 14, unit: 'days' },
  [RISK_DECLINED]: { label: 'Last 1 week', value: '7d', duration: 7, unit: 'days' },
};

export const MOBILE_BREAKPOINTS: Readonly<Array<keyof Theme['breakpoints']>> = [
  'base',
  'xs',
  's',
  'm',
];

export const MOBILE_CALENDAR_NUMBER_OF_MONTHS = 1;
export const DESKTOP_CALENDAR_NUMBER_OF_MONTHS = 2;

export const METRIC_COUNT: MetricOptions = 'count';
export const METRIC_VALUE: MetricOptions = 'amount';
export const DEFAULT_METRIC: MetricOptions = METRIC_VALUE;

export const METRIC_OPTIONS = [
  { label: 'absolute count', value: 'count' },
  { label: 'value (in ₹)', value: 'amount' },
];

// state action types
export const SET_DATE_RANGE = 'SET_DATE_RANGE';
export const SET_METRIC = 'SET_METRIC';
export const SET_CHART_OPTIONS = 'SET_CHART_OPTIONS';
export const SET_INTERVAL = 'SET_INTERVAL';
export const SET_CHART_DATA = 'SET_CHART_DATA';

const FRAUD_BLOCK_CONTRIBUTORS = {
  heading: 'Block highest fraud contributers',
  description:
    'Have you observed most of the frauds are from certain IP addresses, countries, BINs or any other parameter? You can now request us to blacklist those parameters and help you lower the number of frauds.',
};

const DISPUTES_BLOCK_CONTRIBUTORS = {
  heading: 'Block highest dispute contributers',
  description:
    'Have you observed most of the disputes are from certain IP addresses, countries, BINs or any other parameter? You can now request us to blacklist those parameters and help you lower the number of disputes.',
};

export const BLOCK_CONTRIBUTORS = {
  [FRAUD]: FRAUD_BLOCK_CONTRIBUTORS,
  [DISPUTES]: DISPUTES_BLOCK_CONTRIBUTORS,
};
