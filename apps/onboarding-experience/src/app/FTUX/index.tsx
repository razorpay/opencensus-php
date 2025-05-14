import React from 'react';
import { Wrapper } from 'apps/onboarding-experience/src/container';
import FTUXApp from '@FTUX/components/App';
import MerchantProvider from '@FTUX/context/MerchantProvider';

const FTUX = () => {
  return (
    <Wrapper>
      <MerchantProvider>
        <FTUXApp />
      </MerchantProvider>
    </Wrapper>
  );
};

export default FTUX;
