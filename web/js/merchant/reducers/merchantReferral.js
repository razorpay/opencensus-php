import { merchantFetch } from 'merchant/utils/ajax';
import { merge } from 'common/utils/immutable';

//supprt detail constant
const FETCH_MERCHANT_REFERRAL_DETAILS = 'FETCH_MERCHANT_REFERRAL_DETAILS';

const initialState = {
  loading: true,
  error: null,
  data: {},
};

//actions
export const fetchMerchantReferralDetail = () => {
  return {
    type: FETCH_MERCHANT_REFERRAL_DETAILS,
    payload: merchantFetch({ url: 'merchants/onboarding/m2m_referral', mode: 'live' }),
  };
};

//reducers
export default function merchantReferral(state = initialState, action) {
  switch (action.type) {
    case `${FETCH_MERCHANT_REFERRAL_DETAILS}::SUCCESS`:
      return merge(state, {
        data: action.payload.data,
        loading: false,
        error: null,
      });

    case `${FETCH_MERCHANT_REFERRAL_DETAILS}::ERROR`:
      return merge(state, {
        data: {},
        loading: false,
        error: action.payload.errors,
      });

    default:
      return state;
  }
}
