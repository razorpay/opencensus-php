import { isMobile } from 'common/utils/validators';

const IN_MOBILE_NUMBER = [
  {
    number: '9876543210',
    isValid: true,
  },
  {
    number: '7654321089',
    isValid: true,
  },
  {
    number: '3876543210',
    isValid: false,
  },
  {
    number: '987654321',
    isValid: false,
  },
];

const MY_MOBILE_NUMBER = [
  {
    number: '123456789',
    isValid: true,
  },
  {
    number: '1123456789',
    isValid: true,
  },
  {
    number: '623456789',
    isValid: false,
  },
  {
    number: '1223456789',
    isValid: false,
  },
  {
    number: '+601223456789',
    isValid: false,
  },
  {
    number: '+60122345678',
    isValid: false,
  },
];
describe('test for isMobile function', () => {
  describe('test scenarios for india', () => {
    test.each(IN_MOBILE_NUMBER)('', ({ number, isValid }) => {
      expect(isMobile(number)).toBe(isValid);
      expect(isMobile(number, 'IN')).toBe(isValid);
    });
  });

  describe('test scenarios for malaysia', () => {
    test.each(MY_MOBILE_NUMBER)('', ({ number, isValid }) => {
      expect(isMobile(number, 'MY')).toBe(isValid);
    });
  });
});
