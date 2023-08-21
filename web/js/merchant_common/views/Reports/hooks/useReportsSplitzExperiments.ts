import { useSplitzService } from 'common/splitz';

export const useReportsSplitzExperiments = () => {
  const {
    abExperiments: { Reports_Revamp_Recents, Reports_Schedules, LA_Reports_Revamp },
  } = useSplitzService();

  return {
    isSchedulesEnabled: Reports_Schedules?.variables?.result === 'on',
    isRevampedLAReports: LA_Reports_Revamp?.variables?.result === 'on',
    isOverviewRecentsFilterEnabled: Reports_Revamp_Recents?.variables?.result === 'on',
  };
};
