import { AnalyticsEntity, MetricOptions } from 'merchant/views/RiskAndFraud/RiskAnalytics/types';

export interface Ratios {
  fraud_to_sales_ratio: number;
  disputes_to_sales_ratio: number;
  risk_declined_to_sales_ratio: number;
  industry_fraud_to_sales_ratio: number;
  industry_disputes_to_sales_ratio: number;
  industry_risk_declined_to_sales_ratio: number;
}

export type StatsFormat = 'currency' | 'percentage' | 'integer';

export interface StatsConfig {
  key: string;
  label: string;
  tooltip: string;
  format: StatsFormat;
  comparisionKey?: string | undefined;
  additionalInfo?: string | undefined;
}

type FormattedAmount = {
  value: string;
  decimal: string;
};

export interface Stats {
  total_payment_amount: FormattedAmount;
  entity_payment_amount: FormattedAmount;
  total_entity_count: string;
  total_payment_count: string;
  entity_payment_ratio: string;
}

export interface StatsOverviewProps {
  isLoading: boolean;
  ratios: Ratios;
  entity: AnalyticsEntity;
  metric: MetricOptions;
  stats: Stats;
}
