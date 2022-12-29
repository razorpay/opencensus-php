import Onboarding from 'merchant/views/PaymentLinks/OnBoarding';

export const onboarding = {
  products: {
    payment_links: {
      feature: 'payment_links',
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
