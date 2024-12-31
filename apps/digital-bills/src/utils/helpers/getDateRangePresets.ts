import moment from 'moment';

const today = moment();
export const getDateRangePresets = () => [
  {
    label: 'Last 7 days',
    value: () => {
      return [today.clone().subtract(7, 'days').toDate(), today.toDate()];
    },
  },
  {
    label: 'Last 30 days',
    value: () => {
      return [today.clone().subtract(30, 'days').toDate(), today.toDate()];
    },
  },
  {
    label: 'Last 90 days',
    value: () => {
      return [today.clone().subtract(90, 'days').toDate(), today.toDate()];
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
    fromDate = new Date(range?.[0]).toISOString();
  }
  if (range?.[1]) {
    toDate = new Date(range?.[1]);
    toDate.setHours(23, 59, 59, 999);
    toDate.toISOString();
  }
  return { fromDate, toDate };
};

export const getUpdatedDateRange = (selectedDateRange) => {
  const fromDate = selectedDateRange[0] ? new Date(selectedDateRange[0]) : null;
  const toDate = selectedDateRange[1] ? new Date(selectedDateRange[1]) : null;
  return [fromDate, toDate];
};
