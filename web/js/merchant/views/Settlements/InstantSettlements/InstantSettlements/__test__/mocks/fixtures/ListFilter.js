export const defaultProps = {
  form: 'instantRouteSettlementListFilter',
  count: 25,
};

export const options = [
  {
    label: 'All',
    value: '',
  },
  {
    label: 'Created',
    value: 'created',
  },
  {
    label: 'Initiated',
    value: 'initiated',
  },
  {
    label: 'Partially Processed',
    value: 'partially_processed',
  },
  {
    label: 'Processed',
    value: 'processed',
  },
  {
    label: 'Reversed',
    value: 'reversed',
  },
];
