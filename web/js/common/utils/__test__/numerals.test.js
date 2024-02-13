import { i18nifyHumanReadable } from 'common/utils/numerals';

describe('i18nifyHumanReadable', () => {
  it('should format number in human-readable format with currency', () => {
    const formattedValue = i18nifyHumanReadable(1234567, 'USD');
    expect(formattedValue).toBe('$1.23M');
  });

  it('should format number in human-readable format without currency', () => {
    const formattedValue = i18nifyHumanReadable(987654, '');
    expect(formattedValue).toBe('987.654K');
  });
});
