import { useEffect, useReducer } from 'react';
import { ScheduleType } from 'merchant_common/views/Reports/types/schedule';
import {
  createScheduleReducer,
  initialCreateScheduleModalState,
  setCustomDurationRange,
  setCustomEnabled,
  setRecipients,
  setRunForeverEnabled,
  setScheduleName,
  setSelectedAccount,
  setSelectedConfig,
  setSelectedDataDuration,
  setSelectedFormat,
  setSelectedRepetition,
  setShowErrorInSection,
  setSubmitButtonLoading,
  setWhenTime,
  prefillStateWithData,
} from 'merchant_common/views/Reports/components/ReportModal/components/ScheduleReport/reducer/scheduleSlice';
import { BaseConfigType } from 'merchant_common/views/Reports/types/config';

export const useScheduleReportReducer = (
  preExistingScheduleData?: ScheduleType,
  allReportConfigs?: BaseConfigType[],
) => {
  const [state, dispatch] = useReducer(createScheduleReducer, initialCreateScheduleModalState);

  const actions = {
    setCustomEnabled: (payload) => dispatch(setCustomEnabled(payload)),
    setRunForeverEnabled: (payload) => dispatch(setRunForeverEnabled(payload)),
    setShowErrorInSection: (payload) => dispatch(setShowErrorInSection(payload)),
    setSelectedConfig: (payload) => dispatch(setSelectedConfig(payload)),
    setScheduleName: (payload) => dispatch(setScheduleName(payload)),
    setSelectedFormat: (payload) => dispatch(setSelectedFormat(payload)),
    setSelectedDataDuration: (payload) => dispatch(setSelectedDataDuration(payload)),
    setSelectedRepetition: (payload) => dispatch(setSelectedRepetition(payload)),
    setCustomDurationRange: (payload) => dispatch(setCustomDurationRange(payload)),
    setRecipients: (payload) => dispatch(setRecipients(payload)),
    setSubmitButtonLoading: (payload) => dispatch(setSubmitButtonLoading(payload)),
    setSelectedAccount: (payload) => dispatch(setSelectedAccount(payload)),
    setWhenTime: (payload) => dispatch(setWhenTime(payload)),
  };

  useEffect(() => {
    if (preExistingScheduleData) {
      const refConfig = allReportConfigs?.find(
        (config) => config.id === preExistingScheduleData.config_id,
      );
      dispatch(
        prefillStateWithData({
          preExistingScheduleData,
          refConfig,
        }),
      );
    }
  }, [preExistingScheduleData]);

  return { ...state, ...actions };
};
