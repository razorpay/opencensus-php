import React from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade/src/atoms/View';
import Size from '@razorpay/blade/src/atoms/Size';
import OnboardingStepCard from '../../OnboardingStepCard';

const Screen = styled(View)`
  background-color: #f9fbfe;
`;

const WhitelistedSteps: React.FC = () => {
  const onClick = (step) => {
    console.log('clicked', step);
  };
  const onCTAClick = () => {
    console.log('onCTAClick');
  };

  return (
    <Screen>
      <OnboardingStepCard
        title="1. Enable Payments"
        subtitle="Submit these details and start accepting payments"
        steps={[
          { name: 'Contact Details', id: 'contact_details', onClick, isComplete: true },
          { name: 'Business Overview', id: 'business_overview', onClick },
          { name: 'Business Details', id: 'business_details', onClick },
        ]}
        onCTAClick={onCTAClick}
        showCTA
      />
      <Size height="20px">
        <View />
      </Size>
      <OnboardingStepCard
        title="2. Enable Settlements"
        subtitle="Submit your bank details and documents to receive money in your bank account"
        info="Your documents are under review. We will get back to you in 3 working days"
        steps={[
          {
            name: 'Complete step 1 to unlock this',
            id: 'enable_settlements',
            onClick,
            isLocked: true,
          },
        ]}
      />
    </Screen>
  );
};

export default WhitelistedSteps;
