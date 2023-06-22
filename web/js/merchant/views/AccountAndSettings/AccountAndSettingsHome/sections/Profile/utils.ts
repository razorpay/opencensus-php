import { Modules } from 'common/constant/enums';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';

const analyticsConfig = {
  name: {
    selfServeAction: 'User Name Updated',
    objectName: 'user name update',
    actionName: 'status',
  },
  display_name: {
    selfServeAction: 'Display Name Updated',
    objectName: 'display name update',
    actionName: 'status',
  },
};

export const handleUpdateAnalytics = ({
  properties,
  id,
}: {
  properties: Record<string, string>;
  id: string;
}): void => {
  const { selfServeAction, objectName, actionName } = analyticsConfig[id];
  selfServeTrackSuccess({
    selfServeAction,
    page: 'Personal Profile',
    screen: Modules.AccountAndSettings,
  });
  analyticsTrackWithUserInfo({
    objectName,
    actionName,
    screen: Modules.AccountAndSettings,
    properties: {
      status: 'success',
      ...properties,
    },
  });
};
