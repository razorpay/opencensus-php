import React, { lazy } from 'react';
import { withReportsSplitzExperiment } from 'merchant_common/views/Reports/utils';
import { Suspense } from 'merchant_common/views/Reports/components';

const RevampedPartnerReports = lazy(
  () => import(/* webpackChunkName: "RevampedPartnerReports" */ 'merchant_common/views/Reports'),
);

const OldPartnerReports = lazy(
  () =>
    import(/* webpackChunkName: "OldPartnerReports" */ 'merchant/views/PartnerDashboard/Reports'),
);

const PartnerReports = withReportsSplitzExperiment(({ revampedPartnerReports }) => (
  <Suspense>
    {revampedPartnerReports ? (
      <RevampedPartnerReports dashboard="partner" />
    ) : (
      <OldPartnerReports />
    )}
  </Suspense>
));

export default PartnerReports;
