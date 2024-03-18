import moment, { Moment } from 'moment';

import { ReportInitialState } from './types';

export const getInitialState = (): ReportInitialState => {
  return {
    startDate: moment().startOf('day').unix(),
    endDate: moment().subtract(6, 'months').startOf('day').unix(),
    preset: { label: 'Last 6 months', value: '6m', duration: 6, unit: 'months' },
  };
};

export const validateDurationRange = (startDate: Moment, endDate: Moment): boolean => {
  return moment.isMoment(startDate) && moment.isMoment(endDate);
};

export const getDurationCoveredInReports = ({
  isCustomDurationEnabled,
  customDurationRange,
  dateRange,
}): { start_time: number; end_time: number } => {
  if (isCustomDurationEnabled) {
    return {
      start_time: customDurationRange?.startDate.clone().unix(),
      end_time: customDurationRange?.endDate.clone().unix(),
    };
  }

  return {
    start_time: dateRange?.startDate,
    end_time: dateRange?.endDate,
  };
};
