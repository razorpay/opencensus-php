import parseUserAgent from '@apps/digital-bills/src/utils/helpers/parseUserAgent';

const mocks = {
  userAgentString:
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
};

describe('parseUserAgent', () => {
  test('should return correct user agent details', () => {
    const userAgent = parseUserAgent(mocks.userAgentString);
    expect(userAgent).toStrictEqual({
      browser: 'Chrome',
      os: 'Mac OS',
      device: 'Macintosh',
    });
  });
});
