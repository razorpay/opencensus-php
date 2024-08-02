import React from 'react';
import { Badge } from '@razorpay/blade/components';

import {
  Wrapper,
  FrameContainer,
} from 'merchant/views/Settings/Configuration/CheckoutConfig/CheckoutDemo/styles';

import CheckoutV2 from './CheckoutV2';

const CheckoutDemo = () => {
  return (
    <Wrapper>
      <Badge color="information" size="medium">
        Live Preview
      </Badge>
      <FrameContainer>
        <CheckoutV2 />
      </FrameContainer>
    </Wrapper>
  );
};

export default CheckoutDemo;
