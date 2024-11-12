export const isOmniChannelMerchant = (user) => {
  return (
    user.isOmniEnabledMerchant || (!!user?.pos_activation_status && user?.isOmniChannelMerchant)
  );
};

export const isBillMeMerchant = ({ abExperiments }) => {
  return abExperiments?.bill_me_enabled?.variables?.result === 'on';
};
