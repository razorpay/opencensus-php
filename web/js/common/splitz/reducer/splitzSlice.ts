import { PayloadAction, createSlice } from '@reduxjs/toolkit';
import { ExperimentInfoType } from 'common/splitz/types';

export const splitzServiceInitialState: {
  isInitialized: boolean;
  abExperiments: ExperimentInfoType;
} = {
  isInitialized: false,
  abExperiments: {},
};

const splitzServiceSlice = createSlice({
  initialState: splitzServiceInitialState,
  name: 'splitzService',
  reducers: {
    setInitialized: (state, action: PayloadAction<boolean>) => {
      state.isInitialized = action.payload;
    },
    setABExperiments: (state, action: PayloadAction<ExperimentInfoType>) => {
      state.abExperiments = {
        ...state.abExperiments,
        ...action.payload,
      };
    },
  },
});

export const splitzServiceActions = splitzServiceSlice.actions;

export const splitzServiceReducer = splitzServiceSlice.reducer;
