import React, { Suspense } from 'react';
import { Box, Modal, ModalBody, ProgressBar, Skeleton } from '@razorpay/blade/components';

import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import lazy from 'merchant/routes/LazyLoader';

const OnDemandV2 = lazy(
  () =>
    import(
      /* webpackChunkName: "OnDemandV2", webpackPrefetch: true */
      'merchant/views/Settlements/Settlements/components/Modals/OnDemandV2/OnDemandV2'
    ),
);

const Loader = () => {
  return (
    <Box borderRadius="large" overflow="hidden">
      <ProgressBar isIndeterminate />
      <Box padding="spacing.6">
        <Skeleton borderRadius="medium" height="22px" width="60%" />
        <Skeleton borderRadius="medium" marginTop="spacing.4" height="16px" width="80%" />
        <Skeleton borderRadius="medium" marginTop="60px" height="48px" />
        <Skeleton borderRadius="medium" marginTop="82px" height="36px" />
      </Box>
    </Box>
  );
};

const OnDemandModalEntry = () => {
  return (
    <Modal isOpen onDismiss={() => {}}>
      <ModalBody padding="spacing.0">
        <ErrorBoundary resetOnProps rank={Ranks.P0} team={Teams.CAPITAL}>
          <Suspense fallback={<Loader />}>
            <OnDemandV2 />
          </Suspense>
        </ErrorBoundary>
      </ModalBody>
    </Modal>
  );
};

export { OnDemandModalEntry };
