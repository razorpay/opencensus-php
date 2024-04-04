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
    tooltip: 'Total sales value',
    format: 'currency' as StatsFormat,
  },
  {
    key: 'entity_payment_amount',
    label: 'Value of reported fraud',
    tooltip: 'Value of reported fraud',
    format: 'currency' as StatsFormat,
  },
  {
    key: 'entity_payment_ratio',
    label: 'Fraud-to-sales ratio',
    tooltip: 'Fraud-to-sales ratio',
    format: 'percentage' as StatsFormat,
    comparisionKey: 'industry_fraud_to_sales_ratio',
    additionalInfo: 'Higher than industry average',
  },
  {
    key: 'total_entity_count',
    label: 'Number of reported frauds',
    tooltip: 'Number of reported frauds',
    format: 'integer' as StatsFormat,
  },
];

const FRAUDS_COUNT_STATS = [
  {
    key: 'total_payment_count',
    label: 'Total international transactions',
    tooltip: 'Total international transactions',
    format: 'integer' as StatsFormat,
  },
  {
    key: 'total_entity_count',
    label: 'Number of reported frauds',
    tooltip: 'Number of reported frauds',
    format: 'integer' as StatsFormat,
  },
  {
    key: 'entity_payment_ratio',
    label: 'Fraud-to-sales ratio',
    tooltip: 'Fraud-to-sales ratio',
    format: 'percentage' as StatsFormat,
    comparisionKey: 'industry_fraud_to_sales_ratio',
    additionalInfo: 'Higher than industry average',
  },
  {
    key: 'entity_payment_amount',
    label: 'Value of reported fraud',
    tooltip: 'Value of reported fraud',
    format: 'currency' as StatsFormat,
  },
];

// Stats Configuration for DISPUTES
const DISPUTES_VALUE_STATS = [
  {
    key: 'total_payment_amount',
    label: 'Total sales value',
    tooltip: 'Total sales value',
    format: 'currency' as StatsFormat,
  },
  {
    key: 'entity_payment_amount',
    label: 'Value of reported disputes',
    tooltip: 'Value of reported disputes',
    format: 'currency' as StatsFormat,
  },
  {
    key: 'entity_payment_ratio',
    label: 'Dispute-to-sales ratio',
    tooltip: 'Dispute-to-sales ratio',
    format: 'percentage' as StatsFormat,
    comparisionKey: 'industry_disputes_to_sales_ratio',
    additionalInfo: 'Higher than industry average',
  },
  {
    key: 'total_entity_count',
    label: 'Number of reported disputes',
    tooltip: 'Number of reported disputes',
    format: 'integer' as StatsFormat,
  },
];

const DISPUTES_COUNT_STATS = [
  {
    key: 'total_payment_amount',
    label: 'Total sales value',
    tooltip: 'Total sales value',
    format: 'currency' as StatsFormat,
  },
  {
    key: 'entity_payment_amount',
    label: 'Value of reported disputes',
    tooltip: 'Value of reported disputes',
    format: 'currency' as StatsFormat,
  },
  {
    key: 'entity_payment_ratio',
    label: 'Dispute-to-sales ratio',
    tooltip: 'Dispute-to-sales ratio',
    format: 'percentage' as StatsFormat,
    comparisionKey: 'industry_disputes_to_sales_ratio',
    additionalInfo: 'Higher than industry average',
  },
  {
    key: 'total_entity_count',
    label: 'Number of reported disputes',
    tooltip: 'Number of reported disputes',
    format: 'integer' as StatsFormat,
  },
];

// Stats Configuration for RISK_DECLINED
const RISK_DECLINES_VALUE_STATS = [
  {
    key: 'total_payment_amount',
    label: 'Total sales value',
    tooltip: 'Total sales value',
    format: 'currency' as StatsFormat,
  },
  {
    key: 'entity_payment_amount',
    label: 'Value of risk declines',
    tooltip: 'Value of risk declines',
    format: 'currency' as StatsFormat,
  },
  {
    key: 'entity_payment_ratio',
    label: 'Risk decline rate',
    tooltip: 'Risk decline rate',
    format: 'percentage' as StatsFormat,
    comparisionKey: 'industry_risk_declined_to_sales_ratio',
    additionalInfo: 'Higher than industry average',
  },
  {
    key: 'total_entity_count',
    label: 'Number of risk declines',
    tooltip: 'Number of risk declines',
    format: 'integer' as StatsFormat,
  },
];

const RISK_DECLINES_COUNT_STATS = [
  {
    key: 'total_payment_count',
    label: 'Total international transactions',
    tooltip: 'Total international transactions',
    format: 'integer' as StatsFormat,
  },
  {
    key: 'total_entity_count',
    label: 'Number of risk declines',
    tooltip: 'Number of risk declines',
    format: 'integer' as StatsFormat,
  },
  {
    key: 'entity_payment_ratio',
    label: 'Risk decline rate',
    tooltip: 'Risk decline rate',
    format: 'percentage' as StatsFormat,
    comparisionKey: 'industry_risk_declined_to_sales_ratio',
    additionalInfo: 'Higher than industry average',
  },
  {
    key: 'entity_payment_amount',
    label: 'Value of risk declines',
    tooltip: 'Value of risk declines',
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
