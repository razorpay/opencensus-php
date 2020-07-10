import { set, merge, unshift } from 'common/utils/immutable';
import { merchantFetch } from 'merchant/utils/ajax';

const SUBSCRIPTION_BUTTONS_FETCH = 'SUBSCRIPTION_BUTTONS_FETCH';
const SUBSCRIPTION_BUTTON_CREATE = 'SUBSCRIPTION_BUTTON_CREATE';
const SUBSCRIPTION_BUTTON_EDIT = 'SUBSCRIPTION_BUTTON_EDIT';

export const fetchSubscriptionButtonsList = params => {
  return {
    type: SUBSCRIPTION_BUTTONS_FETCH,
    payload: merchantFetch({
      url: 'payment_pages',
      method: 'get',
      data: {
        view_type: 'subscription_button',
        ...params,
      },
    }),
  };
};

export const updateSubscriptionButtonInReduxList = (newLink, isNew) => {
  return {
    type: isNew ? SUBSCRIPTION_BUTTON_CREATE : SUBSCRIPTION_BUTTON_EDIT,
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
    case `${SUBSCRIPTION_BUTTONS_FETCH}::PENDING`:
      return merge(state, {
        loading: true,
        items: [],
      });

    case `${SUBSCRIPTION_BUTTONS_FETCH}::SUCCESS`: {
      return merge(state, {
        loading: false,
        items: action.payload.data.items,
        count: action.payload.data.count,
      });
    }

    case `${SUBSCRIPTION_BUTTONS_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.error,
      });

    case SUBSCRIPTION_BUTTON_CREATE:
      return set(state, 'items', unshift(state.items, action.payload));

    case SUBSCRIPTION_BUTTON_EDIT:
      const entityIndex = state.items.findIndex(
        entity => entity.id === action.payload.id
      );
      return set(state, `items.${entityIndex}`, action.payload);

    default:
      return state;
  }
}
