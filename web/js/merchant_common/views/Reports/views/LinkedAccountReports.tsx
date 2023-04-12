import React, { lazy } from 'react';
import { withReportsSplitzExperiment } from 'merchant_common/views/Reports/utils';
import { Suspense } from 'merchant_common/views/Reports/components';

const RevampedLAReports = lazy(
  () => import(/* webpackChunkName: "RevampedLAReports" */ 'merchant_common/views/Reports'),
);

const OldLAReports = lazy(
  () => import(/* webpackChunkName: "OldLAReports" */ 'merchantLA/containers/Reports'),
);

const LinkedAccountReports = withReportsSplitzExperiment(({ revampedLAReports }) => (
  <Suspense>
    {revampedLAReports ? <RevampedLAReports dashboard="linkedAccount" /> : <OldLAReports />}
  </Suspense>
));

export default LinkedAccountReports;
