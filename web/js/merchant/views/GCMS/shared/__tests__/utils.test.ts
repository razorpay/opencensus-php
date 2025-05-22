import {
  convertUnixToShortDate,
  displayExpiryValidity,
  isNonNegativeInteger,
  isValidNumber,
} from '../utils';

describe('GCMS shared Utils', () => {
  test('displayExpiryValidity displays correct days', () => {
    const program = {
      policies: {
        gift_card_validity_period_count: 3,
        gift_card_validity_period: 'day',
      },
    };
    const expected = '3 Days';
    const result = displayExpiryValidity(program);
    expect(result).toBe(expected);
  });
  test('displayExpiryValidity is backwards compatible with the old key', () => {
    const program = {
      policies: {
        gift_card_validity_in_days: 3,
      },
    };
    const expected = '3 Days';
    const result = displayExpiryValidity(program);
    expect(result).toBe(expected);
  });
  test('convertUnixToShortDate gives correct human readable date', () => {
    const ts = Date.now();
    const date = new Date(ts);
    const shortDate = convertUnixToShortDate(ts / 1000);
    expect(shortDate).toContain(String(date.getDate()));
  });
  test('isNonNegativeInteger on positive and negative numbers', () => {
    expect(isNonNegativeInteger('3')).toBeTruthy();
    expect(isNonNegativeInteger('0')).toBeTruthy();
    expect(isNonNegativeInteger('-0')).toBeFalsy();
    expect(isNonNegativeInteger('-3')).toBeFalsy();
    expect(isNonNegativeInteger('')).toBeFalsy();
  });
  test('isValidNumber on positive and negative numbers', () => {
    expect(isValidNumber('3.3')).toBeTruthy();
    expect(isValidNumber('0.3')).toBeTruthy();
    expect(isValidNumber('-0')).toBeFalsy();
    expect(isValidNumber('abcd')).toBeFalsy();
    expect(isValidNumber('..3')).toBeFalsy();
  });
});
