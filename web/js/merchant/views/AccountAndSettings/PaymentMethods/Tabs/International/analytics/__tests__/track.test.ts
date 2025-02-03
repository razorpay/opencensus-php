import { analyticsTrack } from 'common/utils/analytics';
import { track } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/analytics/track';

jest.mock('common/utils/analytics', () => ({
  __esModule: true,
  analyticsTrack: jest.fn(),
}));

describe('Test international track util', () => {
  it('should track international payments settings event with default values', () => {
    const actionName = 'render';
    const properties = { objectName: 'international payments settings' };
    const expected = {
      screen: 'Account & Settings',
      properties: {
        section: 'international payments settings',
        ...properties,
      },
      actionName,
      objectName: properties.objectName,
    };

    track(actionName, properties);

    expect(analyticsTrack).toHaveBeenCalledWith(expected);
  });
});
