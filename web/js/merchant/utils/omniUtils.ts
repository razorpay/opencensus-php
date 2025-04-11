import { getUser } from '@federated/apps/shell/commonStore';

export const isOmniChannelMerchant = (user) => {
  return (
    user.isOmniEnabledMerchant || (!!user?.pos_activation_status && user?.isOmniChannelMerchant)
  );
};

export const isBillMeMerchant = ({ abExperiments }) => {
  return abExperiments?.bill_me_enabled?.variables?.result === 'on';
};

export const isBillMeOnlyMerchant = ({ abExperiments }) => {
  const user = getUser();
  const getBillMeMerchantKeys = Object.keys(user?.merchants ?? {});
  const isBillmeOnlyUser = getBillMeMerchantKeys.length === 1 && user?.merchants?.[getBillMeMerchantKeys[0]]?.product === 'billing';
  return isBillMeMerchant({ abExperiments }) && isBillmeOnlyUser;
};
