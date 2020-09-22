import React from 'react';
import styled from 'styled-components';
import Space from '@razorpay/blade/src/atoms/Space';
import View from '@razorpay/blade/src/atoms/View';
import Flex from '@razorpay/blade/src/atoms/Flex';
import Text from '@razorpay/blade/src/atoms/Text';
import Link from '@commander/shield/src/shared/Link';
import HeaderBackground from './images/header_background.svg';

const StyledActivationProgressHeader = styled(View)`
  box-shadow: 0px 4px 5px rgba(11, 112, 231, 0.05);
  background: url("${HeaderBackground}") right bottom -10px no-repeat;
`;

const ActivationProgressHeader = () => (
  <Space padding={[2, 2.5, 2, 2.5]}>
    <Flex justifyContent="space-between">
      <StyledActivationProgressHeader>
        <View>
          <Text size="large" weight="bold">
            Account Activation
          </Text>
          <Text size="xsmall" weight="bold" color="positive.960">
            20% complete
          </Text>
        </View>
        <Space padding={[0.5, 0]}>
          <Link size="xsmall" weight="bold" color="primary.800">
            Save and Exit
          </Link>
        </Space>
      </StyledActivationProgressHeader>
    </Flex>
  </Space>
);

export default ActivationProgressHeader;
