import {
  getFormattedDate,
  getTimeUnix,
  getStartAndEndUnixTimeStampsForDaysFrom,
  extractExtensionFromTemplate,
  isLogInProgress,
  getActualLogStatus,
} from 'merchant_common/containers/ReportsAsync/utils';
import moment from 'moment';

describe('Report Utils', () => {
  test('should test getFormattedDate', () => {
    expect(getFormattedDate(1668211201)).toEqual('12 Nov 2022');
  });

  describe('Time based tests', () => {
    beforeAll(() => {
      jest.useFakeTimers('modern').setSystemTime(new Date('2022-10-10'));
    });

    afterAll(() => {
      jest.useRealTimers();
    });

    describe('getTimeUnix', () => {
      test('should return seconds passed from start of day on  passing time in unix', () => {
        // Monday, 10 October 2022 10:00:00
        expect(getTimeUnix(1665396000)).toEqual(23760);
      });
    });

    describe('getStartAndEndUnixTimeStampsForDaysFrom', () => {
      test('should return start and end unix time stamps for 1 day when no args are passed', () => {
        // Sunday, 9 October 2022 00:00:00 to Sunday, 9 October 2022 23:59:59
        expect(getStartAndEndUnixTimeStampsForDaysFrom()).toEqual([1665273600, 1665359999]);
      });

      test('should return start and end unix time stamps for 2 days when 2 days is passed', () => {
        // Saturday, 8 October 2022 00:00:00 to Sunday, 9 October 2022 23:59:59
        expect(getStartAndEndUnixTimeStampsForDaysFrom(2)).toEqual([1665187200, 1665359999]);
      });

      test('should return start and end unix time stamps for 1 day when 1 day and custom moment obj is passed', () => {
        // Saturday, 8 October 2022 00:00:00 to Saturday, 8 October 2022 23:59:59
        expect(getStartAndEndUnixTimeStampsForDaysFrom(1, moment().subtract(2, 'day'))).toEqual([
          1665187200,
          1665273599,
        ]);
      });
    });
  });

  describe('extractExtensionFromTemplate', () => {
    test('should return extension from template', () => {
      expect(
        extractExtensionFromTemplate({
          file_meta: {
            extension: 'csv',
          },
        }),
      ).toEqual('csv');
    });

    test('should return undefined if proper template format is not passed', () => {
      expect(extractExtensionFromTemplate()).toEqual(undefined);
      expect(extractExtensionFromTemplate({ file_meta: {} })).toEqual(undefined);
    });
  });

  describe('isLogInProgress', () => {
    test('should return true on passing created/processing', () => {
      ['created', 'processing'].forEach((status) => {
        expect(isLogInProgress(status)).toEqual(true);
      });
    });

    test('should return false on passing status other than created/processing', () => {
      ['test', 'failure'].forEach((status) => {
        expect(isLogInProgress(status)).toEqual(false);
      });
    });
  });

  describe('getActualLogStatus', () => {
    test('should return in-process on passing created/processing/retrying', () => {
      ['created', 'processing', 'retrying'].forEach((status) => {
        expect(getActualLogStatus({ status })).toEqual('in-process');
      });
    });

    test('should return ready-for-download on passing processed and fileId', () => {
      expect(getActualLogStatus({ status: 'processed', fileId: 'test' })).toEqual(
        'ready-for-download',
      );
    });

    test('should return no-data on passing processed and no fileId', () => {
      expect(getActualLogStatus({ status: 'processed', fileId: null })).toEqual('no-data');
    });

    test('should return error on passing failure', () => {
      expect(getActualLogStatus({ status: 'failed' })).toEqual('error');
    });

    test('should return null on passing other states', () => {
      expect(getActualLogStatus({ status: 'test' })).toEqual(null);
    });
  });
});
