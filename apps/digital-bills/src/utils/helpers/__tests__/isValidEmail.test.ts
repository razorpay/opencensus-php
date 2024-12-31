import isValidEmail from '@apps/digital-bills/src/utils/helpers/isValidEmail';

describe('isValidEmail', () => {
  test('should return true for valid email', () => {
    expect(isValidEmail('test@razorpay.com')).toBe(true);
  });
  test('should return false for invalid email', () => {
    expect(isValidEmail('test@razorpay')).toBe(false);
  });
});
