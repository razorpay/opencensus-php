import Onboarding from 'merchant/views/Affordability/AffordabilityWidget/Onboarding';

export const onboarding = {
  affordabilityWidget: {
    affordability: {
      enabled: false,
      trialDate: new Date(),
      pricing: {
        rate: 2000,
      },
    },
  },
};

export const App = (props) => {
  return <Onboarding {...props} />;
};
