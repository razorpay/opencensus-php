export const isOmniChannelMerchant = (user) => {
  return (
    user.isOmniEnabledMerchant || (!!user?.pos_activation_status && user?.isOmniChannelMerchant)
  );
};
