import React, { lazy } from 'react';
import { Suspense } from 'merchant_common/views/Reports/components';

const MerchantReportsV2 = lazy(
  () => import(/* webpackChunkName: "RevampedMerchantReports" */ 'merchant_common/views/Reports'),
);

const MerchantReports = () => {
  return (
    <Suspense>
      <MerchantReportsV2 dashboard="merchant" />
    </Suspense>
  );
};

export default MerchantReports;
