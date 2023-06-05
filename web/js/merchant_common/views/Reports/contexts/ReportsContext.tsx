import React, { createContext, useContext } from 'react';
import { DashboardType } from 'merchant_common/views/Reports/types';

export const ReportContext = createContext({
  /**
   * Dashboard type where reports ui is to be used. (partner | merchant | linkedAccount).
   */
  dashboardType: '',
});

/**
 *
 * @returns {DashboardType} - Dashboard type on which core reports ui is being used.
 *
 */
export const useDashboardType = () => useContext(ReportContext).dashboardType as DashboardType;

// will nvr cause a rerender
export const ReportContextProvider = ({ children, dashboardType }) => {
  return <ReportContext.Provider value={{ dashboardType }}>{children}</ReportContext.Provider>;
};
