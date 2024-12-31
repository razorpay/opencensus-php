import { getSelectedDatesFromPicker } from '@apps/digital-bills/src/utils/helpers/getDateRangePresets';

describe('getDateRangePresets', () => {
  test('should return the selected dates from the date picker', () => {
    const result = getSelectedDatesFromPicker([
      'Tue Dec 03 2024 00:00:00 GMT+0530 (India Standard Time)',
      null,
    ]);
    expect(result).toStrictEqual({ fromDate: '2024-12-02T18:30:00.000Z', toDate: undefined });
  });
});
