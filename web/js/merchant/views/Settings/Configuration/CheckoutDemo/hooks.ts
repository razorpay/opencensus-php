import { useCheckoutConfig as useCheckoutStyling } from 'merchant/views/Settings/Configuration/CheckoutStyling/context';
import { useCheckoutFeatures } from 'merchant/views/Settings/Configuration/CheckoutFeatures/context';

export const useCheckoutDemoValues = () => {
  const { values: checkoutStylings } = useCheckoutStyling();
  const { values: checkoutFeatures } = useCheckoutFeatures();

  return {
    values: {
      ...checkoutStylings,
      ...checkoutFeatures,
    },
  };
};
