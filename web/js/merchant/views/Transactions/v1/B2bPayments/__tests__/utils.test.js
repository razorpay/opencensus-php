import { getCountryName } from 'merchant/views/Transactions/v1/B2bPayments/utils';

describe('Test getCountryName function', () => {
  test('should handle null/undefined/empty string country in getCountryName', () => {
    expect(getCountryName(null)).toBeNull();
    expect(getCountryName(undefined)).toBeNull();
    expect(getCountryName('')).toBeNull();
  });

  test('should map country code to country name in getCountryName', () => {
    expect(getCountryName('us')).toBe('United States');
    expect(getCountryName('ca')).toBe('Canada');
    expect(getCountryName('xyz')).toBe('xyz');
  });
});
