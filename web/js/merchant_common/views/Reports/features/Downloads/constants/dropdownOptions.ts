export const downloadsFilterDropdown = [
  {
    label: 'Date - Newest',
    value: 'param.&sort_by=created_at&sort_order=desc',
  },
  {
    label: 'Date - Oldest',
    value: 'param.&sort_by=created_at&sort_order=asc',
  },
  {
    label: 'Status - Failure',
    value: 'param.&status=failed',
  },
  {
    label: 'Status - Pending',
    value: 'param.&status=pending',
  },
  {
    label: 'Status - Success',
    value: 'param.&status=processed',
  },
];
