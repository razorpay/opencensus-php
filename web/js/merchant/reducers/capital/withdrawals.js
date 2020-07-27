import { merge } from 'common/utils/immutable';
import Withdrawal from 'merchant/models/Capital/Withdrawals';

const FETCH_WITHDRAWAL_CONFIG = 'FETCH_WITHDRAWAL_CONFIG';
const FETCH_SEED_DATA = 'FETCH_SEED_DATA';
const FETCH_WITHDRAWALS = 'FETCH_WITHDRAWALS';
const FETCH_WITHDRAWAL_DETAILS = 'FETCH_WITHDRAWAL_DETAILS';

export const fetchSeedData = () => {
  const withdrawal = new Withdrawal();

  return {
    type: FETCH_SEED_DATA,
    payload: withdrawal.fetchSeedData(),
  };
};

export const fetchWithdrawalConfiguration = data => {
  const withdrawal = new Withdrawal();
  return {
    type: FETCH_WITHDRAWAL_CONFIG,
    payload: withdrawal.fetchWithdrawalConfiguration(data),
  };
};

export const fetchWithdrawalConfigurationByMerchantID = data => {
  const withdrawal = new Withdrawal();
  return {
    type: FETCH_WITHDRAWAL_CONFIG,
    payload: withdrawal.fetchWithdrawalConfigurationByMerchantID(data),
  };
};

export const fetchWithdrawals = data => {
  const withdrawal = new Withdrawal();
  return {
    type: FETCH_WITHDRAWALS,
    payload: withdrawal.fetchWithdrawals(data),
  };
};

export const fetchWithdrawalDetails = data => {
  const withdrawal = new Withdrawal();
  return {
    type: FETCH_WITHDRAWAL_DETAILS,
    payload: withdrawal.fetchWithdrawalDetails(data),
  };
};

export const createWithdrawal = payload => {
  const withdrawal = new Withdrawal();
  return withdrawal.createWithdrawal(payload);
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
      data: [],
      error: null,
    },
  };
};

const initialState = getInitialState();

export default function(state = initialState, action) {
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

    case `${FETCH_WITHDRAWALS}::SUCCESS`:
      return merge(state, {
        list: {
          loading: false,
          data: action.payload.data.withdrawal,
        },
      });

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

    default:
      return state;
  }
}
