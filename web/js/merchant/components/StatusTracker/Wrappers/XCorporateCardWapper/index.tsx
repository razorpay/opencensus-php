import React, { Suspense } from 'react';
import GrowthAssetEB from 'common/ui/GrowthAssetEB';
import ShowWhen from 'merchant/components/ShowWhen';
import lazy from 'merchant/routes/LazyLoader';

const ConnectedXCCStatusTracker = lazy(() =>
  import(/* webpackChunkName: "ConnectedXCCStatusTracker" */ './XCorporateCardWapper'),
);

const XCCStatusTrackerWrapper = (): JSX.Element => {
  return (
    <GrowthAssetEB>
      <ShowWhen additionalCondition={(user) => user.isXCCStatusTrackerEnabled}>
        <Suspense fallback={null}>
          <ConnectedXCCStatusTracker />
        </Suspense>
      </ShowWhen>
    </GrowthAssetEB>
  );
};

export default XCCStatusTrackerWrapper;
