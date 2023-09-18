import { merchantFetch } from 'merchant/utils/ajax';
import { merge } from 'common/utils/immutable';
import { FALLBACK_PRODUCTS } from 'merchant/components/SidebarV2/utils/Fallback';

const FETCH_LEFT_NAV_ITEMS = 'FETCH_LEFT_NAV_ITEMS';

const initialState = {
  loading: true,
  error: null,
  data: FALLBACK_PRODUCTS,
};
export const fetchLeftNavItems = () => {
  return {
    type: FETCH_LEFT_NAV_ITEMS,
    payload: merchantFetch({ absUrl: '/merchant/navigation', timeout: 4000 }),
  };
};

export default function leftNavReducer(state = initialState, action) {
  switch (action.type) {
    case `${FETCH_LEFT_NAV_ITEMS}::SUCCESS`: {
      const data = action?.payload?.data?.sections || FALLBACK_PRODUCTS;
      return merge(state, {
        data,
        loading: false,
        error: null,
      });
    }
    case `${FETCH_LEFT_NAV_ITEMS}::ERROR`:
      return merge(state, {
        data: FALLBACK_PRODUCTS,
        loading: false,
        error: action.payload.errors,
      });
    default:
      return state;
  }
}
