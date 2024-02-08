import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';

// eslint-disable-next-line import/no-cycle
import { merchantFetch } from 'merchant/utils/ajax';

export const EDD_STATUS = {
  INITIATED: 'initiated',
  VERIFIED: 'verified',
  REJECTED: 'rejected',
  NOT_VERIFIED: 'not_verified',
  DEFAULT: null,
} as const;

export const V_KYC_STATUS = {
  INITIATED: 'initiated',
  UNDER_REVIEW: 'under_review',
  APPROVED: 'approved',
  REJECTED: 'rejected',
  FAILED: 'failed',
  DEFAULT: null,
} as const;

export const V_KYC_BANNER_STATUS = {
  INITIAL: 'initial',
  IN_PROGRESS: 'in_progress',
  PENDING: 'pending',
  RETRY: 'retry',
  COMPLETED: 'completed',
  DEFAULT: null,
} as const;

export type VideoKYCInitialState = {
  isLoaded: boolean;
  isLoading: boolean;
  vKycBannerStatus: (typeof V_KYC_BANNER_STATUS)[keyof typeof V_KYC_BANNER_STATUS];
  vKycStatus: (typeof V_KYC_STATUS)[keyof typeof V_KYC_STATUS] | null;
  eddStatus: (typeof EDD_STATUS)[keyof typeof EDD_STATUS] | null;
  international: boolean | null;
  moneySaverAccountsActivated: boolean | null;
  instantBankAccountsActivated: boolean | null;
  vKycRejectedReason: string;
  intlProductStatus: string | null;
  isCreateVideoKYCModalOpen: boolean;
  isCreatingLink: boolean;
};

export const initialState: VideoKYCInitialState = {
  isLoaded: false,
  isLoading: false,
  vKycBannerStatus: V_KYC_BANNER_STATUS.DEFAULT,
  vKycStatus: V_KYC_STATUS.DEFAULT,
  eddStatus: EDD_STATUS.DEFAULT,
  international: null,
  moneySaverAccountsActivated: null,
  instantBankAccountsActivated: null,
  vKycRejectedReason: '',
  intlProductStatus: null,
  isCreateVideoKYCModalOpen: false,
  isCreatingLink: false,
};

export const fetchEddDetails = createAsyncThunk('videoKycBanner/fetchEddDetails', async () => {
  const { success, data } = await merchantFetch({
    url: 'edd_details',
    mode: 'live',
  });

  if (success && data) {
    const { status, details } = data;
    const vKycDetails = Array.isArray(details)
      ? details.find((item) => item.type === 'vkyc')
      : details;

    return {
      eddStatus: !status ? EDD_STATUS.DEFAULT : status,
      vKycStatus: vKycDetails?.status ?? V_KYC_STATUS.DEFAULT,
      vKycRejectedReason: vKycDetails?.reason ?? '',
    };
  }

  return {
    eddStatus: null,
    vKycStatus: null,
    vKycRejectedReason: '',
  };
});

export const createVKYCLink = createAsyncThunk(
  'videoKycBanner/createVKYCLink',
  async (name: string) => {
    const { success, data } = await merchantFetch({
      url: 'vkyc',
      method: 'POST',
      mode: 'live',
      body: {
        name,
      },
    });

    if (success && data) {
      return data;
    }

    return null;
  },
);

const EXCLUDED_ACTIONS_FOR_V_KYC_BANNER: string[] = [
  fetchEddDetails.pending.type,
  fetchEddDetails.fulfilled.type,
  fetchEddDetails.rejected.type,
];

