const columnNames = ['component', 'count', 'fee', 'tax', 'type', 'amount', 'settled_amount'];

const breakupTableItems = [
  {
    component: 'adjustment',
    amount: 50300,
    count: 16,
    type: 'debit',
    fee: 0,
    tax: 0,
  },
  {
    component: 'payment_domestic',
    amount: 34647940,
    count: 204,
    type: 'credit',
    fee: 678291,
    tax: 122088,
  },
  {
    component: 'refund_domestic',
    amount: 21511,
    count: 8,
    type: 'debit',
    fee: 0,
    tax: 0,
  },
  {
    component: 'reversal',
    amount: 101200,
    count: 5,
    type: 'credit',
    fee: 0,
    tax: 0,
  },
  {
    component: 'transfer',
    amount: 185539,
    count: 45,
    type: 'debit',
    fee: 0,
    tax: 0,
  },
];

export const breakupTableProps = {
  items: breakupTableItems,
  loading: false,
  columnNames,
  isNew: true,
};
