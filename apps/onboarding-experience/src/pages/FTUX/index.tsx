import React from 'react';
import { Wrapper } from 'apps/onboarding-experience/src/container';
import Home from './Home';
import MerchantProvider from '@FTUX/context/MerchantProvider';

const FTUX = () => {
  return (
    <Wrapper>
      <MerchantProvider>
        <Home />
      </MerchantProvider>
    </Wrapper>
  );
};

export default FTUX;
