import {
  getResponseTime,
  trackBankAccountDetailsChange,
} from 'merchant/views/Account/Profile/components/BankAccountDetailsChangeSteps/utils';
import * as analytics from 'common/utils/analytics';

beforeAll(() => {
  jest.spyOn(analytics, 'analyticsTrack').mockImplementation(jest.fn);
});

describe('Bank account update utils', () => {
  describe('trackBankAccountDetailsChange', () => {
    test('should call analyticsTrack with appropiate values', () => {
      const testActionName = 'test action name';
      const testObjectName = 'test object name';
      const testProperites = { testProp: true };
      const testScreen = 'Test Screen';
      trackBankAccountDetailsChange({
        objectName: testObjectName,
        actionName: testActionName,
        properties: testProperites,
        screen: testScreen,
      });
      expect(analytics.analyticsTrack).toHaveBeenCalled();
      expect(analytics.analyticsTrack).toHaveBeenCalledWith({
        actionName: testActionName,
        objectName: testObjectName,
        properties: testProperites,
        screen: testScreen,
      });
    });

    test('should call analyticsTrack with default values if not passed', () => {
      const testActionName = 'test action name';
      const testObjectName = 'test object name';
      trackBankAccountDetailsChange({
        objectName: testObjectName,
        actionName: testActionName,
      });
      expect(analytics.analyticsTrack).toHaveBeenCalled();
      expect(analytics.analyticsTrack).toHaveBeenCalledWith({
        actionName: testActionName,
        objectName: testObjectName,
        properties: {},
        screen: 'My Account',
      });
    });
  });

  describe('getResponseTime', () => {
    test('should return difference between now and passed date in seconds', () => {
      const startedAt = new Date(new Date('2022-05-21').setHours(0, 0, 0, 0));
      const endedAt = new Date(new Date('2022-05-21').setHours(0, 0, 5, 480));
      expect(getResponseTime(startedAt, endedAt)).toBe('5.48s');
    });
  });
});
