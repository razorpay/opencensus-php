import { useCheckoutConfig } from 'merchant/views/Settings/Configuration/CheckoutStyling/context';

export const useCheckoutDemoValues = () => {
  const { values: checkoutConfigs } = useCheckoutConfig();
  return {
    values: {
      ...checkoutConfigs,
    },
  };
};
