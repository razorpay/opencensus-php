import { getSplitzExperiments } from 'common/utils/__test__/mocks/fixtures';
import { isMobile, flexibleDevUrl, validateAmount } from 'common/utils/validators';
import abExperimentsMap from 'merchant/utils/abExperimentsMap';

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
    test.each(IN_MOBILE_NUMBER)(
      'should be valid mobile number for india',
      ({ number, isValid }) => {
        expect(isMobile(number)).toBe(isValid);
        expect(isMobile(number, 'IN')).toBe(isValid);
      },
    );
  });
  describe('test scenarios for malaysia', () => {
    test.each(MY_MOBILE_NUMBER)(
      'should be valid mobile number for malaysia',
      ({ number, isValid }) => {
        expect(isMobile(number, 'MY')).toBe(isValid);
      },
    );
  });
});

describe('flexibleDevUrl', () => {
  it('matches valid URLs', () => {
    expect(flexibleDevUrl('https://www.example.com')).toBe(true);
    expect(flexibleDevUrl('www.example.com')).toBe(true);
    expect(flexibleDevUrl('http://www.example.com:8080/path/to/resource')).toBe(true);
    expect(flexibleDevUrl('https://subdomain.example.com/path/to/resource.html')).toBe(true);
    expect(flexibleDevUrl('http://www.example.com/path/to/resource/with-dashes?query=string')).toBe(
      true,
    );
    expect(flexibleDevUrl('https://www.example.com?param1=value1&param2=value2')).toBe(true);
    expect(flexibleDevUrl('http://www.example.com/path/to/resource#fragment')).toBe(true);
    expect(flexibleDevUrl('http://www.example.com/path/to/resource/#fragment')).toBe(true);
  });

  it('does not match invalid URLs', () => {
    expect(flexibleDevUrl('http://www.example.com:8080/path with spaces')).toBe(false);
    expect(flexibleDevUrl('ftp://www.example.com')).toBe(false);
    expect(flexibleDevUrl('http://www.example.com/path/to/resource/with spaces?query=string')).toBe(
      false,
    );
  });
});

describe('Tests for validateAmount', () => {
  beforeAll(() => {
    window.rzp_user = {
      splitz_experiments: getSplitzExperiments(abExperimentsMap.n_exponent_support),
    };
  });

  test('function should handle different input types gracefully', () => {
    expect(validateAmount(123.123, 100, 'INR')).toBe(
      'Amount must be a number in the format 123.45',
    );

    expect(validateAmount(123.12, 100, 'INR')).toBe(undefined);
  });

  test('function should return error when amount is invalid', () => {
    expect(validateAmount('123.123', 100, 'INR')).toBe(
      'Amount must be a number in the format 123.45',
    );

    expect(validateAmount('1,123.123', 100, 'INR')).toBe(
      'Amount must be a number in the format 123.45',
    );

    expect(validateAmount('1q123.123', 100, 'INR')).toBe(
      'Amount must be a number in the format 123.45',
    );
  });

  test('function should return error when currency is KWD and amount is greater than 3 decimal', () => {
    expect(validateAmount('123.1234', 100, 'KWD')).toBe(
      'Amount must be a number in the format 123.450',
    );
  });

  test('function should return error when currency is INR and amount is greater than 2 decimal', () => {
    expect(validateAmount('123.1234', 100, 'INR')).toBe(
      'Amount must be a number in the format 123.45',
    );
  });

  test('function should not return error when currency is KWD and amount is 3 decimal or less', () => {
    expect(validateAmount('123.120', 100, 'KWD')).toBe(undefined);

    expect(validateAmount('123.12', 100, 'KWD')).toBe(undefined);

    expect(validateAmount('123.1', 100, 'KWD')).toBe(undefined);

    expect(validateAmount('123', 100, 'KWD')).toBe(undefined);
  });

  test('function should not return error when currency is INR and amount is 2 decimal or less', () => {
    expect(validateAmount('123.12', 100, 'INR')).toBe(undefined);

    expect(validateAmount('123.1', 100, 'INR')).toBe(undefined);

    expect(validateAmount('123', 100, 'INR')).toBe(undefined);
  });

  test('should return error message when currency is KWD and the 3rd decimal digit is not zero', () => {
    expect(validateAmount('123.123', 100, 'KWD')).toBe(
      'Last digit should be 0 for three decimal currencies',
    );
  });

  test('function should return error when currency is INR/KWD and amount is less than min value', () => {
    expect(validateAmount('23', 100, 'INR')).toBe('Amount must be at least 100');

    expect(validateAmount('23', 100, 'KWD')).toBe('Amount must be at least 100');
  });

  test('function should not return error when empty string is passed', () => {
    expect(validateAmount('', 100, 'INR')).toBe(undefined);
  });
});
