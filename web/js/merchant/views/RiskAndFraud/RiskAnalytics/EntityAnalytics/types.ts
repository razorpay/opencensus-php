import {
  SET_DATE_RANGE,
  SET_METRIC,
  SET_CHART_OPTIONS,
  SET_INTERVAL,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/constants';

import type { SelectedGraphOption } from '../ChartContainer/types';
import type {
  AnalyticsEntity,
  DateRange,
  IntervalValue,
  MetricOptions,
  Ratios,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/types';

export interface EntityAnalyticsProps {
  entity: AnalyticsEntity;
  ratios: Ratios;
  sectionRef: (ref: HTMLElement) => void;
}

export type EntityAnalyticsState = {
  queryData: null;
  dateRange: DateRange;
  metric: MetricOptions;
  graphOptions: SelectedGraphOption[];
  interval: IntervalValue;
};

interface SetDateRangeAction {
  type: typeof SET_DATE_RANGE;
  payload: DateRange;
}

interface SetMetricAction {
  type: typeof SET_METRIC;
  payload: MetricOptions;
}

interface SetChartOptionsAction {
  type: typeof SET_CHART_OPTIONS;
  payload: SelectedGraphOption[];
}

interface SetIntervalAction {
  type: typeof SET_INTERVAL;
  payload: IntervalValue;
}

export type RiskAnalyticsAction =
  | SetDateRangeAction
  | SetMetricAction
  | SetChartOptionsAction
  | SetIntervalAction;

export type AnalyticsReducer = (
  state: EntityAnalyticsState,
  action: RiskAnalyticsAction,
) => EntityAnalyticsState;
