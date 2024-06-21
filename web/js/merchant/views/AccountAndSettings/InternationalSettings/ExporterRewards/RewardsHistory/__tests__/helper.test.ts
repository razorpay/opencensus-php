import { formatTimestampRange } from '../helper';

describe('formatTimestampRange', () => {
  test('should format dates within the same year correctly', () => {
    const start_date = 1654041600; // 1 Jun 2022
    const end_date = 1656633600; // 1 Jul 2022
    const expected = '01 Jun - 01 Jul 2022';
    expect(formatTimestampRange(start_date, end_date)).toEqual(expected);
  });

  test('should format dates across different years correctly', () => {
    const start_date = 1609459200; // 1 Jan 2021
    const end_date = 1640995200; // 1 Jan 2022
    const expected = '01 Jan 2021 - 01 Jan 2022';
    expect(formatTimestampRange(start_date, end_date)).toEqual(expected);
  });

  test('should handle the case where start and end timestamps are the same', () => {
    const timestamp = 1654041600; // 1 Jun 2022
    const expected = '01 Jun - 01 Jun 2022';
    expect(formatTimestampRange(timestamp, timestamp)).toEqual(expected);
  });

  test('should return an empty string if start or end timestamp is invalid', () => {
    const start_date = 0; // Invalid timestamp
    const end_date = 1656633600; // 1 Jul 2022
    const expected = '';
    expect(formatTimestampRange(start_date, end_date)).toEqual(expected);
  });

  test('returns an empty string if either timestamp is NaN', () => {
    const start_date = NaN; // Not a Number
    const end_date = 1656633600; // 1 Jul 2022
    const expected = '';
    expect(formatTimestampRange(start_date, end_date)).toEqual(expected);
  });

  test('returns an empty string if either timestamp is undefined', () => {
    const start_date = undefined; // Undefined timestamp
    const end_date = 1656633600; // 1 Jul 2022
    const expected = '';
    expect(formatTimestampRange(start_date, end_date)).toEqual(expected);
  });
});
