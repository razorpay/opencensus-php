import { getUser } from 'merchant/store';
import { isOrgFeatureExist } from 'merchant/models/User';

export const showNoExpiryPL = () => {
  const user = getUser();
  const orgFeatureEnabled = isOrgFeatureExist('hide_no_expiry_for_pl');
  const merchantFeatureEnabled = user?.isMerchantExpiryPL;
  return orgFeatureEnabled ? merchantFeatureEnabled : true;
};

export const showPayerNamePL = () => {
  return isOrgFeatureExist('enable_payer_name_for_pl');
};
