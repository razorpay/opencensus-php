import { platformFeeCalculator } from 'merchant/views/Transactions/Payments/Utils/platformUtils';

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
