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
      localStorage.setItem(
        KEY,
        JSON.stringify({
          count: 4,
          expireAt: moment().add(1, 'days').format(),
        }),
      );
      expect(isVisible()).toBeFalsy();
      localStorage.removeItem(KEY);
    });
    test('should hide ftux banner is current time is before expire time', () => {
      localStorage.setItem(
        KEY,
        JSON.stringify({
          count: 1,
          expireAt: moment().add(1, 'days').format(),
        }),
      );
      expect(isVisible()).toBeFalsy();
      localStorage.removeItem(KEY);
    });
    test('should display true when count is less than threshold and current time is more that expire time', () => {
      localStorage.setItem(
        KEY,
        JSON.stringify({
          count: 1,
          expireAt: moment().subtract(1, 'days').format(),
        }),
      );
      expect(isVisible()).toBeTruthy();
      localStorage.removeItem(KEY);
    });
  });

  describe('Hide visibility', () => {
    const getStorageData = () => {
      const storage = localStorage.getItem(KEY) || '{}';
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
      localStorage.setItem(
        KEY,
        JSON.stringify({
          count: 4,
          expireAt: moment().subtract(1, 'days').format(),
        }),
      );
      hideFtux({ onGotIt: true });
      const data = getStorageData();
      expect(data.count).toEqual(4);
      localStorage.removeItem(KEY);
    });
  });
});
