import React from 'react';
import lazyLoader from 'merchant/routes/LazyLoader';

const FTUXLanding = lazyLoader(
  () =>
    import(
      /* webpackChunkName: "FTUXLanding" */ '@federated/apps/onboarding-experience/entry/FTUX'
    ),
);

const FTUXHomepage = (): JSX.Element => {
  return <FTUXLanding />;
};

export default FTUXHomepage;
