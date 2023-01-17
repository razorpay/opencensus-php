import PlanDetails from 'merchant/views/Affordability/AffordabilityWidget/PlanDetails';

export const planDetails = {
  affordability: {
    enabled: true,
    trialDate: new Date(),
    pricing: {
      rate: 2000,
    },
  },
};

export const App = (props) => {
  return <PlanDetails {...planDetails} {...props} />;
};
