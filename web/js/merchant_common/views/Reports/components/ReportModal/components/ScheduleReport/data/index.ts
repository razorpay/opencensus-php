export const availableFormat = [
  {
    label: 'Excel',
    value: 'xlsx',
  },
  {
    label: 'CSV',
    value: 'csv',
  },
];

const weeks = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

export const getDataDurations = (custom?: boolean) => [
  {
    label: 'Same Day',
    value: 'same_day',
  },
  ...(custom
    ? [
        {
          label: 'Previous Day',
          value: 'previous_day',
        },
      ]
    : []),

  {
    label: 'Same Week',
    value: 'same_week',
  },
  ...(custom
    ? [
        {
          label: 'Previous Week',
          value: 'previous_week',
        },
      ]
    : []),
  {
    label: 'Same Month',
    value: 'same_month',
  },
  ...(custom
    ? [
        {
          label: 'Previous Month',
          value: 'previous_month',
        },
      ]
    : []),
  {
    label: 'Previous Quarter',
    value: 'previous_quarter',
  },
];

export const pastMonthRepetitions = [
  {
    label: 'Monthly on 1st',
    dateIndex: 1,
    value: 'monthly',
  },
  {
    label: 'Monthly on 5th',
    dateIndex: 5,
    value: 'monthly',
  },
  {
    label: 'Monthly on 10th',
    dateIndex: 10,
    value: 'monthly',
  },
  {
    label: 'Monthly on 15th',
    dateIndex: 15,
    value: 'monthly',
  },
  {
    label: 'Monthly on 20th',
    dateIndex: 20,
    value: 'monthly',
  },
  {
    label: 'Monthly on 25th',
    dateIndex: 25,
    value: 'monthly',
  },
  {
    label: 'Last day of the month',
    dateIndex: -1,
    value: 'monthly',
  },
];

export const getRepetitions = (dataDuration: string, custom?: boolean) => {
  switch (dataDuration) {
    case 'same_day':
    case 'previous_day':
      return [
        {
          label: 'Daily',
          value: 'daily',
        },
      ];
    case 'same_week':
    case 'previous_week':
      return custom
        ? weeks.map((week, weekIndex) => ({
            label: `Weekly on ${week}`,
            value: `weekly`,
            weekIndex,
          }))
        : [
            {
              label: 'Weekly',
              value: 'weekly',
            },
          ];
    case 'same_month':
    case 'previous_month':
      return custom
        ? pastMonthRepetitions
        : [
            {
              label: 'Monthly',
              value: 'monthly',
              dateIndex: 1,
            },
          ];
    case 'previous_quarter':
      return [
        {
          label: 'Quarterly',
          value: 'monthly',
        },
      ];
    default:
      return [];
  }
};
