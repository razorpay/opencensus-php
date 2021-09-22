import { calculateCreditDebitAmount, sanitizeTabName } from 'merchant/views/Settlements/v2/util';

const items = [
  {
    component: 'payment_domestic',
    amount: 10989100,
    count: 21,
    type: 'credit',
    fee: 90534,
    tax: 16294,
    settled_amount: 10882272,
  },
  {
    resourceIdField: 'id',
    component: 'adjustment',
    amount: 100000,
    count: 1,
    type: 'credit',
    fee: 0,
    tax: 0,
    resourceUrl: 'settlements',
    amountInINR: '1000.00',
    settled_amount: 100000,
  },
  {
    resourceIdField: 'id',
    component: 'refund',
    amount: 1000,
    count: 1,
    type: 'debit',
    fee: 0,
    tax: 0,
    resourceUrl: 'settlements',
    amountInINR: '10.00',
    settled_amount: -1000,
  },
];

test('calculate correct credit and debit amount', () => {
  const resultWithNewBreakup = calculateCreditDebitAmount(items, true);

  expect(resultWithNewBreakup).toStrictEqual({
    credit: 10982272,
    debit: -1000,
  });

  const resultWithoutNewBreakup = calculateCreditDebitAmount(items, false);

  expect(resultWithoutNewBreakup).toStrictEqual({
    credit: 11089100,
    debit: 1000,
  });
});

test('sanitize tab name', () => {
  const sanitizedTabName = sanitizeTabName(' Settlement_Test ');

  expect(sanitizedTabName).toBe('Settlement');
});
