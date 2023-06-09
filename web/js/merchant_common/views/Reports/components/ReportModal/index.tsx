import React, { lazy } from 'react';
import { Suspense } from 'merchant_common/views/Reports/components/Suspense';
import { BaseReportModalPropsType } from './types';

const ReportsModal = lazy(() => import(/* webpackChunkName: "ReportsModal" */ './ReportModal'));

export const ReportModal = (props: BaseReportModalPropsType) => (
  <Suspense>
    <ReportsModal {...props} />
  </Suspense>
);
