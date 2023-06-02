import React from 'react';
import Onboarding from 'merchant/views/PaymentPages/OnBoarding';

export const onboarding = {
  products: {
    payment_pages: {
      feature: 'payment_pages',
      isEnabled: true,
      isQuickGuideOpen: true,
      isTour: false,
      lastElementId: undefined,
      showOnboarding: true,
    },
  },
};

export const App = (props) => {
  return <Onboarding {...props} />;
};
