import store from 'merchant/store';
import {
  convertToMajorUnitInUserCurrency,
  convertToMinorUnitInUserCurrency,
} from 'merchant/utils/currency';

let mockCurrency = 'INR';
store.getState = jest.fn(() => ({
  session: {
    user: {
      merchant: {
        currency: mockCurrency,
      },
    },
  },
}));

describe('Merchant currency utils', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockCurrency = 'INR';
  });

  it('should convert using INR currency for INR user', () => {
    expect(convertToMajorUnitInUserCurrency(1234567)).toBe(12345.67);
    expect(convertToMinorUnitInUserCurrency(1234567)).toBe(123456700);
  });

  it('should convert to major unit for a non-100 currency for different countries', () => {
    const edge_cases = [
      ['KWD', 1234.567],
      ['BHD', 1234.567],
      ['OMR', 1234.567],
      ['JPY', 1234567],
    ];
    const utilOutputs = edge_cases.map(([countryCode, _]) => {
      mockCurrency = countryCode as string;
      const formattedValue = convertToMajorUnitInUserCurrency(1234567);
      return [countryCode, formattedValue];
    });
    expect(utilOutputs).toStrictEqual(edge_cases);
  });
  it('should convert to minor unit for a non-100 currency for different countries', () => {
    const edge_cases = [
      ['KWD', 1234567000],
      ['BHD', 1234567000],
      ['OMR', 1234567000],
      ['JPY', 1234567],
    ];
    const utilOutputs = edge_cases.map(([countryCode, _]) => {
      mockCurrency = countryCode as string;
      const formattedValue = convertToMinorUnitInUserCurrency(1234567);
      return [countryCode, formattedValue];
    });
    expect(utilOutputs).toStrictEqual(edge_cases);
  });
});
