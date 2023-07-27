export const INITIAL_STATE = {
  isLoading: false,
  data: [],
  error: null,
  featureFlags: {
    isB2BEnabled: false,
  },
  localBankTransfer: {
    isActivating: false,
    error: null,
  },
  intBankTransfer: {
    isActivating: false,
    error: null,
  },
  accountsDeactivated: false,
  reason: '',
  isIneligiblePurposeCodeModalOpen: false,
};
