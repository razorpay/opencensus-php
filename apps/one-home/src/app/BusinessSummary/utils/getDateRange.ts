import moment from 'moment';

export type DateRangeOption = 'yesterday' | 'last_7_days' | 'last_30_days';

/**
 * Generates a human-readable date range based on the selected option.
 * @param option - The date range option ('yesterday', 'last_7_days', 'last_30_days').
 * @returns A formatted date string representing the range.
 */
export const getDateRange = (option: DateRangeOption): string => {
  const format = 'D MMM'; // Example: 1 Nov, 28 Sept

  switch (option) {
    case 'yesterday': {
      return moment().subtract(1, 'day').format('D MMM');
    }
    case 'last_7_days': {
      const start = moment().subtract(7, 'days').format(format);
      const end = moment().format(format);
      return `${start} - ${end}`;
    }
    case 'last_30_days': {
      const start = moment().subtract(30, 'days').format(format);
      const end = moment().subtract(1, 'day').format(format);
      return `${start} - ${end}`;
    }
    default:
      return 'Invalid date range';
  }
};
