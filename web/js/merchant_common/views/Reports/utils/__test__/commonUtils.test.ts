import { getDefaultUserObj } from 'merchant/models/__tests__/mocks/fixtures/User';

import { getAvailableFormats } from 'merchant_common/views/Reports/utils/commonUtils';
import {
  DEFAULT_FORMATS,
  RPT_FORMAT,
} from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport/components/Formats/constants';

describe('getAvailableFormats', () => {
  test('should return default formats when feature flag - isCustomReportExtensionsEnabled is not enabled', () => {
    const user = getDefaultUserObj();

    const availableFormats = getAvailableFormats(user);

    expect(availableFormats.length).toBe(DEFAULT_FORMATS.length);
  });

  test('should return rpt extension along with default formats when feature flag - isCustomReportExtensionsEnabled is enabled', () => {
    const user = getDefaultUserObj();

    user.isOrgFeatureEnabled = jest.fn().mockReturnValueOnce(true);

    const availableFormats = getAvailableFormats(user);

    expect(availableFormats.length).toBe(DEFAULT_FORMATS.length + 1);
    expect(availableFormats).toEqual(expect.arrayContaining([RPT_FORMAT]));
  });
});
