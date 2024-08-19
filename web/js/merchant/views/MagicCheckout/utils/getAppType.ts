import {
  MAGIC_APP_NAME,
  RCOD_APP_NAME,
  SOPC_APP_NAME,
} from 'merchant/views/MagicCheckout/common/constants';

export const getAppType = (dashboardView: string) => {
  let appType = MAGIC_APP_NAME;
  if (dashboardView === RCOD_APP_NAME || dashboardView === SOPC_APP_NAME) appType = SOPC_APP_NAME;
  return appType;
};
