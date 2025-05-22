import React from 'react';
import { Wrapper } from 'apps/onboarding-experience/src/container';
import FTUXApp from '@FTUX/components/App';
import MerchantProvider from '@FTUX/context/MerchantProvider';
import { TwoFaAuthProps } from '@FTUX/types/common';

const FTUX = ({ triggerTwoFaAuth }: { triggerTwoFaAuth: (params: TwoFaAuthProps) => void }) => {
  return (
    <Wrapper>
      <MerchantProvider triggerTwoFaAuth={triggerTwoFaAuth}>
        <FTUXApp />
      </MerchantProvider>
    </Wrapper>
  );
};

export default FTUX;
