import { merge } from 'common/utils/immutable';
import Migration from 'merchant/models/Capital/Migrations';
const FETCH_MERCHANT_DETAILS = 'FETCH_MERCHANT_DETAILS';

export const fetchMerchantDetails = (data) => {
  const migration = new Migration();
  return {
    type: FETCH_MERCHANT_DETAILS,
    payload: migration.fetchMerchantDetails(data),
  };
};

export const updateMerchantDetails = (payload) => {
  const migration = new Migration();
  return migration.updateMerchantDetails(payload);
};

const getInitialState = () => {
  return {
    merchantGromorEsignDetails: {
      loading: false,
      data: {},
      error: null,
    },
  };
};

const initialState = getInitialState();

export default function (state = initialState, action) {
  switch (action.type) {
    case `${FETCH_MERCHANT_DETAILS}::PENDING`:
      return merge(state, {
        merchantGromorEsignDetails: {
          loading: true,
          data: state.merchantGromorEsignDetails.data,
        },
      });

    case `${FETCH_MERCHANT_DETAILS}::SUCCESS`:
      return merge(state, {
        merchantGromorEsignDetails: {
          loading: false,
          data: action.payload.data,
        },
      });

    case `${FETCH_MERCHANT_DETAILS}::ERROR`:
      return merge(state, {
        merchantGromorEsignDetails: {
          loading: false,
          data: {},
          error: action.payload.errors,
        },
      });

    default:
      return state;
  }
}