const videoKycBannerSlice = createSlice({
  name: 'videoKycBanner',
  initialState,
  reducers: {
    setVKycStatus: (state, action) => {
      state.vKycStatus = action.payload.status;

      if (action.payload.status === V_KYC_STATUS.REJECTED) {
        state.vKycRejectedReason = action.payload.reason;
      }
    },
    setEddStatus: (state, action) => {
      state.eddStatus = action.payload;
    },
    setMoneySaverAccountsActivated: (state, action) => {
      state.moneySaverAccountsActivated = action.payload;
    },
    setInstantBankAccountsActivated: (state, action) => {
      state.instantBankAccountsActivated = action.payload;
    },
    setInternational: (state, action) => {
      state.international = action.payload;
    },
    setIsLoading: (state, action) => {
      state.isLoading = action.payload;
    },
    setIsLoaded: (state, action) => {
      state.isLoaded = action.payload;
    },
    setIntlProductStatus: (state, action) => {
      state.intlProductStatus = action.payload;
    },
    setIsCreateVideoKYCModalOpen: (state, action) => {
      state.isCreateVideoKYCModalOpen = action.payload;
    },
  },
  extraReducers: (builder) => {
    builder.addCase(fetchEddDetails.pending, (state) => {
      state.isLoading = true;
    });
    builder.addCase(fetchEddDetails.rejected, (state) => {
      state.isLoading = false;
    });
    builder.addCase(fetchEddDetails.fulfilled, (state, action) => {
      state.isLoading = false;
      state.isLoaded = true;
      state.eddStatus = action.payload.eddStatus;
      state.vKycStatus = action.payload.vKycStatus;
      state.vKycRejectedReason = action.payload.vKycRejectedReason;
    });
    builder.addCase(createVKYCLink.pending, (state) => {
      state.isCreatingLink = true;
    });
    builder.addCase(createVKYCLink.rejected, (state) => {
      state.isCreatingLink = false;
    });
    builder.addCase(createVKYCLink.fulfilled, (state) => {
      state.isCreatingLink = false;
    });
    builder.addMatcher(
      (action) => !EXCLUDED_ACTIONS_FOR_V_KYC_BANNER.includes(action.type),
      (state) => {
        const {
          international: isInternational,
          moneySaverAccountsActivated: isMoneySaverAccountsActivated,
          instantBankAccountsActivated: isInstantBankAccountsActivated,
          eddStatus,
          vKycStatus,
        } = state;
        /**
         * for existing merchants who have activated their moneySaverExportAccounts and instantBankAccounts
         * v_kyc is not required, also if edd is verified or rejected, v_kyc is not required
         */
        const isAllMethodsActivated =
          isInternational && isMoneySaverAccountsActivated && isInstantBankAccountsActivated;

        if (isAllMethodsActivated || eddStatus === EDD_STATUS.VERIFIED) {
          state.vKycBannerStatus = V_KYC_BANNER_STATUS.COMPLETED;
          return;
        }

        /**
         * if v_kyc is rejected or failed or edd status is rejected, then show retry banner
         */
        const isVKycFailedOrRejected = (
          [V_KYC_STATUS.REJECTED, V_KYC_STATUS.FAILED] as Array<string | null>
        ).includes(vKycStatus);

        if (isVKycFailedOrRejected || eddStatus === EDD_STATUS.REJECTED) {
          state.vKycBannerStatus = V_KYC_BANNER_STATUS.RETRY;

          return;
        }

        const isEDDStatusInitiatedOrDefault = [
          EDD_STATUS.INITIATED,
          EDD_STATUS.NOT_VERIFIED,
        ].includes(eddStatus as typeof EDD_STATUS.INITIATED);

        /**
         * if v_kyc is default and edd is initiated or not_verified, then show initial banner
         */
        if (isEDDStatusInitiatedOrDefault && vKycStatus === V_KYC_STATUS.DEFAULT) {
          state.vKycBannerStatus = V_KYC_BANNER_STATUS.INITIAL;

          return;
        }

        /**
         * if v_kyc is initiated and edd is initiated or default, then show in-progress banner
         */
        if (isEDDStatusInitiatedOrDefault && vKycStatus === V_KYC_STATUS.INITIATED) {
          state.vKycBannerStatus = V_KYC_BANNER_STATUS.IN_PROGRESS;

          return;
        }

        /**
         * if v_kyc is under_review or approved and edd is initiated or default, then show pending banner
         */
        if (vKycStatus === V_KYC_STATUS.UNDER_REVIEW || vKycStatus === V_KYC_STATUS.APPROVED) {
          state.vKycBannerStatus = V_KYC_BANNER_STATUS.PENDING;
        }
      },
    );
  },
});

export const videoKycBannerActions = videoKycBannerSlice.actions;

export default videoKycBannerSlice.reducer;
