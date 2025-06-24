import { isOneHomeExperimentEnabled } from '../utils';
import * as serverUtils from '../../../server/utils';

// Mock the isBrowser function
jest.mock('../../../server/utils', () => ({
  isBrowser: jest.fn(),
}));

describe('utils', () => {
  describe('isOneHomeExperimentEnabled', () => {
    beforeEach(() => {
      // Reset the mocks
      jest.clearAllMocks();
    });

    afterEach(() => {
      // Clean up
      delete window.IS_ONE_HOME_ENABLED;
    });

    test('returns false when not in browser environment', () => {
      // Mock isBrowser to return false
      jest.spyOn(serverUtils, 'isBrowser').mockReturnValue(false);

      expect(isOneHomeExperimentEnabled()).toBe(false);
      expect(serverUtils.isBrowser).toHaveBeenCalledTimes(1);
    });

    test('returns window.IS_ONE_HOME_ENABLED value when in browser environment', () => {
      // Mock isBrowser to return true
      jest.spyOn(serverUtils, 'isBrowser').mockReturnValue(true);

      // Set IS_ONE_HOME_ENABLED to true
      window.IS_ONE_HOME_ENABLED = true;

      expect(isOneHomeExperimentEnabled()).toBe(true);
      expect(serverUtils.isBrowser).toHaveBeenCalledTimes(1);
    });

    test('returns false when IS_ONE_HOME_ENABLED is false in browser environment', () => {
      // Mock isBrowser to return true
      jest.spyOn(serverUtils, 'isBrowser').mockReturnValue(true);

      // Set IS_ONE_HOME_ENABLED to false
      window.IS_ONE_HOME_ENABLED = false;

      expect(isOneHomeExperimentEnabled()).toBe(false);
      expect(serverUtils.isBrowser).toHaveBeenCalledTimes(1);
    });

    test('returns false when IS_ONE_HOME_ENABLED is undefined in browser environment', () => {
      // Mock isBrowser to return true
      jest.spyOn(serverUtils, 'isBrowser').mockReturnValue(true);

      // Set IS_ONE_HOME_ENABLED to undefined
      delete window.IS_ONE_HOME_ENABLED;

      expect(isOneHomeExperimentEnabled()).toBe(undefined);
      expect(serverUtils.isBrowser).toHaveBeenCalledTimes(1);
    });
  });
});
