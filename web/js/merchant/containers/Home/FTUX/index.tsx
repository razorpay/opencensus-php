import React, { Suspense } from 'react';
import { Box } from '@razorpay/blade/components';
import lazyLoader from 'merchant/routes/LazyLoader';
import LayoutLoader from './Loader';

const FTUXLanding = lazyLoader(
  () =>
    import(
      /* webpackChunkName: "FTUXLanding" */ '@federated/apps/onboarding-experience/entry/FTUX'
    ),
);

const FTUXHomepage = (): JSX.Element => {
  return (
    <Box display="flex" justifyContent="center">
      <Box maxWidth="920px" width="100%">
        <Suspense fallback={<LayoutLoader />}>
          <FTUXLanding />
        </Suspense>
      </Box>
    </Box>
  );
};

export default FTUXHomepage;
