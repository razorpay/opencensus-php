import { getGreetingAndDate } from 'merchant/containers/Home/RTUX/MerchantOverview/utils';
import { getFormattedDateFromTimestamp } from '../MerchantOverviewData/Settlement/utils';

describe('MerchantOverview - getGreetingAndDate', () => {
  test('should return correct greeting and formatted date for morning', () => {
    const mockDate = new Date(2022, 0, 1, 9, 0, 0);
    const result = getGreetingAndDate(mockDate);

    expect(result.greeting).toBe('Good morning');
  });

  test('should return correct greeting and formatted date for afternoon', () => {
    const mockDate = new Date(2022, 0, 1, 14, 0, 0);
    const result = getGreetingAndDate(mockDate);

    expect(result.greeting).toBe('Good afternoon');
  });

  test('should return correct greeting and formatted date for evening', () => {
    const mockDate = new Date(2022, 0, 1, 20, 0, 0);
    const result = getGreetingAndDate(mockDate);

    expect(result.greeting).toBe('Good evening');
  });
});

describe('getFormattedDateFromTimestamp', () => {
  test('should return the correct formatted date for a valid timestamp', () => {
    const timestamp = 1609459200; // Jan 1, 2021
    const result = getFormattedDateFromTimestamp(timestamp);
    expect(result).toBe('January 1');
  });

  test('should return the correct formatted date for a timestamp in the middle of the month', () => {
    const timestamp = 1615766400; // Mar 15, 2021
    const result = getFormattedDateFromTimestamp(timestamp);
    expect(result).toBe('March 15');
  });

  test('should return the correct formatted date for a timestamp at the end of the month', () => {
    const timestamp = 1617235200; // Apr 1, 2021
    const result = getFormattedDateFromTimestamp(timestamp);
    expect(result).toBe('April 1');
  });

  test('should return the correct formatted date for a timestamp on the last day of the year', () => {
    const timestamp = 1640995200; // Jan 1, 2022
    const result = getFormattedDateFromTimestamp(timestamp);
    expect(result).toBe('January 1');
  });

  test('should return the correct formatted date for a timestamp on the first day of the year', () => {
    const timestamp = 1609459200; // Jan 1, 2021
    const result = getFormattedDateFromTimestamp(timestamp);
    expect(result).toBe('January 1');
  });
});
