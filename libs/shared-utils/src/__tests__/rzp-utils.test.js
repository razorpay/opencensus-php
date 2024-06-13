import { isConfigTagAPISupported } from '../rzp-utils';

describe('Tests for isConfigTagAPISupported', () => {
  [
    {
      input: 'MY',
      output: 'MY',
    },
    {
      input: 'SG',
      output: 'SG',
    },
    {
      input: 'UK',
      output: 'UK',
    },
    {
      input: 'US',
      output: 'US',
    },
    {
      input: 'ID',
      output: 'ID',
    },
    {
      input: 'TH',
      output: 'TH',
    },
  ].forEach(({ input, output }) => {
    test('should return the country code if it is supported', () => {
      const result = isConfigTagAPISupported(input);
      expect(result).toBe(output);
    });
  });

  test('should return undefined if the country code is not supported', () => {
    const result = isConfigTagAPISupported('CN');
    expect(result).toBeUndefined();
  });
  test('should return undefined if the input is empty or undefined or null', () => {
    const result1 = isConfigTagAPISupported('');
    const result2 = isConfigTagAPISupported(undefined);
    const result3 = isConfigTagAPISupported(null);
    expect(result1).toBeUndefined();
    expect(result2).toBeUndefined();
    expect(result3).toBeUndefined();
  });
});
