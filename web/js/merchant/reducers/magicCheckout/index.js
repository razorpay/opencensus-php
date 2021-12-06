import { merge, set } from 'common/utils/immutable';
import { merchantFetch } from 'merchant/utils/ajax';

export const REFRESH_MAGIC_CHECKOUT_STATUS = 'REFRESH_MAGIC_CHECKOUT_STATUS';

export const fetchMagicCheckoutStatus = (params) => {
  const url = 'merchant/checkout_details';

  const payload = merchantFetch({
    url,
    params,
  });

  return {
    type: REFRESH_MAGIC_CHECKOUT_STATUS,
    payload,
  };
};

export const updateMagicCheckoutStatus = (data, params) => {
  const url = 'merchant/checkout_details';

  const payload = merchantFetch({
    url,
    params,
    data: {
      status_1cc: data.status,
      merchant_id: data.merchant_id,
    },
    method: 'post',
  });

  return {
    type: REFRESH_MAGIC_CHECKOUT_STATUS,
    payload,
  };
};

const initialState = {
  loading: true,
  status: 'available',
  error: null,
};

export default function magicCheckoutReducer(state = initialState, action) {
  switch (action.type) {
    case `${REFRESH_MAGIC_CHECKOUT_STATUS}::SUCCESS`:
      return merge(state, {
        loading: false,
        status: action.payload?.data?.status_1cc || 'available',
        error: null,
      });
    case `${REFRESH_MAGIC_CHECKOUT_STATUS}::PENDING`:
      return set(state, 'loading', true);
    default:
      return state;
  }
}
