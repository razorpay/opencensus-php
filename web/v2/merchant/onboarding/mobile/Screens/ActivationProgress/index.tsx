import React from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade/src/atoms/View';
import Space from '@razorpay/blade/src/atoms/Space';
import { Motion, spring, presets } from 'react-motion';
import { FullPageLoader } from 'v2/components/Loader';
import useActivation from '../../hooks/useActivation';
// import { getMerchantFlow } from '../../services/utils';
// import WhitelistedSteps from './WhitelistedSteps';
import GreylistedSteps from './GreylistedSteps';
import ActivationProgressHeader from './ActivationProgressHeader';

const Screen = styled(View)`
  background-color: #f9fbfe;
  min-height: 100vh;
`;
const StyledSeparator = styled(View)`
  height: 1px;
  width: 100%;
  background-color: rgba(22, 47, 86, 0.1);
`;

const ScreenContainer = styled.div.attrs((props) => ({
  style: {
    opacity: props.$opacity,
    transform: `translateY(${props.$y}%)`,
  },
}))`
  min-height: 100%;
`;

const ActivationProgress: React.FC = () => {
  const { status, data } = useActivation();

  if (status === 'loading') {
    return <FullPageLoader />;
  }

  if (status === 'error') {
    return <View> Something went wrong </View>;
  }
  // const merchantFlow = getMerchantFlow(data.business_type, data.activation_flow);

  const Steps = GreylistedSteps; //WhitelistedSteps;
  // if (merchantFlow === 'greylist') {
  //   Steps = GreylistedSteps;
  // }

  return (
    <Motion
      defaultStyle={{ y: 100, opacity: 0 }}
      style={{
        y: spring(0, { ...presets.gentle, precision: 0.1 }),
        opacity: spring(1, { ...presets.gentle, precision: 0.1 }),
      }}
    >
      {(styles) => (
        <ScreenContainer $y={styles.y} $opacity={styles.opacity}>
          <ActivationProgressHeader progress={data.activation_progress} />
          <StyledSeparator />
          <Space padding={[2]}>
            <Screen>
              <Steps />
            </Screen>
          </Space>
        </ScreenContainer>
      )}
    </Motion>
  );
};

export default ActivationProgress;
