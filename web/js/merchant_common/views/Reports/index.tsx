import React from 'react';
import { ReportsPropType } from './types';
import { ReportsSection } from './Reports';
import { ReportContextProvider } from './contexts/ReportsContext';
import { ReportsErrorBoundary } from 'merchant_common/views/Reports/components';
import { useI18Service } from 'common/i18';

/**
 * `Reporting UI`
 *
 * @returns {JSX.Element} Reports UI root component
 */
const Reports = ({ dashboard }: ReportsPropType): JSX.Element => {
  const i18 = useI18Service();
  return (
    <ReportsErrorBoundary>
      <ReportContextProvider dashboardType={dashboard}>
        <ReportsSection dashboardType={dashboard} i18={i18} />
      </ReportContextProvider>
    </ReportsErrorBoundary>
  );
};

export default Reports;
