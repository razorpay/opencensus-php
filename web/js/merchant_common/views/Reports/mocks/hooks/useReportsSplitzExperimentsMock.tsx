jest.mock('merchant_common/views/Reports/hooks/useReportsSplitzExperiments', () => ({
  useReportsSplitzExperiments: () => ({
    isSchedulesEnabled: true,
    isRevampedLAReports: true,
    isOverviewRecentsFilterEnabled: true,
    isReportsSelfServeEnabled: true,
  }),
}));
