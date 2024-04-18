import { i18CurrencyConversionFromCommonUnitToMinorUnit } from 'common/utils/rzp-utils';
import {
  calculateEmi,
  checkEmiEligibility,
  getCardEmiArray,
} from 'merchant/views/Affordability/AssistedFinancing/utils';

import { eligibilityResponse, merchantMethodsResponse } from './mocks/api';
import { MockGetCardEmiResponse } from './mocks/constants';

jest.mock('../utils', () => ({
  ...(jest.requireActual('../utils') as jest.Mocked<any>),
  __esModule: true,
  checkEmiEligibility: jest.fn(),
  calculateEmiData: jest.fn(),
}));

jest.mock('common/utils/rzp-utils', () => ({
  i18CurrencyConversionFromCommonUnitToMinorUnit: jest.fn(),
}));

describe('getCardEmiArray', () => {
  it('should return an empty array if props are empty', () => {
    const result = getCardEmiArray({}, {}, 0);
    expect(Array.isArray(result)).toBe(true);
  });

  it('should correctly map the emi_options object to an array of EMI options', () => {
    (i18CurrencyConversionFromCommonUnitToMinorUnit as jest.Mock).mockReturnValue(100000);

    const emi_options = {
      HDFC: merchantMethodsResponse.data.emi_options.HDFC,
      HDFC_DC: merchantMethodsResponse.data.emi_options.HDFC_DC,
    };
    const orderAmount = 1000;

    (checkEmiEligibility as jest.Mock).mockReturnValue(false);

    const result = getCardEmiArray(emi_options, eligibilityResponse.data, orderAmount);
    expect(result).toEqual(MockGetCardEmiResponse);
  });
});

describe('calculateEmi', () => {
  it('correctly calculates the EMI when the rate is not zero', () => {
    (i18CurrencyConversionFromCommonUnitToMinorUnit as jest.Mock).mockReturnValue(100000);
    const principle = 100000;
    const duration = 12;
    const rate = 10;
    const result = calculateEmi(principle, duration, rate);
    expect(result).toBe(8791.59);
  });

  it('correctly calculates the EMI when the rate is zero', () => {
    (i18CurrencyConversionFromCommonUnitToMinorUnit as jest.Mock).mockReturnValue(100000);
    const principle = 100000;
    const duration = 12;
    const rate = 0;
    const result = calculateEmi(principle, duration, rate);
    expect(result).toBe(8334);
  });
});
