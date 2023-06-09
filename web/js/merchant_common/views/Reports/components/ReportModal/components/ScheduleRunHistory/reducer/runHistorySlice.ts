import { createSlice } from '@reduxjs/toolkit';
import { downloadsFilterDropdown } from 'merchant_common/views/Reports/features/Downloads/constants/dropdownOptions';
import { ScheduleRunHistoryContextType } from 'merchant_common/views/Reports/components/ReportModal/components/ScheduleRunHistory/types';

export const initialRunHistoryState: ScheduleRunHistoryContextType = {
  filter: downloadsFilterDropdown[0],
  pageTrack: 1,
  isPollActive: true,
  isLoading: true,
  logs: [],
  totalLogsCount: 0,
};

const runHistorySlice = createSlice({
  initialState: initialRunHistoryState,
  name: 'runHistorySlice',
  reducers: {
    handleLogsFilter: (state, action) => {
      state.logs = [];
      state.isLoading = true;
      state.isPollActive = true;
      state.pageTrack = 1;
      state.totalLogsCount = 0;
      state.filter = action.payload;
    },
    handlePageTrack: (state, action) => {
      state.logs = [];
      state.isLoading = true;
      state.isPollActive = true;
      state.pageTrack = action.payload;
    },
    handleLogsFetchSuccess: (state, action) => {
      const { total_count, items } = action.payload;
      state.totalLogsCount = total_count;
      state.logs = items;
      state.isLoading = false;
    },
    setIsLoading: (state, action) => {
      state.isLoading = action.payload;
    },
    setIsPollActive: (state, action) => {
      state.isPollActive = action.payload;
    },
  },
});

export const {
  handleLogsFetchSuccess,
  handleLogsFilter,
  handlePageTrack,
  setIsLoading,
  setIsPollActive,
} = runHistorySlice.actions;

export const runHistoryReducer = runHistorySlice.reducer;
