import { createSlice } from '@reduxjs/toolkit';

import {
  createVCipLink,
  fetchEddDetails,
} from 'merchant/reducers/unlockIntlPaymentMethods/actions';
import {
  INITIAL_STATE,
  MORE_PAYMENT_METHOD_STATUS,
} from 'merchant/reducers/unlockIntlPaymentMethods/initialState';
import { V_KYC_STATUS } from 'merchant/reducers/videoKYCBanner';

const unlockIntlPaymentMethodsSlice = createSlice({
  name: 'unlockIntlPaymentMethods',
  initialState: INITIAL_STATE,
  reducers: {
    setKycDocumentStatus: (state, action) => {
      state.kycDocumentStatus = action.payload;
      state.status =
        state.status === MORE_PAYMENT_METHOD_STATUS.INITIAL
          ? MORE_PAYMENT_METHOD_STATUS.IN_PROGRESS
          : state.status;
    },
    setEDDStatus: (state, action) => {
      state.eddStatus = action.payload.eddStatus;
      state.vKycStatus = action.payload.vKycStatus;
      state.vKycRejectedReason = action.payload.vKycRejectedReason ?? '';
    },
    setVkycStatus: (state, action) => {
      state.vKycStatus = action.payload;
    },
    setIsMethodEnablementFormOpen: (state, action) => {
      state.isMethodEnablementFormOpen = action.payload.isOpen;
      state.defaultTab = action.payload.defaultTab ?? 0;
    },
  },
  extraReducers: (builder) => {
    builder.addCase(fetchEddDetails.pending, (state) => {
      state.isStatusLoading = true;
    });
    builder.addCase(fetchEddDetails.rejected, (state) => {
      state.isStatusLoading = false;
    });
    builder.addCase(fetchEddDetails.fulfilled, (state, action) => {
      state.vKycRejectedReason = action.payload.vKycRejectedReason ?? '';
      state.isStatusLoading = false;
      ['eddStatus', 'vKycStatus', 'kycDocumentStatus', 'kycRejectedReason', 'status'].forEach(
        (key) => (state[key] = action.payload[key]),
      );
      if (state.status === MORE_PAYMENT_METHOD_STATUS.VERIFIED) {
        state.showMorePaymentMethodsSection = false;
      }
    });

    builder.addCase(createVCipLink.pending, (state) => {
      state.isCreatingLink = true;
    });
    builder.addCase(createVCipLink.rejected, (state) => {
      state.isCreatingLink = false;
    });
    builder.addCase(createVCipLink.fulfilled, (state) => {
      state.isCreatingLink = false;
      state.vKycStatus = V_KYC_STATUS.INITIATED;
    });
  },
});

export const unlockIntlPaymentMethodsReducer = unlockIntlPaymentMethodsSlice.reducer;
export const { setKycDocumentStatus, setVkycStatus, setEDDStatus, setIsMethodEnablementFormOpen } =
  unlockIntlPaymentMethodsSlice.actions;
