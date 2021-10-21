import React from 'react';
import View from '@razorpay/blade-old/src/atoms/View';
import OnboardingStepCard from '../../OnboardingStepCard';

const AccessBlockedSteps: React.FC = () => {
  return (
    <View>
      <OnboardingStepCard
        title="Complete your Partner KYC First"
        errorInfo="Kindly open the Partner section on your dashboard and submit the required information
        for Partner KYC before accessing the Merchant KYC"
        steps={[]}
      />
    </View>
  );
};

export default AccessBlockedSteps;
