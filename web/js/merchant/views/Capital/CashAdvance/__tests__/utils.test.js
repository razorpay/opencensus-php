import moment from 'moment';
import { isADayAgo } from 'merchant/views/Capital/CashAdvance/utils';

describe('Capital/CashAdvance/utils', () => {
  describe('isADayAgo', () => {
    it('should return true if a day ago', () => {
      const dayInThePast = moment().subtract(2, 'd');
      expect(isADayAgo(dayInThePast)).toBe(true);
    });

    it('should return false if not a day ago', () => {
      const today = moment();
      expect(isADayAgo(today)).toBe(false);
    });

    it('should return false if no date is passed', () => {
      expect(isADayAgo()).toBe(false);
    });
  });
});
