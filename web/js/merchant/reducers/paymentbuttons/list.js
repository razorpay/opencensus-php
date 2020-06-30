import { set, merge, unshift } from 'common/utils/immutable';
import { merchantFetch } from 'merchant/utils/ajax';

const PAYMENT_BUTTONS_FETCH = 'PAYMENT_BUTTONS_FETCH';

export const fetchPaymentButtonsList = params => {
  return {
    type: PAYMENT_BUTTONS_FETCH,
    payload: merchantFetch({
      url: 'payment_pages',
      method: 'get',
      data: {
        view_type: 'button',
        ...params,
      },
    }),
  };
};

export const updatePBInReduxList = (newLink, isNew) => {
  return {
    type: isNew ? 'PB_CREATE' : 'PB_EDIT',
    payload: newLink,
  };
};

const initialState = {
  loading: true,
  items: [],
  count: 0,
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${PAYMENT_BUTTONS_FETCH}::PENDING`:
      return merge(state, {
        loading: true,
        items: [],
      });

    case `${PAYMENT_BUTTONS_FETCH}::SUCCESS`: {
      return merge(state, {
        loading: false,
        items: action.payload.data.items,
        count: action.payload.data.count,
      });
    }

    case `${PAYMENT_BUTTONS_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.error,
      });

    case 'PB_CREATE':
      return set(state, 'items', unshift(state.items, action.payload));

    case 'PB_EDIT':
      const entityIndex = state.items.findIndex(
        entity => entity.id === action.payload.id
      );
      return set(state, `items.${entityIndex}`, action.payload);

    default:
      return state;
  }
}
