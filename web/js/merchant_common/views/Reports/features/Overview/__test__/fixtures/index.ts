import { REPORT_TEST_DASHBOARD } from 'merchant_common/views/Reports/constants';
import { reportsInitialStatesForAllDashboards } from 'merchant_common/views/Reports/redux/reducer';

export const getOverViewStateWith = (segment = {}, dashboardType = REPORT_TEST_DASHBOARD) => {
  return JSON.parse(
    JSON.stringify({
      reportsCore: {
        ...reportsInitialStatesForAllDashboards,
        [dashboardType]: {
          ...reportsInitialStatesForAllDashboards[dashboardType],
          overview: {
            reportConfigs: {
              ...reportsInitialStatesForAllDashboards[dashboardType].overview.reportConfigs,
              ...segment,
            },
          },
        },
      },
    }),
  );
};
