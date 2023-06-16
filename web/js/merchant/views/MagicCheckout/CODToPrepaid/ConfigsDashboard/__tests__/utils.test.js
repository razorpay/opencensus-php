import {
  getConversionString,
  getDiscountString,
  getExpiryTimeString,
  getConvertOrderOnString,
  getConvertRiskCategoryArray,
  isValidDuration,
} from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/utils';

import { getTimeInSeconds } from 'merchant/views/MagicCheckout/helper';

import {
  UTILS_DUMMY,
  SAVED_CONFIGS_VALUES,
  VALID_DURATION_VALUES,
} from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/__tests__/mocks/fixtures';

describe('testing utility functions', () => {
  test.each(UTILS_DUMMY)('should return the correct value', (item) => {
    expect(getConversionString(item.configs)).toBe(item.riskResponse);
    expect(getDiscountString(item.configs)).toBe(item.discountResponse);
    expect(getExpiryTimeString(item.configs)).toBe(item.expireTimeResponse);
    expect(getConvertOrderOnString(item.configs)).toBe(item.conversionPlatformResponse);
  });

  test.each(SAVED_CONFIGS_VALUES)('should return correct payload values', (item) => {
    const { riskCategory, isManualReviewOpted } = item.configs;
    const { durationVal, riskResponse, durationResponse } = item;

    expect(getConvertRiskCategoryArray(riskCategory, isManualReviewOpted)).toStrictEqual(
      riskResponse,
    );
    expect(getTimeInSeconds(durationVal)).toBe(durationResponse);
  });

  test.each(VALID_DURATION_VALUES)('should return valid duration values', (item) => {
    const { hours, mins, response } = item;
    expect(isValidDuration(hours, mins)).toBe(response);
  });
});
