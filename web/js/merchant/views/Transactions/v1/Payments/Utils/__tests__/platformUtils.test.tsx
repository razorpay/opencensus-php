import {
  platformFeeCalculator,
  isPlatformTransaction,
} from 'merchant/views/Transactions/v1/Payments/Utils/platformUtils';

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
    isPartnerPlatformFeeEnabled: true,
  };
  const returnPlatformFeeData = {
    totalFeeAmount: 20282,
    totalFee: 175,
    totalPaymentFee: 207,
    totalTax: 32,
    partnerFee: 20075,
  };
  const returnPartnerFeeData = {
    totalFeeAmount: 282,
    totalFee: 175,
    totalPaymentFee: 207,
    totalTax: 32,
    partnerFee: 20000,
  };

  test('should return returnPlatformFeeData if isPartnerPlatformFeeEnabled ', () => {
    expect(platformFeeCalculator(data)).toStrictEqual({
      ...returnPlatformFeeData,
    });
  });
  test('should return returnPartnerFeeData if isPartnerPlatformFeeEnabled is not enabled ', () => {
    expect(platformFeeCalculator({ ...data, isPartnerPlatformFeeEnabled: false })).toStrictEqual({
      ...returnPartnerFeeData,
    });
  });
  test('should return calculated values if items are empty', () => {
    expect(platformFeeCalculator({ ...data, loading: true, items: [] })).toStrictEqual({
      ...returnPlatformFeeData,
      partnerFee: 0,
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
