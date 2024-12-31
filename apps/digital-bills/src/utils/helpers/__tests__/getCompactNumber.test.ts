import { getCompactNumber } from '@apps/digital-bills/src/utils/helpers/numberFormatting';

describe('getCompactNumber', () => {
  test('should format the number in compact notation', () => {
    expect(getCompactNumber(1000)).toBe('1K');
    expect(getCompactNumber(1000000)).toBe('1M');
    expect(getCompactNumber(1000000000)).toBe('1B');
  });
  test('should format the number in compact notation with specified locale', () => {
    expect(getCompactNumber(1000, 'en-IN')).toBe('1T');
    expect(getCompactNumber(1000, 'en-US')).toBe('1K');
    expect(getCompactNumber(1000000, 'en-IN')).toEqual('10L');
    expect(getCompactNumber(1000000, 'en-US')).toBe('1M');
    expect(getCompactNumber(1000000000, 'en-IN')).toBe('100Cr');
    expect(getCompactNumber(1000000000, 'en-US')).toBe('1B');
  });
});
