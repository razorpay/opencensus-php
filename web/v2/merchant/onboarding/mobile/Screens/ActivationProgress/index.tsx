import React from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade/src/atoms/View';
import Space from '@razorpay/blade/src/atoms/Space';
import { FullPageLoader } from 'v2/components/Loader';
import useActivation from '../../hooks/useActivation';
import { getMerchantFlow } from '../../services/utils';
import WhitelistedSteps from './WhitelistedSteps';
import GreylistedSteps from './GreylistedSteps';
import ActivationProgressHeader from './ActivationProgressHeader';

const Screen = styled(View)`
  background-color: #f9fbfe;
`;

const ActivationProgress: React.FC = () => {
  const { status, data } = useActivation();

  if (status === 'loading') {
    return <FullPageLoader />;
  }

  if (status === 'error') {
    return <View> Something went wrong </View>;
  }
  const merchantFlow = getMerchantFlow(data.business_type, data.activation_flow);

  let Steps = WhitelistedSteps;
  if (merchantFlow === 'greylist') {
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
