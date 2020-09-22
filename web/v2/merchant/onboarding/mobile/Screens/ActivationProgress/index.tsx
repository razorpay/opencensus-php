import React from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade/src/atoms/View';
import Space from '@razorpay/blade/src/atoms/Space';
import WhitelistedSteps from './WhitelistedSteps';
import GreylistedSteps from './GreylistedSteps';
import ActivationProgressHeader from './ActivationProgressHeader';

const Screen = styled(View)`
  background-color: #f9fbfe;
`;

const ActivationProgress: React.FC = ({ activationFlow }) => {
  let Steps = WhitelistedSteps;
  if (activationFlow === 'greylist') {
    Steps = GreylistedSteps;
  }
  return (
    <View>
      <ActivationProgressHeader />
      <Space padding={[2]}>
        <Screen>
          <Steps />
        </Screen>
      </Space>
    </View>
  );
};

export default ActivationProgress;
