import {
  platformFeeCalculator,
  isPlatformTransaction,
} from 'merchant/views/Transactions/Payments/Utils/platformUtils';

describe('platformFee calculator', () => {
  const data = {
    fee: 207,
    tax: 32,
    amount_transferred: 25000,
    loading: false,
    items: [
      { tax: 10, fees: 60, amount: 20000, amount_reversed: 0 },
      { tax: 2, fees: 15, amount: 5000, amount_reversed: 5000 },
    ],
  };
  const returnData = {
    totalFeeAmount: 20282,
    totalFee: 175,
    totalRazorpayFee: 207,
    totalTax: 32,
    platformFee: 20075,
  };
  test('should return calculated values', () => {
    expect(platformFeeCalculator(data)).toStrictEqual({
      ...returnData,
    });
  });
  test('should return calculated values if items are empty', () => {
    expect(platformFeeCalculator({ ...data, loading: true, items: [] })).toStrictEqual({
      ...returnData,
      platformFee: 0,
      totalFeeAmount: 207,
    });
  });
});

describe('platform transaction check', () => {
  const data = {
    loading: false,
    items: [
      { partner_details: { name: 'test user' } },
      { partner_details: { name: 'test user two' } },
    ],
  };
  test('should return true if partner details present in data', () => {
    expect(isPlatformTransaction(data)).toStrictEqual(true);
  });

  test('should return false if items are empty', () => {
    expect(isPlatformTransaction({ ...data, items: [] })).toStrictEqual(false);
  });

  test('should return false if partner details are not present', () => {
    expect(isPlatformTransaction({ ...data, items: [{ id: 'test' }] })).toStrictEqual(false);
  });
  test('should return false if partner details are not present in all the items', () => {
    expect(
      isPlatformTransaction({
        ...data,
        items: [{ id: 'test', partner_details: { name: 'test' } }, { id: 'test' }],
      }),
    ).toStrictEqual(false);
  });
});
