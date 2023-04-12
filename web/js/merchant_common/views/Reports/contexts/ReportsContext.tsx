import React, { createContext, useContext } from 'react';

export const ReportContext = createContext({
  /**
   * Dashboard type where reports ui is to be used. (partner | merchant | linkedAccount).
   */
  dashboardType: '',
});

/**
 *
 * @returns {string} - Dashboard type on which core reports ui is being used.
 *
 */
export const useDashboardType = () => useContext(ReportContext).dashboardType;

// will nvr cause a rerender
export const ReportContextProvider = ({ children, dashboardType }) => {
  return <ReportContext.Provider value={{ dashboardType }}>{children}</ReportContext.Provider>;
};
