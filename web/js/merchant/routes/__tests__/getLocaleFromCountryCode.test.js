import { getLocaleFromCountryCode } from '../constants';

describe('getLocaleFromCountryCode', () => {
  test.each([
    ['us', 'en-US'], // lowercase input for United States
    ['MY', 'en-MY'], // Malaysia
    ['SG', 'en-SG'], // Singapore
    ['ID', 'en-ID'], // Indonesia
    ['GB', 'en-GB'], // United Kingdom
    ['TH', 'en-TH'], // Thailand
    ['In', 'en-IN'], // mixed case input for India
  ])('correctly converts %s to locale string %s', (input, expected) => {
    expect(getLocaleFromCountryCode(input)).toBe(expected);
  });

  test.each([
    [undefined, 'en'],
    [null, 'en'],
    ['', 'en'],
    ['xyz', 'en-XYZ'],
  ])('should handle invalid country codes', (input, expected) => {
    expect(getLocaleFromCountryCode(input)).toBe(expected);
  });
});
