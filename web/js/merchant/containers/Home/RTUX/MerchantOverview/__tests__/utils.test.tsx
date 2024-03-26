import { getGreetingAndDate } from 'merchant/containers/Home/RTUX/MerchantOverview/utils';

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
