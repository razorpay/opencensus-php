import { SelectedRangeType } from 'merchant_common/views/Reports/components/types';
import { TODAY } from 'merchant_common/views/Reports/constants';

export const getTodayRange = (): SelectedRangeType => {
  const startDate = TODAY.clone().startOf('day');
  const endDate = TODAY;
  return { startDate, endDate };
};

export const getYesterdayRange = (): SelectedRangeType => {
  const startDate = TODAY.clone().subtract(1, 'day').startOf('day');
  const endDate = TODAY.clone().subtract(1, 'day').endOf('day');
  return { startDate, endDate };
};

export const getPastWeekRange = (): SelectedRangeType => {
  const startDate = TODAY.clone().subtract(1, 'week').startOf('day');
  const endDate = TODAY;
  return { startDate, endDate };
};

export const getPast15DaysRange = (): SelectedRangeType => {
  const startDate = TODAY.clone().subtract(15, 'day').startOf('day');
  const endDate = TODAY;
  return { startDate, endDate };
};

export const getPastMonthRange = (): SelectedRangeType => {
  const startDate = TODAY.clone().subtract(1, 'month').startOf('day');
  const endDate = TODAY;
  return { startDate, endDate };
};

export const getPastQuaterRange = (): SelectedRangeType => {
  const startDate = TODAY.clone().subtract(3, 'month').startOf('day');
  const endDate = TODAY;
  return { startDate, endDate };
};
