import {
  FRAUD,
  DISPUTES,
  RISK_DECLINED,
  METRIC_COUNT,
  METRIC_VALUE,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/constants';

import { StatsFormat } from './types';

// Stats Configuration for FRAUD
const FRAUDS_VALUE_STATS = [
  {
    key: 'total_payment_amount',
    label: 'Total sales value',
    tooltip: 'Total captured sales (in ₹) for your business in the selected time period.',
    format: 'currency' as StatsFormat,
  },
  {
    key: 'entity_payment_amount',
    label: 'Value of reported frauds',
    tooltip:
      'Total value of fraudulent transactions reported by networks in the selected time period. Fraud reporting can take up to a few weeks and hence it can correspond to a transaction from an older date.',
    format: 'currency' as StatsFormat,
  },
  {
    key: 'entity_payment_ratio',
    label: 'Fraud-to-sales ratio',
    tooltip:
      'Value of fraud transactions reported by card networks as a % of total value of captured transactions in a given time period.',
    format: 'percentage' as StatsFormat,
    comparisionKey: 'industry_fraud_to_sales_ratio',
    additionalInfo: 'Higher than industry average',
  },
  {
    key: 'total_entity_count',
    label: 'Number of reported frauds',
    tooltip:
      'Total number of fraudulent transactions reported by networks in the selected time period. Fraud reporting can take up to a few weeks and hence it can correspond to a transaction from an older date.',
    format: 'integer' as StatsFormat,
  },
];

const FRAUDS_COUNT_STATS = [
  {
    key: 'total_payment_count',
    label: 'Total international transactions',
    tooltip: 'Total captured transactions in the selected time period.',
    format: 'integer' as StatsFormat,
  },
  {
    key: 'total_entity_count',
    label: 'Number of reported frauds',
    tooltip:
      'Total number of fraudulent transactions reported by networks in the selected time period. Fraud reporting can take up to a few weeks and hence it can correspond to a transaction from an older date.',
    format: 'integer' as StatsFormat,
  },
  {
    key: 'entity_payment_ratio',
    label: 'Fraud-to-sales ratio',
    tooltip:
      'Number of fraud transactions reported by card networks as a % of total number of captured transactions in a given time period.',
    format: 'percentage' as StatsFormat,
    comparisionKey: 'industry_fraud_to_sales_ratio',
    additionalInfo: 'Higher than industry average',
  },
  {
    key: 'entity_payment_amount',
    label: 'Value of reported frauds',
    tooltip:
      'Total value of fraudulent transactions reported by networks in the selected time period. Fraud reporting  can take up to a few weeks and hence it can correspond to a transaction from an older date.',
    format: 'currency' as StatsFormat,
  },
];

// Stats Configuration for DISPUTES
const DISPUTES_VALUE_STATS = [
  {
    key: 'total_payment_amount',
    label: 'Total sales value',
    tooltip: 'Total captured sales (in ₹) for your business in the selected time period.',
    format: 'currency' as StatsFormat,
  },
  {
    key: 'entity_payment_amount',
    label: 'Value of reported disputes',
    tooltip:
      'Total value of disputed transactions reported by networks in the selected time period. Dispute reporting can take up to a few weeks and hence it can correspond to a transaction from an older date.',
    format: 'currency' as StatsFormat,
  },
  {
    key: 'entity_payment_ratio',
    label: 'Dispute-to-sales ratio',
    tooltip:
      'Value of dispute transactions reported by card networks as a % of total number of captured transactions in a given time period.',
    format: 'percentage' as StatsFormat,
    comparisionKey: 'industry_disputes_to_sales_ratio',
    additionalInfo: 'Higher than industry average',
  },
  {
    key: 'total_entity_count',
    label: 'Number of reported disputes',
    tooltip:
      'Total number of disputed transactions reported by networks in the selected time period. Dispute reporting can take up to a few weeks and hence it can correspond to a transaction from an older date.',
    format: 'integer' as StatsFormat,
  },
];

const DISPUTES_COUNT_STATS = [
  {
    key: 'total_payment_count',
    label: 'Total international transactions',
    tooltip: 'Total captured transactions in the selected time period.',
    format: 'integer' as StatsFormat,
  },
  {
    key: 'total_entity_count',
    label: 'Number of reported disputes',
    tooltip:
      'Total number of disputed transactions reported by networks in the selected time period. Dispute reporting can take up to a few weeks and hence it can correspond to a transaction from an older date.',
    format: 'integer' as StatsFormat,
  },
  {
    key: 'entity_payment_ratio',
    label: 'Dispute-to-sales ratio',
    tooltip:
      'Number of dispute transactions reported by card networks as a % of total number of captured transactions in a given time period.',
    format: 'percentage' as StatsFormat,
    comparisionKey: 'industry_disputes_to_sales_ratio',
    additionalInfo: 'Higher than industry average',
  },
  {
    key: 'entity_payment_amount',
    label: 'Value of reported disputes',
    tooltip:
      'Total value of disputed transactions reported by networks in the selected time period. Dispute reporting can take up to a few weeks and hence it can correspond to a transaction from an older date.',
    format: 'currency' as StatsFormat,
  },
];

// Stats Configuration for RISK_DECLINED
const RISK_DECLINES_VALUE_STATS = [
  {
    key: 'total_payment_amount',
    label: 'Total sales value',
    tooltip: 'Total captured sales (in ₹) for your business in the selected time period.',
    format: 'currency' as StatsFormat,
  },
  {
    key: 'entity_payment_amount',
    label: 'Value of risk declines',
    tooltip: 'Total value of risk declines in the selected time period.',
    format: 'currency' as StatsFormat,
  },
  {
    key: 'entity_payment_ratio',
    label: 'Risk decline rate',
    tooltip:
      'Value of risk declines as a % of total value of captured transactions in a given time period.',
    format: 'percentage' as StatsFormat,
    comparisionKey: 'industry_risk_declined_to_sales_ratio',
    additionalInfo: 'Higher than industry average',
  },
  {
    key: 'total_entity_count',
    label: 'Number of risk declines',
    tooltip: 'Total number of risk declines in the selected time period.',
    format: 'integer' as StatsFormat,
  },
];

const RISK_DECLINES_COUNT_STATS = [
  {
    key: 'total_payment_count',
    label: 'Total international transactions',
    tooltip: 'Total captured sales (in ₹) for your business in the selected time period.',
    format: 'integer' as StatsFormat,
  },
  {
    key: 'total_entity_count',
    label: 'Number of risk declines',
    tooltip: 'Total number of risk declines in the selected time period.',
    format: 'integer' as StatsFormat,
  },
  {
    key: 'entity_payment_ratio',
    label: 'Risk decline rate',
    tooltip:
      'Number of risk declines as a % of total number of captured transactions in a given time period.',
    format: 'percentage' as StatsFormat,
    comparisionKey: 'industry_risk_declined_to_sales_ratio',
    additionalInfo: 'Higher than industry average',
  },
  {
    key: 'entity_payment_amount',
    label: 'Value of risk declines',
    tooltip: 'Total value of risk declines in the selected time period.',
    format: 'currency' as StatsFormat,
  },
];

export const STATS_CONFIG_MAPPING = {
  [FRAUD]: {
    [METRIC_COUNT]: FRAUDS_COUNT_STATS,
    [METRIC_VALUE]: FRAUDS_VALUE_STATS,
  },
  [DISPUTES]: {
    [METRIC_COUNT]: DISPUTES_COUNT_STATS,
    [METRIC_VALUE]: DISPUTES_VALUE_STATS,
  },
  [RISK_DECLINED]: {
    [METRIC_COUNT]: RISK_DECLINES_COUNT_STATS,
    [METRIC_VALUE]: RISK_DECLINES_VALUE_STATS,
  },
};
