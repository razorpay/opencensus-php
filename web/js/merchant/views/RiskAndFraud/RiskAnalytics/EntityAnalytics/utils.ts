import moment from 'moment';

import {
  DEFAULT_METRIC,
  DEFAULT_PRESET,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/constants';

import { EntityAnalyticsState } from './types';
import { DEFAULT_CHART_OPTIONS } from '../ChartContainer/constants';
import { AnalyticsEntity, IntervalValue } from '../types';

export const getBreakdownInterval = (from: number, to: number): IntervalValue => {
  const startDate = moment(from * 1000);
  const endDate = moment(to * 1000);
  const differenceInDays = Math.abs(endDate.diff(startDate, 'days'));

  if (differenceInDays <= 30) {
    return 'day';
  } else if (differenceInDays > 30 && differenceInDays <= 180) {
    return 'week';
  } else if (differenceInDays > 180) {
    return 'month';
  } else {
    return 'day';
  }
};

export const getInitialState = (entity: AnalyticsEntity): EntityAnalyticsState => {
  const preset = DEFAULT_PRESET[entity];
  const { duration, unit } = preset;

  // Get the previous day since there is no data available for the current day.
  const endDate = moment().startOf('day').unix();
  const startDate = moment().subtract(duration, unit).startOf('day').unix();
  const interval = getBreakdownInterval(startDate, endDate);

  const payload = {
    queryData: null,
    dateRange: { startDate, endDate, preset },
    metric: DEFAULT_METRIC,
    graphOptions: DEFAULT_CHART_OPTIONS[entity],
    interval,
  };

  return payload;
};
