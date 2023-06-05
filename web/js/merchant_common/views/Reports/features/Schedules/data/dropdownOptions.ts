export const schedulesFilterDropdown = [
  {
    label: 'Date - Newest',
    value: 'param.&sort_by=created_at&sort_order=desc',
  },
  {
    label: 'Date - Ongoing',
    value: 'param.&sort_by=created_at&sort_order=asc',
  },
  {
    label: 'Status - Paused',
    value: 'param.&status=paused',
  },
  {
    label: 'Status - Active',
    value: 'param.&status=active',
  },
];
