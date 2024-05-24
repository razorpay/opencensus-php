import { isConfigTagAPISupported } from '../rzp-utils';

describe('Tests for isConfigTagAPISupported', () => {
  test('should return the country code if it is supported', () => {
    const result = isConfigTagAPISupported('MY');
    expect(result).toBe('MY');
  });

  test('should return the country code if it is supported (another supported country)', () => {
    const result = isConfigTagAPISupported('SG');
    expect(result).toBe('SG');
  });

  test('should return undefined if the country code is not supported', () => {
    const result = isConfigTagAPISupported('US');
    expect(result).toBeUndefined();
  });

  test('should return undefined if the input is null', () => {
    const result = isConfigTagAPISupported(null);
    expect(result).toBeUndefined();
  });

  test('should return undefined if the input is empty', () => {
    const result = isConfigTagAPISupported('');
    expect(result).toBeUndefined();
  });
});
