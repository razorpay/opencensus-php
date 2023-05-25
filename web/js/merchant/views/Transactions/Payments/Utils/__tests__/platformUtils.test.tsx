import {
  platformFeeCalculator,
  isPlatformTransaction,
} from 'merchant/views/Transactions/Payments/Utils/platformUtils';

describe('platformFee calculator', () => {
  const data = {
    fee: 200,
    tax: 100,
    amount_transferred: 300,
    loading: false,
    items: [
      { tax: 60, fees: 150, amount: 100 },
      { tax: 50, fees: 50, amount: 250 },
    ],
  };
  const returnData = {
    totalFeeAmount: 600,
    totalFee: 400,
    totalRazorpayFee: 610,
    totalTax: 210,
    platformFee: 350,
  };
  test('should return calculated values', () => {
    expect(platformFeeCalculator(data)).toStrictEqual({
      ...returnData,
    });
  });
  test('should return calculated values if items are empty', () => {
    expect(platformFeeCalculator({ ...data, loading: true, items: [] })).toStrictEqual({
      ...returnData,
      totalRazorpayFee: 300,
      totalFee: 200,
      totalTax: 100,
      platformFee: 0,
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
