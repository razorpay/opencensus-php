jest.mock('common/ui/Pager', () => () => {
  return (
    <div>
      <span>Pager Details</span>
      <span>Showing 1 - 0</span>
    </div>
  );
});

jest.mock('common/ui/TableLoader', () => ({ colSpan }) => (
  <tr>
    <td className="text-center empty-table" colSpan={colSpan}>
      <span>Loading List</span>
    </td>
  </tr>
));

export const tableHeader = [
  'Settlement Id',
  'Requested Amount',
  'Settled Amount',
  'Pending Amount',
  'Created At',
  'Status',
];

export const options = [
  {
    label: 'All',
    value: '',
  },
  {
    label: 'Created',
    value: 'CREATED',
  },
  {
    label: 'Initiated',
    value: 'INITIATED',
  },
  {
    label: 'Partially Processed',
    value: 'PARTIALLY_PROCESSED',
  },
  {
    label: 'Processed',
    value: 'PROCESSED',
  },
  {
    label: 'Reversed',
    value: 'REVERSED',
  },
  {
    label: 'Failed',
    value: 'FAILED',
  },
];
