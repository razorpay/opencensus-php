import React, { lazy } from 'react';
import { Suspense } from 'merchant_common/views/Reports/components';

const PartnerReportsV2 = lazy(
  () => import(/* webpackChunkName: "PartnerReports" */ 'merchant_common/views/Reports'),
);

const PartnerReports = () => {
  return (
    <Suspense>
      <PartnerReportsV2 dashboard="partner" />
    </Suspense>
  );
};

export default PartnerReports;
