import { set, merge } from 'common/utils/immutable';

import {
  B2B_EXPORTS_TRANSACTIONS_FETCH,
  B2B_EXPORTS_FETCH_ACCOUNTS,
  B2B_EXPORTS_ACTIVATE_ACCOUNTS,
  B2B_EXPORTS_UPLOAD_INVOICE,
  B2B_EXPORTS_SET_FEATURE,
  B2B_EXPORTS_GET_INVOICE_DETAILS,
  B2B_EXPORTS_GET_BALANCE,
  B2B_EXPORTS_GET_BENEFICIARY,
  B2B_EXPORTS_CREATE_PAYOUT,
} from './constants';

const transactionInitialStates = {
  isLoading: false,
  isUploading: false,
  error: null,
  invoicesUploading: {},
  invoiceFetching: {},
  data: {
    items: [],
  },
};

function b2bExportsTransactionsReducer(state = transactionInitialStates, action) {
  switch (action.type) {
    case `${B2B_EXPORTS_TRANSACTIONS_FETCH}::PENDING`: {
      return set(state, 'isLoading', true);
    }
    case `${B2B_EXPORTS_TRANSACTIONS_FETCH}::ERROR`: {
      const { errors } = action.payload || {};
      return merge(state, {
        error: Array.isArray(errors) ? errors.join(' ') : '',
        isLoading: false,
      });
    }
    case `${B2B_EXPORTS_TRANSACTIONS_FETCH}::SUCCESS`: {
      const { data } = action.payload || {};
      return merge(state, {
        isLoading: false,
        data: {
          ...state.data,
          ...data,
        },
      });
    }
    case `${B2B_EXPORTS_UPLOAD_INVOICE}::PENDING`: {
      if (action.payload?.id) {
        return merge(state, {
          invoicesUploading: {
            ...state.invoicesUploading,
            [action.payload.id]: true,
          },
        });
      }
      return state;
    }
    case `${B2B_EXPORTS_UPLOAD_INVOICE}::ERROR`:
    case `${B2B_EXPORTS_UPLOAD_INVOICE}::SUCCESS`: {
      if (action.payload?.id) {
        return merge(state, {
          invoicesUploading: {
            ...state.invoicesUploading,
            [action.payload.id]: false,
          },
        });
      }
      return state;
    }
    case `${B2B_EXPORTS_GET_INVOICE_DETAILS}::STATUS_PENDING`: {
      if (action.payload?.id) {
        return merge(state, {
          invoiceFetching: {
            ...state.invoiceFetching,
            [action.payload.id]: true,
          },
        });
      }
      return state;
    }
    case `${B2B_EXPORTS_GET_INVOICE_DETAILS}::STATUS_ERROR`:
    case `${B2B_EXPORTS_GET_INVOICE_DETAILS}::STATUS_SUCCESS`: {
      if (action.payload?.id) {
        return merge(state, {
          invoiceFetching: {
            ...state.invoiceFetching,
            [action.payload.id]: false,
          },
        });
      }
      return state;
    }
    default:
      return state;
  }
}

function b2bExportsAccountsReducer(
  state = {
    isLoading: false,
    isActivating: false,
    data: [],
    featureFlags: {
      isAccountCreated: false,
      isB2BEnabled: false,
    },
    error: null,
  },
  action,
) {
  switch (action.type) {
    case `${B2B_EXPORTS_FETCH_ACCOUNTS}::PENDING`: {
      return set(state, 'isLoading', true);
    }
    case `${B2B_EXPORTS_FETCH_ACCOUNTS}::ERROR`: {
      return merge(state, {
        isLoading: false,
        error: action.payload,
      });
    }
    case `${B2B_EXPORTS_FETCH_ACCOUNTS}::SUCCESS`: {
      const accounts = action.payload?.data ?? [];
      return merge(state, {
        isLoading: false,
        data: Array.isArray(accounts) ? accounts : [],
      });
    }
    case `${B2B_EXPORTS_ACTIVATE_ACCOUNTS}::PENDING`: {
      return set(state, 'isActivating', true);
    }
    case `${B2B_EXPORTS_ACTIVATE_ACCOUNTS}::ERROR`: {
      return merge(state, {
        isActivating: false,
        error: action.payload,
      });
    }
    case `${B2B_EXPORTS_ACTIVATE_ACCOUNTS}::SUCCESS`: {
      return merge(state, {
        isActivating: false,
        data: action.payload.data,
        featureFlags: {
          ...state.featureFlags,
          isAccountCreated: true,
        },
      });
    }
    case B2B_EXPORTS_SET_FEATURE: {
      return set(state, 'featureFlags', {
        ...state.featureFlags,
        ...action.payload,
      });
    }
    default:
      return state;
  }
}

function b2bExportsAccountBalanceReducer(
  state = {
    isLoading: false,
    data: null,
    error: false,
    errorMessage: 'Failed to check account balance. Try again after sometime.',
  },
  action,
) {
  switch (action.type) {
    case `${B2B_EXPORTS_GET_BALANCE}::PENDING`: {
      return set(state, 'isLoading', true);
    }
    case `${B2B_EXPORTS_GET_BALANCE}::SUCCESS`: {
      if (action.payload?.success) {
        const amount = action.payload?.data?.amount;
        const currency = action.payload?.data?.currency;
        if (amount !== undefined && currency) {
          return merge(state, {
            isLoading: false,
            data: {
              [currency]: {
                amount,
                lastFetched: Date.now(),
              },
            },
            error: false,
          });
        }
      }

      return merge(state, {
        isLoading: false,
        error: true,
      });
    }
    case `${B2B_EXPORTS_GET_BALANCE}::ERROR`: {
      return merge(state, {
        isLoading: false,
        error: true,
      });
    }
    default: {
      return state;
    }
  }
}

function b2bExportsBeneficiaryReducer(
  state = {
    isLoading: false,
    isPayoutSubmitting: false,
    data: null,
    error: false,
  },
  action,
) {
  switch (action.type) {
    case `${B2B_EXPORTS_GET_BENEFICIARY}::PENDING`: {
      return set(state, 'isLoading', true);
    }
    case `${B2B_EXPORTS_CREATE_PAYOUT}::PENDING`: {
      return set(state, 'isPayoutSubmitting', true);
    }
    case `${B2B_EXPORTS_GET_BENEFICIARY}::SUCCESS`: {
      return merge(state, {
        isLoading: false,
        error: false,
        data: action.payload,
      });
    }
    case `${B2B_EXPORTS_CREATE_PAYOUT}::SUCCESS`: {
      return set(state, 'isPayoutSubmitting', false);
    }
    case `${B2B_EXPORTS_GET_BENEFICIARY}::ERROR`: {
      return merge(state, {
        isLoading: false,
        error: true,
      });
    }
    default: {
      return state;
    }
  }
}

export {
  b2bExportsTransactionsReducer,
  b2bExportsAccountsReducer,
  b2bExportsAccountBalanceReducer,
  b2bExportsBeneficiaryReducer,
};
