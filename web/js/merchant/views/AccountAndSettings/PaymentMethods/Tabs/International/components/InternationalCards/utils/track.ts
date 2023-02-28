import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { AnyObject } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/typings';
import { Modules } from 'common/constant/enums';

export const trackIEEvent = ({
  objectName,
  actionName,
  properties = {},
  subSection = 'International Cards',
}: {
  objectName: string;
  actionName: string;
  subSection?: string;
  properties?: AnyObject;
}): void => {
  const _props = {
    version: 'v2',
    section: 'International Payments',
    subSection,
    ...properties,
  };

  analyticsTrackWithUserInfo({
    objectName,
    actionName,
    screen: Modules.AccountAndSettings,
    properties: _props,
    addUserProperties: true,
  });
};
