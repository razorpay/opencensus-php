import React, { lazy } from 'react';
import { Suspense } from 'merchant_common/views/Reports/components';
import { useReportsSplitzExperiments } from 'merchant_common/views/Reports/hooks';

const RevampedLAReports = lazy(
  () => import(/* webpackChunkName: "RevampedLAReports" */ 'merchant_common/views/Reports'),
);

const OldLAReports = lazy(
  () => import(/* webpackChunkName: "OldLAReports" */ 'merchantLA/containers/Reports'),
);

const LinkedAccountReports = () => {
  const { isRevampedLAReports } = useReportsSplitzExperiments();
  return (
    <Suspense>
      {isRevampedLAReports ? <RevampedLAReports dashboard="linkedAccount" /> : <OldLAReports />}
    </Suspense>
  );
};

export default LinkedAccountReports;
