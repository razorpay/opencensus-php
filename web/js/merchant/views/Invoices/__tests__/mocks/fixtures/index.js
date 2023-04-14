import Onboarding from 'merchant/views/Invoices/OnBoarding';

export const onboarding = {
  products: {
    invoice: {
      feature: 'invoice',
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
