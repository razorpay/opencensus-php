import { merge } from 'common/utils/immutable';
import { ACTIONS } from 'merchant/reducers/magicCheckout/prepayCOD/orderConversionTab/actions';

const listViewInitialState = {
  id: '',
  receipt: '',
  riskTier: '',
  from: '',
  to: '',
  count: 25,
  skip: 0,
  items: [],
  loading: false,
  paymentLinkStatus: '',
  error: null,
  selectedPresetFromParent: null,
  hasMoreOrders: true,
};

export const magicPrepayCODOrdersReducer = (state = listViewInitialState, action) => {
  switch (action.type) {
    case ACTIONS.UPDATE_FILTERS:
      return merge(state, { ...action.payload });
    case ACTIONS.FETCH_ORDERS_PENDING:
      return merge(state, { loading: true, items: [] });
    case ACTIONS.FETCH_ORDERS_SUCCESS:
      return merge(state, {
        loading: false,
        items: action.payload?.data?.items,
        error: null,
        hasMoreOrders: action.payload?.data?.has_more,
        ...action.data,
      });
    case ACTIONS.FETCH_ORDERS_ERROR:
      return merge(state, { loading: false, error: action.payload?.error });
    case ACTIONS.SET_TIME_RANGE:
      return merge(state, { ...action.payload });
    case ACTIONS.REVIEW_ORDERS_PENDING:
    case ACTIONS.REVIEW_ORDERS_ERROR:
      return state;
    case ACTIONS.REVIEW_ORDERS_SUCCESS: {
      const res = action.payload.data;
      const newArray = state.items.map((item) =>
        item?.magic_payment_link?.id === res.id
          ? { ...item, magic_payment_link: { ...item.magic_payment_link, status: 'cancelled' } }
          : item,
      );
      return merge(state, { items: newArray });
    }
    default:
      return state;
  }
};

const orderDetailsInitialState = {
  items: [],
  loading: false,
  error: null,
};

export const magicPrepayCODOrderInfoReducer = (state = orderDetailsInitialState, action) => {
  switch (action.type) {
    case ACTIONS.FETCH_ORDER_INFO_PENDING:
      return merge(state, { loading: true, items: [] });
    case ACTIONS.FETCH_ORDER_INFO_SUCCESS:
      return merge(state, { loading: false, items: [action.payload?.data], error: null });
    case ACTIONS.FETCH_ORDER_INFO_ERROR:
      return merge(state, { loading: false, error: action.payload?.error });
    default:
      return state;
  }
};
