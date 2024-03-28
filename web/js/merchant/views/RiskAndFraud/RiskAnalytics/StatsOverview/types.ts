import {
  AnalyticsEntity,
  MetricOptions,
  Ratios,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/types';

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

export type Stats = Partial<{
  total_payment_amount: FormattedAmount;
  entity_payment_amount: FormattedAmount;
  total_entity_count: number;
  total_payment_count: number;
  entity_payment_ratio: string;
}>;

export interface StatsOverviewProps {
  isLoading: boolean;
  ratios: Ratios;
  entity: AnalyticsEntity;
  metric: MetricOptions;
  stats: Stats;
}
