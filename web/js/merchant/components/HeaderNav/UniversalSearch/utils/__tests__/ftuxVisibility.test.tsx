import '@testing-library/jest-dom/extend-expect';
import {
  hideFtux,
  isVisible,
} from 'merchant/components/HeaderNav/UniversalSearch/utils/ftuxVisibility';
import moment from 'moment';

const KEY = 'universal-search-ftux';

describe('FtuxVisibility Utils', () => {
  describe('Is Visible util', () => {
    test('should return true if not key present', () => {
      expect(isVisible()).toBeTruthy();
    });
    test('should hide ftux banner if display count is more than threshold', () => {
      window?.localStorage.setItem(
        KEY,
        JSON.stringify({
          count: 4,
          expireAt: moment().add(1, 'days').format(),
        }),
      );
      expect(isVisible()).toBeFalsy();
      window?.localStorage.removeItem(KEY);
    });
    test('should hide ftux banner is current time is before expire time', () => {
      window?.localStorage.setItem(
        KEY,
        JSON.stringify({
          count: 1,
          expireAt: moment().add(1, 'days').format(),
        }),
      );
      expect(isVisible()).toBeFalsy();
      window?.localStorage.removeItem(KEY);
    });
    test('should display true when count is less than threshold and current time is more that expire time', () => {
      window?.localStorage.setItem(
        KEY,
        JSON.stringify({
          count: 1,
          expireAt: moment().subtract(1, 'days').format(),
        }),
      );
      expect(isVisible()).toBeTruthy();
      window?.localStorage.removeItem(KEY);
    });
  });

  describe('Hide visibility', () => {
    const getStorageData = () => {
      const storage = window?.localStorage.getItem(KEY) || '{}';
      return JSON.parse(storage);
    };

    test('should set initial count if not key present in storage', () => {
      hideFtux({ onGotIt: true });
      const data = getStorageData();
      expect(data.count).toEqual(1);
    });
    test('should update count if key is already present in storage', () => {
      hideFtux({ onGotIt: true });
      const data = getStorageData();
      expect(data.count).toEqual(2);
    });
    test('should not update anything if count breached the display threshold', () => {
      window?.localStorage.setItem(
        KEY,
        JSON.stringify({
          count: 4,
          expireAt: moment().subtract(1, 'days').format(),
        }),
      );
      hideFtux({ onGotIt: true });
      const data = getStorageData();
      expect(data.count).toEqual(4);
      window?.localStorage.removeItem(KEY);
    });
  });
});
