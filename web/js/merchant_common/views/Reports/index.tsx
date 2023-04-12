import React from 'react';
import { ReportsPropType } from './types';
import { ReportsSection } from './Reports';
import { ReportContextProvider } from './contexts/ReportsContext';

/**
 * `Reporting UI`
 *
 * @returns {JSX.Element} Reports UI root component
 */
const Reports = ({ dashboard }: ReportsPropType): JSX.Element => {
  return (
    <ReportContextProvider dashboardType={dashboard}>
      <ReportsSection dashboardType={dashboard} />
    </ReportContextProvider>
  );
};

export default Reports;
