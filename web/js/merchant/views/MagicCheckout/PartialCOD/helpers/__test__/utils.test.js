import { PREPAID_PAYMENY_AMOUNT_ITEM_TYPE } from 'merchant/views/MagicCheckout/PartialCOD/types';
import { i18nifyConvertToMajorUnit } from 'merchant/views/Transactions/v2/common/utils';
import {
  initializeSlabData,
  getSlabCustomerRiskText,
} from 'merchant/views/MagicCheckout/PartialCOD/helpers/utils';

jest.mock('merchant/views/Transactions/v2/common/utils', () => ({
  i18nifyConvertToMajorUnit: jest.fn(),
}));

describe('getSlabCustomerRiskText', () => {
  it('should return "Any" when 3 or more risk categories are provided', () => {
    const categories = ['high', 'medium', 'low'];
    expect(getSlabCustomerRiskText(categories)).toBe('Any');
  });

  it('should return formatted string for fewer than 3 categories', () => {
    const categories = ['high', 'medium'];
    expect(getSlabCustomerRiskText(categories)).toBe('High and Medium');
  });

  it('should capitalize and joins single category', () => {
    const categories = ['high'];
    expect(getSlabCustomerRiskText(categories)).toBe('High');
  });

  it('should return an empty string for non-array input', () => {
    expect(getSlabCustomerRiskText(null)).toBe('');
  });
});

describe('initializeSlabData', () => {
  const mockData = {
    type: PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT,
    value: 1000,
    rules: {
      min_order_amount: 200,
      max_order_amount: 500,
      customer_risk_category: ['high', 'medium'],
    },
  };

  beforeEach(() => {
    i18nifyConvertToMajorUnit.mockImplementation((value) => value / 100);
  });

  it('should convert flat amount to major units when type is FLAT', () => {
    const result = initializeSlabData(mockData);
    expect(i18nifyConvertToMajorUnit).toHaveBeenCalledWith(1000);
    expect(result.value).toBe('10');
  });

  it('should not convert percentage values', () => {
    const percentageData = { ...mockData, type: PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.PERCENTAGE };
    const result = initializeSlabData(percentageData);
    expect(result.value).toBe(String(percentageData.value));
  });

  it('should convert min and max order amounts to major units', () => {
    const result = initializeSlabData(mockData);
    expect(i18nifyConvertToMajorUnit).toHaveBeenCalledWith(200);
    expect(i18nifyConvertToMajorUnit).toHaveBeenCalledWith(500);
    expect(result.rules.min_order_amount).toBe('2');
    expect(result.rules.max_order_amount).toBe('5');
  });

  it('should set max_order_amount to undefined if it is falsy', () => {
    const dataWithoutMax = {
      ...mockData,
      rules: { ...mockData.rules, max_order_amount: undefined },
    };
    const result = initializeSlabData(dataWithoutMax);
    expect(result.rules.max_order_amount).toBeUndefined();
  });
});
