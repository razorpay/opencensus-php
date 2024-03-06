export type AnalyticsEntity = 'fraud' | 'disputes' | 'risk_declined';
export type MetricOptions = 'count' | 'amount';

export type PresetUnit = 'days' | 'week' | 'month' | 'quarter';

export type PresetValue = '7d' | '14d' | '30d' | '60d' | '90d' | '6m' | '1y' | '2y';

export type Duration = 7 | 14 | 30 | 60 | 90 | 6 | 1 | 2;

export type DateRange = {
  startDate: number | null;
  endDate: number | null;
  preset: {
    label: string;
    value: PresetValue;
    duration: Duration;
    unit: PresetUnit;
  };
};

export type IntervalLabel =
  | 'Daily'
  | 'Weekly'
  | 'Daily'
  | 'Weekly'
  | 'Weekly'
  | 'Monthly'
  | 'Weekly'
  | 'Monthly'
  | 'Monthly'
  | 'Quarterly';

export type IntervalValue = 'day' | Exclude<PresetUnit, 'days'>;

export type ChartInterval = {
  label: IntervalLabel;
  value: IntervalValue;
  disabled?: boolean;
};

export interface ChartQueryDataItem {
  start_date: number;
  payment: { [metric in MetricOptions]: string };
  entity_data: { [metric in MetricOptions]: string };
}
