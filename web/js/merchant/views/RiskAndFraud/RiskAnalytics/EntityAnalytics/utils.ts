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
  const currentDate = moment().subtract(1, 'day');
  const endDate = moment(currentDate).startOf('day');
  const startDate = endDate.clone().subtract(duration, unit).startOf('day');
  const interval = getBreakdownInterval(startDate.unix(), endDate.unix());

  const payload = {
    queryData: null,
    dateRange: { startDate: startDate.unix(), endDate: endDate.unix(), preset },
    metric: DEFAULT_METRIC,
    graphOptions: DEFAULT_CHART_OPTIONS[entity],
    interval,
  };

  return payload;
};
