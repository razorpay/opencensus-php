import React, { useEffect } from 'react';
import { initRazorAnalytics } from '@libs/shared-utils';
import { DASHBOARD_TEAMS } from '@libs/shared-types';
import { useStore } from '@federated/apps/shell/commonStore';
import { Wrapper } from 'apps/onboarding-experience/src/container';
import FTUXApp from '@FTUX/components/App';
import MerchantProvider from '@FTUX/context/MerchantProvider';
import { TwoFaAuthProps } from '@FTUX/types/common';

const FTUX = ({ triggerTwoFaAuth }: { triggerTwoFaAuth: (params: TwoFaAuthProps) => void }) => {
  const user = useStore((state) => state.session.user);

  useEffect(() => {
    initRazorAnalytics({
      product: DASHBOARD_TEAMS.ONBOARDING_EXPERIENCE,
      user,
    });

    return () => {
      window.razorAnalytics?.disableTracking?.();
    };
  }, []);

  return (
    <Wrapper>
      <MerchantProvider triggerTwoFaAuth={triggerTwoFaAuth}>
        <FTUXApp />
      </MerchantProvider>
    </Wrapper>
  );
};

export default FTUX;
