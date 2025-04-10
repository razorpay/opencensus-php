import React from 'react';
import { useStore } from '@federated/apps/shell/commonStore';
import { Wrapper } from '@apps/onboarding-experience/src/container';

const Ftux = (): JSX.Element => {
  const activeUser = useStore((state) => state.session.user);

  return <>FTUX Home, user: {activeUser.merchant?.id}</>;
};

const FTUXWrapper = () => {
  return (
    <Wrapper>
      <Ftux />
    </Wrapper>
  );
};

export default FTUXWrapper;
