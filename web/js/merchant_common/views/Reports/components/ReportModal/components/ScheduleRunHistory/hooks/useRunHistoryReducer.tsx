import {
  handleLogsFetchSuccess,
  handleLogsFilter,
  handlePageTrack,
  initialRunHistoryState,
  runHistoryReducer,
  setIsLoading,
  setIsPollActive,
} from 'merchant_common/views/Reports/components/ReportModal/components/ScheduleRunHistory/reducer/runHistorySlice';
import { trackSchedulesRunHistoryModal } from 'merchant_common/views/Reports/configs/analytics.config';
import { DashboardType } from 'merchant_common/views/Reports/types';
import { useEffect, useReducer } from 'react';

export const useRunHistoryReducer = (dashboardType: DashboardType) => {
  const [state, dispatch] = useReducer(runHistoryReducer, initialRunHistoryState);
  const actions = {
    handleLogsFetchSuccess: (payload) => dispatch(handleLogsFetchSuccess(payload)),
    handleLogsFilter: (payload) => {
      dispatch(handleLogsFilter(payload));
      trackSchedulesRunHistoryModal({
        actionName: 'Filter Interaction',
        properties: {
          interaction_type: 'select',
          selectedFilter: payload?.label,
        },
        dashboardType,
      });
    },
    handlePageTrack: (payload) => dispatch(handlePageTrack(payload)),
    setIsLoading: (payload) => dispatch(setIsLoading(payload)),
    setIsPollActive: (payload) => dispatch(setIsPollActive(payload)),
  };

  useEffect(() => {
    trackSchedulesRunHistoryModal({
      actionName: 'Loaded',
      dashboardType,
    });
  }, []);

  return { ...state, ...actions };
};
