import { getAvailableDelimiter } from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport/components/Formats/utils';
import { DEFAULT_FORMATS } from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport/components/Formats/constants';

const defaultFormat = { label: '', value: '' };

describe('getAvailableDelimiter', () => {
  test('should return array of delimiter if selected format value is present', () => {
    const format = DEFAULT_FORMATS.find(({ value }) => value === 'csv') || defaultFormat;
    const availableDelimiters = getAvailableDelimiter(format);

    expect(availableDelimiters).toHaveLength(1);
  });

  test('should return empty array if selected format value is not present', () => {
    const availableDelimiters = getAvailableDelimiter({ label: 'test', value: '' });

    expect(availableDelimiters).toHaveLength(0);
  });

  test('should return empty array if selected format has not delimiter options', () => {
    const availableDelimiters = getAvailableDelimiter({ label: 'test', value: 'test' });

    expect(availableDelimiters).toHaveLength(0);
  });
});
