import { DateRangeOption } from './utils';

export const dateFilterOptions: Array<{ key: DateRangeOption; value: string }> = [
  {
    key: 'yesterday',
    value: 'Yesterday',
  },
  {
    key: 'last_7_days',
    value: 'Last Week',
  },
  {
    key: 'last_30_days',
    value: 'Last 30 Days',
  },
];

export const dateFilterOptionsMap: Record<DateRangeOption, string> = {
  yesterday: 'previous day',
  last_7_days: 'last week',
  last_30_days: 'last month',
};
