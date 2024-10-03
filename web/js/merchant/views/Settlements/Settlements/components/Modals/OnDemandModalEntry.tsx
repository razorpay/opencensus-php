import React, { Suspense, useEffect, useState } from 'react';
import { Box, Modal, ModalBody, ProgressBar, Skeleton } from '@razorpay/blade/components';

import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import { useSplitzService } from 'common/splitz';
import lazy from 'merchant/routes/LazyLoader';
import { openModal } from 'merchant_common/reducers/modals';

const OldOdsModal = lazy(
  () =>
    import(
      /* webpackChunkName: "OndemandModal", webpackPrefetch: true */
      'merchant/views/Settlements/Settlements/components/Modals/OndemandModal'
    ),
);

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

/**
 * Uses splitz to determine which version of ODS modal to load
 * TODO: After V2 rampup, revisit and optimise/cleanup
 * */
const OnDemandModalEntry = (props: Record<string, any>) => {
  const {
    abExperiments: { capital_is_settle_now_v2 },
  } = useSplitzService();

  const _shouldUseV2 = capital_is_settle_now_v2?.variables?.result === 'on';
  /**
   * useState is used to avoid experiment evaluating to different value after mount(we want to avoid such cases to prevent bugs).
   **/
  const [shouldUseV2] = useState(() => {
    return _shouldUseV2;
  });

  useEffect(() => {
    if (!shouldUseV2) {
      openModal({
        component: (
          <Suspense
            fallback={
              <Box borderRadius="large" overflow="hidden" minWidth="328px" maxWidth="400px">
                <Loader />
              </Box>
            }
          >
            <OldOdsModal {...props} />
          </Suspense>
        ),
        /** isNew required to override previous value, as we merge modal states */
        isNew: false,
        size: 'small',
        disableClose: true,
      });
    }
  }, []);

  return shouldUseV2 ? (
    // zIndex={1111} - used to put modal above sidenav - should be in sync with MODAL_ZINDEX - OnDemandV2/helpers.ts
    <Modal zIndex={1111} isOpen onDismiss={() => {}}>
      <ModalBody padding="spacing.0">
        <ErrorBoundary resetOnProps rank={Ranks.P0} team={Teams.CAPITAL}>
          <Suspense fallback={<Loader />}>
            <OnDemandV2 />
          </Suspense>
        </ErrorBoundary>
      </ModalBody>
    </Modal>
  ) : null;
};

export { OnDemandModalEntry };
