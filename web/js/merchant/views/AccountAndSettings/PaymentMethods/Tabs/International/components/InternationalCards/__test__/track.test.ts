import { trackIEEvent } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/utils/track';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { Modules } from 'common/constant/enums';

const props = {
  objectName: 'test object name',
  actionName: 'test action name',
  properties: {
    a: 1,
    b: 2,
  },
};

describe('trackIEEvent', () => {
  test('should call analyticsTrackWithUserInfo on calling trackIEEvent', () => {
    trackIEEvent(props);
    expect(analyticsTrackWithUserInfo).toHaveBeenCalledWith({
      objectName: props.objectName,
      actionName: props.actionName,
      screen: Modules.AccountAndSettings,
      properties: {
        ...props.properties,
        version: 'v2',
        section: 'International Payments',
        subSection: 'International Cards',
      },
      addUserProperties: true,
    });
  });

  test('should use default empty properties on analyticsTrackWithUserInfo if properties is not passed', () => {
    trackIEEvent({
      objectName: props.objectName,
      actionName: props.actionName,
    });
    expect(analyticsTrackWithUserInfo).toHaveBeenCalledWith({
      objectName: props.objectName,
      actionName: props.actionName,
      screen: Modules.AccountAndSettings,
      properties: {
        version: 'v2',
        section: 'International Payments',
        subSection: 'International Cards',
      },
      addUserProperties: true,
    });
  });
});
