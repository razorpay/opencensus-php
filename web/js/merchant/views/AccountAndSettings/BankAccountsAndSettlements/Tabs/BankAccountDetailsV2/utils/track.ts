import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';
import { AnyObject } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/typings';
import { Modules } from 'common/constant/enums';

export const trackBankAccountUpdateEvent = ({
  objectName,
  actionName,
  properties = {},
}: {
  objectName: string;
  actionName: string;
  properties?: AnyObject;
}): void => {
  const propertiesWithUserInfo = {
    version: 'v2',
    tab: 'Bank Account Details',
    ...properties,
    ...getCommonAnalyticsProperties(window.rzp_user, {
      addUserProperties: true,
    }),
  };

  analyticsTrack({
    objectName,
    actionName,
    screen: Modules.AccountAndSettings,
    properties: propertiesWithUserInfo,
  });
};
