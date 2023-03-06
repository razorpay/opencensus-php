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
    number: '60102106280',
    isValid: true,
  },
  {
    number: '60128851782',
    isValid: true,
  },
  {
    number: '60132758792',
    isValid: true,
  },
  {
    number: '60146966910',
    isValid: true,
  },
  {
    number: '60164254280',
    isValid: true,
  },
  {
    number: '60176996557',
    isValid: true,
  },
  {
    number: '60189614604',
    isValid: true,
  },
  {
    number: '60193045898',
    isValid: true,
  },
  {
    number: '601117058440',
    isValid: true,
  },
  {
    number: '0102106280',
    isValid: true,
  },
  {
    number: '0128851782',
    isValid: true,
  },
  {
    number: '0132758792',
    isValid: true,
  },
  {
    number: '0146966910',
    isValid: true,
  },
  {
    number: '0164254280',
    isValid: true,
  },
  {
    number: '0176996557',
    isValid: true,
  },
  {
    number: '0189614604',
    isValid: true,
  },
  {
    number: '0193045898',
    isValid: true,
  },
  {
    number: '01117058440',
    isValid: true,
  },
  {
    number: '102106280',
    isValid: false,
  },
  {
    number: '128851782',
    isValid: false,
  },
  {
    number: '132758792',
    isValid: false,
  },
  {
    number: '146966910',
    isValid: false,
  },
  {
    number: '164254280',
    isValid: false,
  },
  {
    number: '176996557',
    isValid: false,
  },
  {
    number: '189614604',
    isValid: false,
  },
  {
    number: '193045898',
    isValid: false,
  },
  {
    number: '1117058440',
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
