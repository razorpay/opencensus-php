export const STATUS_TEXT_AND_COLOR_MAP = {
  ACTIVE: {
    text: 'Active',
    color: 'positive',
  },
  INACTIVE: {
    text: 'Inactive',
    color: 'negative',
  },
} as const;

export const TABLE_HEADERS = [
  'Store Details',
  'Status',
  'Total Sales',
  'Average Billing',
  'Total Transactions',
  'Digital Bills',
  'Digital + Print',
  'Print',
];
