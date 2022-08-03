import moment from 'moment';
import { getStartDateFromDiff } from 'common/utils/rzp-utils';

test('check the startDate is a valid date', () => {
  const date = moment(1659506324000);
  expect(getStartDateFromDiff(7776000, date).format('DD/MM/YYYY')).toBe('05/05/2022');
  expect(getStartDateFromDiff(2592000, date).format('DD/MM/YYYY')).toBe('04/07/2022');
  expect(getStartDateFromDiff(604800, date).format('DD/MM/YYYY')).toBe('27/07/2022');
});
