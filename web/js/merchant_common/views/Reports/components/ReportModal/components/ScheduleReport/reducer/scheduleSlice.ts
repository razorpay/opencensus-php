import { createSlice } from '@reduxjs/toolkit';
import { InitialStateType } from './types';
import moment from 'moment';
import {
  availableFormat,
  getDataDurations,
  getRepetitions,
} from 'merchant_common/views/Reports/components/ReportModal/components/ScheduleReport/data';
import { reverseScheduleData } from 'merchant_common/views/Reports/components/ReportModal/components/ScheduleReport/utils';

export const initialCreateScheduleModalState: InitialStateType = {
  isCustomEnabled: false,
  isRunForeverEnabled: false,
  showErrorInSection: undefined,
  isResetComplete: false,
  // section 1
  selectedConfig: undefined,
  scheduleName: '',
  selectedFormat: undefined,
  selectedAccount: undefined,

  // section 2
  selectedDataDuration: undefined,
  selectedRepetition: undefined,
  customDataDuration: {
    startDate: moment().add(1, 'day').startOf('day'),
    endDate: moment().add(30, 'day').endOf('day'),
  },
  whenTime: undefined,

  // section 3
  recipients: [],

  // misc
  isSubmitButtonLoading: false,
};

const scheduleSlice = createSlice({
  initialState: initialCreateScheduleModalState,
  name: 'scheduleCreateReducer',
  reducers: {
    setCustomEnabled: (state, action) => {
      state.isCustomEnabled = action.payload;
      if (state?.selectedDataDuration?.value) {
        state.selectedDataDuration = getDataDurations(action.payload)[0];
        state.selectedRepetition = getRepetitions(action.payload.value, action.payload)[0];
      }
    },
    setRunForeverEnabled: (state, action) => {
      state.isRunForeverEnabled = action.payload;
    },
    setShowErrorInSection: (state, action) => {
      state.showErrorInSection = action.payload;
    },
    setSelectedConfig: (state, action) => {
      state.selectedConfig = action.payload;
    },
    setScheduleName: (state, action) => {
      state.scheduleName = action.payload;
    },
    setSelectedFormat: (state, action) => {
      state.selectedFormat = action.payload;
    },
    setSelectedDataDuration: (state, action) => {
      state.selectedDataDuration = action.payload;
      if (action.payload.value) {
        state.selectedRepetition = getRepetitions(action.payload.value, state.isCustomEnabled)[0];
      }
    },
    setSelectedRepetition: (state, action) => {
      state.selectedRepetition = action.payload;
    },
    setCustomDurationRange: (state, action) => {
      state.customDataDuration = action.payload;
    },
    setRecipients: (state, action) => {
      state.recipients = action.payload;
    },
    setSubmitButtonLoading: (state, action) => {
      state.isSubmitButtonLoading = action.payload;
    },
    setSelectedAccount: (state, action) => {
      state.selectedAccount = action.payload;
    },
    setWhenTime: (state, action) => {
      state.whenTime = action.payload;
    },
    prefillStateWithData: (state, action) => {
      const { preExistingScheduleData, refConfig } = action.payload;

      // config_id
      if (refConfig) {
        state.selectedConfig = refConfig;
      }

      // name
      if (preExistingScheduleData?.name) state.scheduleName = preExistingScheduleData.name;

      // scheduleStartTime & scheduleEndTime
      if (
        preExistingScheduleData?.schedule_start_time &&
        preExistingScheduleData?.schedule_end_time
      ) {
        state.customDataDuration = {
          startDate: moment.unix(preExistingScheduleData?.schedule_start_time),
          endDate: moment.unix(preExistingScheduleData?.schedule_end_time),
        };
      }

      // template_overrides.file_meta.extension
      if (preExistingScheduleData?.template_overrides?.file_meta?.extension) {
        state.selectedFormat = availableFormat.find(
          (format) =>
            format.value === preExistingScheduleData?.template_overrides?.file_meta?.extension,
        );
      }

      // selectedRepetition, selectedDataDuration, whenTime
      const otherStates = reverseScheduleData(preExistingScheduleData);

      if (otherStates) {
        const { selectedRepetition, whenTime, isCustomEnabled, selectedDataDuration } = otherStates;
        state.isCustomEnabled = Boolean(isCustomEnabled);
        state.selectedDataDuration = selectedDataDuration;
        state.selectedRepetition = selectedRepetition;
        state.whenTime = whenTime;
      }

      // emails
      if (preExistingScheduleData?.emails?.length) {
        state.recipients = preExistingScheduleData.emails;
      }

      state.isResetComplete = true;
    },
  },
});

export const {
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
} = scheduleSlice.actions;

export const createScheduleReducer = scheduleSlice.reducer;
