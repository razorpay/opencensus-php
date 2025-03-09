import moment from 'moment';

const dateRangePreset = [
  {
    label: 'Clear',
    value: () => ['', ''],
  },
  {
    label: 'Last 7 days',
    value: () => [
      moment().startOf('day').subtract(6, 'days').toDate(),
      moment().endOf('day').toDate(),
    ],
  },
  {
    label: 'Last 15 days',
    value: () => [
      moment().startOf('day').subtract(14, 'days').toDate(),
      moment().endOf('day').toDate(),
    ],
  },
  {
    label: 'Last month',
    value: () => [
      moment().startOf('day').subtract(1, 'month').toDate(),
      moment().endOf('day').toDate(),
    ],
  },
  {
    label: 'Last 3 months',
    value: () => [
      moment().startOf('day').subtract(3, 'months').toDate(),
      moment().endOf('day').toDate(),
    ],
  },
  {
    label: 'Last 6 months',
    value: () => [
      moment().startOf('day').subtract(6, 'months').toDate(),
      moment().endOf('day').toDate(),
    ],
  },
  {
    label: 'Last year',
    value: () => [
      moment().startOf('day').subtract(1, 'year').toDate(),
      moment().endOf('day').toDate(),
    ],
  },
];

export { dateRangePreset };
