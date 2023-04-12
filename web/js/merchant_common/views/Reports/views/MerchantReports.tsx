import React, { lazy } from 'react';
import { withReportsSplitzExperiment } from 'merchant_common/views/Reports/utils';
import { Suspense } from 'merchant_common/views/Reports/components';

const RevampedMerchantReports = lazy(
  () => import(/* webpackChunkName: "RevampedMerchantReports" */ 'merchant_common/views/Reports'),
);

const OldMerchantReports = lazy(
  () => import(/* webpackChunkName: "OldMerchantReports" */ 'merchant/views/ReportsAsync/Home'),
);

const MerchantReports = withReportsSplitzExperiment(({ revampedMerchantReports }) => (
  <Suspense>
    {revampedMerchantReports ? (
      <RevampedMerchantReports dashboard="merchant" />
    ) : (
      <OldMerchantReports />
    )}
  </Suspense>
));

export default MerchantReports;
