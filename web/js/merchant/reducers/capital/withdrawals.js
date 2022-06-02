import { merge, set } from 'common/utils/immutable';
import Withdrawal from 'merchant/models/Capital/Withdrawals';

const FETCH_WITHDRAWAL_CONFIG = 'FETCH_WITHDRAWAL_CONFIG';
const FETCH_SEED_DATA = 'FETCH_SEED_DATA';
const FETCH_WITHDRAWALS = 'FETCH_WITHDRAWALS';
const FETCH_WITHDRAWAL_DETAILS = 'FETCH_WITHDRAWAL_DETAILS';
const FETCH_DESTINATION_DETAILS = 'FETCH_DESTINATION_DETAILS';
const FETCH_INSTALLMENTS = 'FETCH_INSTALLMENTS';
const UPDATE_AUTOMATED_LOC_CONFIG = 'UPDATE_AUTOMATED_LOC_CONFIG';
const FETCH_CURRENT_OUTSTANDING = 'FETCH_CURRENT_OUTSTANDING';

export const fetchSeedData = () => {
  const withdrawal = new Withdrawal();

  return {
    type: FETCH_SEED_DATA,
    payload: withdrawal.fetchSeedData(),
  };
};

export const fetchWithdrawalConfiguration = (data) => {
  const withdrawal = new Withdrawal();
  return {
    type: FETCH_WITHDRAWAL_CONFIG,
    payload: withdrawal.fetchWithdrawalConfiguration(data),
  };
};

export const fetchFunctionalWithdrawalConfigByMerchantID = (data) => {
  const withdrawal = new Withdrawal();
  return {
    type: FETCH_WITHDRAWAL_CONFIG,
    payload: withdrawal.fetchFunctionalWithdrawalConfigByMerchantID(data),
  };
};

export const fetchDestinationAccountDetails = (data) => {
  const withdrawal = new Withdrawal();
  return {
    type: FETCH_DESTINATION_DETAILS,
    payload: withdrawal.fetchDestinationAccountDetails(data),
  };
};

export const fetchWithdrawals = (data) => {
  const withdrawal = new Withdrawal();
  return {
    type: FETCH_WITHDRAWALS,
    payload: withdrawal.fetchWithdrawals(data),
  };
};

export const fetchWithdrawalDetails = (data) => {
  const withdrawal = new Withdrawal();
  return {
    type: FETCH_WITHDRAWAL_DETAILS,
    payload: withdrawal.fetchWithdrawalDetails(data),
  };
};

export const fetchInstallments = (data) => {
  const withdrawal = new Withdrawal();
  return {
    type: FETCH_INSTALLMENTS,
    payload: withdrawal.fetchInstallments(data),
  };
};

export const fetchCurrentOutstanding = (data) => {
  const withdrawal = new Withdrawal();
  return {
    type: FETCH_CURRENT_OUTSTANDING,
    payload: withdrawal.fetchCurrentOutstanding(data),
  };
};

export const createWithdrawal = (payload) => {
  const withdrawal = new Withdrawal();
  return withdrawal.createWithdrawal(payload);
};

export const updateAutomatedLOCConfig = (data) => {
  const withdrawal = new Withdrawal();
  return {
    type: UPDATE_AUTOMATED_LOC_CONFIG,
    payload: withdrawal.updateAutomatedLOCConfig(data),
  };
};

const getInitialState = () => {
  return {
    list: {
      loading: false,
      data: null,
      error: null,
    },
    withdrawalDetails: {
      loading: true,
      data: {},
      error: null,
    },
    withdrawalConfiguration: {
      loading: false,
      data: null,
      error: null,
    },
    seedData: {
      loading: false,
      data: null,
      error: null,
    },
    destinationAccountDetails: {
      loading: false,
      data: null,
      error: null,
    },
    installments: {
      loading: false,
      data: [],
      error: null,
    },
    current_outstanding: {
      loading: false,
      data: [],
      error: null,
    },
  };
};

const initialState = getInitialState();

