import { useEffect, useReducer } from 'react';
import { ScheduleType } from 'merchant_common/views/Reports/types/schedule';
import {
  createScheduleReducer,
  initialCreateScheduleModalState,
  setCustomDurationRange,
  setCustomEnabled,
  setIsRecipientsEnabled,
  setRecipients,
  setRunForeverEnabled,
  setSaveReportAs,
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
import { getRepetitions } from 'merchant_common/views/Reports/components/ReportModal/components/ScheduleReport/data';

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
    setSaveReportAs: (payload) => dispatch(setSaveReportAs(payload)),
    setScheduleName: (payload) => dispatch(setScheduleName(payload)),
    setSelectedFormat: (payload) => dispatch(setSelectedFormat(payload)),
    setSelectedDataDuration: (payload) => dispatch(setSelectedDataDuration(payload)),
    setSelectedRepetition: (payload) => dispatch(setSelectedRepetition(payload)),
    setCustomDurationRange: (payload) => dispatch(setCustomDurationRange(payload)),
    setIsRecipientsEnabled: (payload) => dispatch(setIsRecipientsEnabled(payload)),
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

  // change available repetitions based on data duration
  useEffect(() => {
    if (state.selectedDataDuration?.value)
      actions.setSelectedRepetition(
        getRepetitions(state.selectedDataDuration.value, state.isCustomEnabled)[0],
      );
  }, [state.selectedDataDuration, state.isCustomEnabled]);

  // reset recipients
  useEffect(() => {
    if (!state.isRecipientsEnabled) {
      actions.setRecipients([]);
      if (state.showErrorInSection === 2) {
        actions.setShowErrorInSection(undefined);
      }
    }
  }, [state.isRecipientsEnabled]);

  return { ...state, ...actions };
};
