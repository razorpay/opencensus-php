import React, { Suspense } from 'react';

import { useSplitzService } from 'common/splitz';
import lazyLoader from 'merchant/routes/LazyLoader';
import { useStore } from '@federated/apps/shell/commonStore';
import { isEligibleForReKyc } from '../../ReKycStatusAlerts/utils';

const ReKycStatusModal = lazyLoader(() =>
  import(/* webpackChunkName: 'reKycStatusModal' */ 'merchant/components/ReKycStatusAlerts').then(
    (module) => ({ default: module.ReKycStatusModal }),
  ),
);

export default function TopLevelModals() {
  const splitz = useSplitzService();
  const user = useStore((state) => state.session.user);

  const shouldShowReKycModal = isEligibleForReKyc(splitz, user);

  return (
    <>
      {shouldShowReKycModal ? (
        <Suspense fallback={null}>
          <ReKycStatusModal />
        </Suspense>
      ) : null}
    </>
  );
}
