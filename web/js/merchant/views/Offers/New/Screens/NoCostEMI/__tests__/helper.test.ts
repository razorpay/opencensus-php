import {
  isLowCostAmountMissing,
  isMerchantDiscountValid,
  isOfferTypeAbsent,
  filterNoCostTenures,
  getAllLowCostTenures,
} from 'merchant/views/Offers/New/Screens/NoCostEMI/helpers/helper';
import {
  OfferStateType,
  EMI_OFFER_TYPES,
  LowCostOfferType,
} from 'merchant/views/Offers/New/Screens/NoCostEMI/types';

describe('Validate: isMerchantDiscountValid', () => {
  test('Should return true if merchant payback is more than merchant borne interest', () => {
    expect(isMerchantDiscountValid(0.01, 2)).toBe(true);
  });
  test('Should return false if merchant payback is less than merchant borne interest', () => {
    expect(isMerchantDiscountValid(3.2, 2)).toBe(false);
  });
});

describe('Validate: isOfferTypeAbsent', () => {
  const sampleOfferData: OfferStateType = {
    '3': {
      tenure: 3,
    },
  };

  test('Should return truthy value if offer type is not selected', () => {
    expect(isOfferTypeAbsent(sampleOfferData)).toBeTruthy();
  });

  test('Should return false value if offer type is selected', () => {
    sampleOfferData['3'].offer_type = EMI_OFFER_TYPES.NO_COST;
    expect(isOfferTypeAbsent(sampleOfferData)).toBeFalsy();
  });
});

describe('Validate: isLowCostAmountMissing', () => {
  const sampleOfferData: OfferStateType = {
    '3': {
      tenure: 3,
      offer_type: EMI_OFFER_TYPES.LOW_COST,
    },
  };
  test('low cost offer + amount missing + return true', () => {
    expect(isLowCostAmountMissing(sampleOfferData)).toBeTruthy();
  });

  test('low cost offer + amount present + return false', () => {
    sampleOfferData['3'].merchant_discount = '0.01';
    sampleOfferData['3'].valid = true;
    expect(isLowCostAmountMissing(sampleOfferData)).toBeFalsy();
  });
});

describe('Validate: filterNoCostTenures', () => {
  const noCostTenures = [2, 3, 6, 9, 12];
  const lowCostOffers: LowCostOfferType[] = [
    {
      tenure: 3,
      issuer: 'HDFC',
      discount_to_avail: {
        discount_percentage: 1.2,
        applicable_values: null,
      },
    },
  ];
  test('should filter out low cost offer tenures from emi_durations', () => {
    expect(filterNoCostTenures(noCostTenures, lowCostOffers)).toStrictEqual([2, 6, 9, 12]);
  });
});

describe('Validate: getAllLowCostTenures', () => {
  const lowCostOffers: LowCostOfferType[] = [
    {
      tenure: 3,
      issuer: 'HDFC',
      discount_to_avail: {
        discount_percentage: 1.2,
        applicable_values: null,
      },
    },
  ];
  test('Should get all tenures for which low cost offer was selected', () => {
    expect(getAllLowCostTenures(lowCostOffers)).toStrictEqual([3]);
  });
});