export default function withdrawalFunction(state = initialState, action) {
  switch (action.type) {
    case `${FETCH_SEED_DATA}::PENDING`:
      return merge(state, {
        seedData: {
          loading: true,
        },
      });

    case `${FETCH_SEED_DATA}::SUCCESS`:
      return merge(state, {
        seedData: {
          loading: false,
          data: action.payload.data,
        },
      });

    case `${FETCH_SEED_DATA}::ERROR`:
      return merge(state, {
        seedData: {
          loading: false,
          error: action.payload.errors,
        },
      });
    case `${FETCH_WITHDRAWAL_CONFIG}::PENDING`:
      return merge(state, {
        withdrawalConfiguration: {
          loading: true,
          data: state.withdrawalConfiguration.data,
        },
      });

    case `${FETCH_WITHDRAWAL_CONFIG}::SUCCESS`:
      return merge(state, {
        withdrawalConfiguration: {
          loading: false,
          data: action.payload.data.withdrawal_config,
        },
      });

    case `${FETCH_WITHDRAWAL_CONFIG}::ERROR`:
      return merge(state, {
        withdrawalConfiguration: {
          loading: false,
          data: null,
          error: action.payload.errors,
        },
      });

    case `${FETCH_WITHDRAWALS}::PENDING`:
      return merge(state, {
        list: {
          loading: true,
          data: [],
        },
      });

    case `${FETCH_WITHDRAWALS}::SUCCESS`: {
      const { data: { withdrawal = [] } = {} } = action.payload;

      return merge(state, {
        list: {
          loading: false,
          data: withdrawal,
        },
      });
    }

    case `${FETCH_WITHDRAWALS}::ERROR`:
      return merge(state, {
        list: {
          loading: false,
          data: [],
          error: action.payload.errors,
        },
      });

    case `${FETCH_WITHDRAWAL_DETAILS}::PENDING`:
      return merge(state, {
        withdrawalDetails: {
          loading: true,
          data: {},
        },
      });

    case `${FETCH_WITHDRAWAL_DETAILS}::SUCCESS`:
      return merge(state, {
        withdrawalDetails: {
          loading: false,
          data: action.payload.data.withdrawal,
        },
      });

    case `${FETCH_WITHDRAWAL_DETAILS}::ERROR`:
      return merge(state, {
        withdrawalDetails: {
          loading: false,
          data: {},
          error: action.payload.errors,
        },
      });

    case `${FETCH_DESTINATION_DETAILS}::PENDING`:
      return merge(state, {
        destinationAccountDetails: {
          loading: true,
          data: {},
        },
      });

    case `${FETCH_DESTINATION_DETAILS}::SUCCESS`:
      return merge(state, {
        destinationAccountDetails: {
          loading: false,
          data: action.payload.data.destination_account,
        },
      });

    case `${FETCH_DESTINATION_DETAILS}::ERROR`:
      return merge(state, {
        destinationAccountDetails: {
          loading: false,
          data: {},
          error: action.payload.errors,
        },
      });
    case `${FETCH_INSTALLMENTS}::PENDING`:
      return merge(state, {
        installments: {
          loading: true,
        },
      });
    case `${FETCH_INSTALLMENTS}::SUCCESS`:
      return merge(state, {
        installments: {
          loading: false,
          data: action.payload.data.repayment_schedule,
        },
      });
    case `${FETCH_INSTALLMENTS}::ERROR`:
      return merge(state, {
        installments: {
          loading: false,
          error: action.payload.errors,
        },
      });

    case `${FETCH_CURRENT_OUTSTANDING}::PENDING`:
      return merge(state, {
        current_outstanding: {
          loading: true,
        },
      });

    case `${FETCH_CURRENT_OUTSTANDING}::SUCCESS`:
      return merge(state, {
        current_outstanding: {
          loading: false,
          data: action.payload.data.current_outstanding,
        },
      });

    case `${FETCH_CURRENT_OUTSTANDING}::ERROR`:
      return merge(state, {
        current_outstanding: {
          loading: false,
          error: action.payload.errors,
        },
      });

    case `${UPDATE_AUTOMATED_LOC_CONFIG}::SUCCESS`:
      return set(
        state,
        'withdrawalConfiguration.data.automated_loc',
        action.payload.data.curr_automated_loc || false,
      );
    default:
      return state;
  }
}
