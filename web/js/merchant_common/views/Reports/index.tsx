import React from 'react';
import { ReportsPropType } from './types';
import { ReportsSection } from './Reports';
import { ReportContextProvider } from './contexts/ReportsContext';
import { ReportsErrorBoundary } from 'merchant_common/views/Reports/components';

/**
 * `Reporting UI`
 *
 * @returns {JSX.Element} Reports UI root component
 */
const Reports = ({ dashboard }: ReportsPropType): JSX.Element => {
  return (
    <ReportsErrorBoundary>
      <ReportContextProvider dashboardType={dashboard}>
        <ReportsSection dashboardType={dashboard} />
      </ReportContextProvider>
    </ReportsErrorBoundary>
  );
};

export default Reports;
