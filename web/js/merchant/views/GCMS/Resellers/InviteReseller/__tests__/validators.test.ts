import { validateResellerName, validateResellerEmail, validateResellerPhone } from '../validators';

describe('InviteReseller Validations', () => {
  describe('validateResellerName', () => {
    test('should return error for empty values', () => {
      expect(validateResellerName('')).toBe('Please fill out this field');
      expect(validateResellerName(null)).toBe('Please fill out this field');
      expect(validateResellerName(undefined)).toBe('Please fill out this field');
    });

    test('should return error for names less than 4 characters', () => {
      expect(validateResellerName('abc')).toBe('Reseller name should be at least of 4 characters');
      expect(validateResellerName('a')).toBe('Reseller name should be at least of 4 characters');
      expect(validateResellerName('12')).toBe('Reseller name should be at least of 4 characters');
    });

    test('should return false for valid names', () => {
      expect(validateResellerName('John Doe')).toBe(false);
      expect(validateResellerName('Test Company')).toBe(false);
      expect(validateResellerName('ABCD')).toBe(false);
      expect(validateResellerName('1234')).toBe(false);
      expect(validateResellerName('Valid Name With Spaces')).toBe(false);
    });

    test('should handle whitespace in names', () => {
      expect(validateResellerName('   ')).toBe('Reseller name should be at least of 4 characters');
      expect(validateResellerName(' ab ')).toBe(false);
      expect(validateResellerName('   abcd   ')).toBe(false);
    });
  });

  describe('validateResellerEmail', () => {
    test('should return error for empty values', () => {
      expect(validateResellerEmail('')).toBe('Please fill out this field');
      expect(validateResellerEmail(null)).toBe('Please fill out this field');
      expect(validateResellerEmail(undefined)).toBe('Please fill out this field');
    });

    test('should return false for any non-empty email', () => {
      // Note: Current implementation only checks for non-empty value
      expect(validateResellerEmail('test@example.com')).toBe(false);
      expect(validateResellerEmail('invalid-email')).toBe(false);
      expect(validateResellerEmail('test@test')).toBe(false);
      expect(validateResellerEmail('a@b.c')).toBe(false);
    });
  });

  describe('validateResellerPhone', () => {
    test('should return error for empty values', () => {
      expect(validateResellerPhone('')).toBe('Please fill out this field');
      expect(validateResellerPhone(null)).toBe('Please fill out this field');
      expect(validateResellerPhone(undefined)).toBe('Please fill out this field');
    });

    test('should return error for phone numbers not exactly 10 digits', () => {
      expect(validateResellerPhone('123456789')).toBe('Please fill out this field'); // 9 digits
      expect(validateResellerPhone('12345678901')).toBe('Please fill out this field'); // 11 digits
      expect(validateResellerPhone('123')).toBe('Please fill out this field');
      expect(validateResellerPhone('abcdefghij')).toBe(false);
    });

    test('should return false for valid 10-digit phone numbers', () => {
      expect(validateResellerPhone('1234567890')).toBe(false);
      expect(validateResellerPhone('9876543210')).toBe(false);
      expect(validateResellerPhone('0123456789')).toBe(false);
    });

    test('should handle phone numbers with non-digit characters', () => {
      expect(validateResellerPhone('123-456-7890')).toBe('Please fill out this field');
      expect(validateResellerPhone('(123)4567890')).toBe('Please fill out this field');
      expect(validateResellerPhone('abc1234567890')).toBe('Please fill out this field');
      expect(validateResellerPhone(' 1234567890 ')).toBe('Please fill out this field');
    });
  });
});
