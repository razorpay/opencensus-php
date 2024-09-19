import { useCheckoutConfig } from 'merchant/views/Settings/Configuration/CheckoutConfig/context';

export const useCheckoutDemoValues = () => {
  const { values: checkoutConfigs } = useCheckoutConfig();
  return {
    values: {
      ...checkoutConfigs,
    },
  };
};
