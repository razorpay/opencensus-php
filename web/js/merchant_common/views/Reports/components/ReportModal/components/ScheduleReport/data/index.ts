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
    label: 'Past 24 hours',
    value: 'past_24_hours',
  },
  ...(custom
    ? [
        {
          label: 'Past 2 days',
          value: 'past_2_days',
        },
        {
          label: 'Past 3 days',
          value: 'past_3_days',
        },
      ]
    : []),
  {
    label: 'Past Week',
    value: 'past_week',
  },
  {
    label: 'Past 15 days',
    value: 'past_15_days',
  },
  {
    label: 'Past Month',
    value: 'past_month',
  },
  {
    label: 'Past Quarter',
    value: 'past_quater',
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
    case 'past_24_hours':
    case 'past_2_days':
    case 'past_3_days':
      return [
        {
          label: 'Daily',
          value: 'daily',
        },
      ];
    case 'past_week':
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
    case 'past_15_days':
      return [
        {
          label: 'In 15 days',
          value: 'in_15_days',
        },
      ];
    case 'past_month':
      return custom
        ? pastMonthRepetitions
        : [
            {
              label: 'Monthly',
              value: 'monthly',
            },
          ];
    case 'past_quater':
      return [
        {
          label: 'Quarterly',
          value: 'quaterly',
        },
      ];
    default:
      return [];
  }
};
