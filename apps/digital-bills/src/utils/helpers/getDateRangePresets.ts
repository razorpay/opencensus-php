import moment from 'moment';

const today = moment();

// Since, old BillMe service is handling a max of 90 days range,
// we are setting the range from start date (start of the day) + 1 to end date (end of the day)
export const getDateRangePresets = () => [
  {
    label: 'Last 7 days',
    value: () => {
      return [today.clone().subtract(6, 'days').toDate(), today.toDate()];
    },
  },
  {
    label: 'Last 30 days',
    value: () => {
      return [today.clone().subtract(29, 'days').toDate(), today.toDate()];
    },
  },
  {
    label: 'Last 90 days',
    value: () => {
      return [today.clone().subtract(89, 'days').toDate(), today.toDate()];
    },
  },
  {
    label: 'Custom',
    value: () => {
      return [today.clone().subtract(1, 'days').toDate(), today.toDate()];
    },
  },
];

export const getSelectedDatesFromPicker = (range) => {
  let fromDate, toDate;
  if (range?.[0]) {
    fromDate = new Date(range?.[0]);
    fromDate.setHours(0, 0, 0, 0);
    fromDate = fromDate.toISOString();
  }
  if (range?.[1]) {
    toDate = new Date(range?.[1]);
    toDate.setHours(23, 59, 59, 999);
    toDate = toDate.toISOString();
  }
  return { fromDate, toDate };
};

export const getUpdatedDateRange = (selectedDateRange) => {
  const fromDate = selectedDateRange[0] ? new Date(selectedDateRange[0]) : null;
  const toDate = selectedDateRange[1] ? new Date(selectedDateRange[1]) : null;
  return [fromDate, toDate];
};
