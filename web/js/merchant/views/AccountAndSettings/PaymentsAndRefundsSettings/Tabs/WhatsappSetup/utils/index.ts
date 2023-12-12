import { Modules } from 'common/constant/enums';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';

// Checking for AISensy as Its is the only business provider available for now.
import { BusinessServiceProvider } from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/constants';

export const getApplicationStatus = ({ tokens }) => {
  const connectedBusinessProvider: any = tokens.filter(
    (token) => BusinessServiceProvider[0].applicationName === token.application_name,
  );
  let businessProvider = {};
  if (connectedBusinessProvider.length > 0) {
    businessProvider = {
      ...BusinessServiceProvider[0],
      ...connectedBusinessProvider[0],
    };
  }
  return {
    isActive: connectedBusinessProvider.length > 0,
    businessProvider,
  };
};

export const whatsappAccountSetupAnalyticsTrack = ({
  objectName,
  actionName,
  screen = Modules.WhatsappAccountSetup,
  properties = {},
}) => {
  analyticsTrackWithUserInfo({
    objectName,
    actionName,
    screen,
    addUserProperties: true,
    properties,
  });
};
