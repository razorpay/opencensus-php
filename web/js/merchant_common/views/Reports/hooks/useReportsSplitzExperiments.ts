import { useSplitzService } from 'common/splitz';
import { User } from 'common/typings';
import { isJKOfflineMerchant } from '@libs/shared-utils';
import { OrgData } from 'newAuth/signin/types';
import { isBillMeOnlyMerchant } from 'merchant/utils/omniUtils';

export const useReportsSplitzExperiments = (org?: OrgData, user?: User) => {
  const splitz = useSplitzService();
  const {
    abExperiments: {
      Reports_Revamp_Recents,
      Reports_Schedules,
      LA_Reports_Revamp,
      Data_Sync_Advertisement_Banner_Experiment,
      create_custom_report,
    },
  } = splitz;

  

  // For JK org the schedules are disabled hence handling from hook
  const isJKORg = Boolean(user && org) && isJKOfflineMerchant(org, user);
  const isBillMeMerchantOnly = isBillMeOnlyMerchant(splitz);

  const disableReportsSchedules = isBillMeMerchantOnly || isJKORg;

  return {
    isSchedulesEnabled: disableReportsSchedules ? false : Reports_Schedules?.variables?.result === 'on',
    isRevampedLAReports: LA_Reports_Revamp?.variables?.result === 'on',
    isOverviewRecentsFilterEnabled: Reports_Revamp_Recents?.variables?.result === 'on',
    isDataSyncAdvertisementBannerEnabled:
      Data_Sync_Advertisement_Banner_Experiment?.variables?.result === 'on',
    isReportsSelfServeEnabled: create_custom_report?.variables?.result === 'on',
  };
};
