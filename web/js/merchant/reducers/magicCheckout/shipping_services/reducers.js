import { merge } from 'common/utils/immutable';
import { formatShippingMethods } from 'merchant/reducers/magicCheckout/shipping_services/formatters';
import { ACTIONS } from 'merchant/reducers/magicCheckout/shipping_services/actions';

const initialState = {
  shippingProviders: {},
  shippingMethods: {},
  userShippingMethods: {
    shipping_fee_rule: {
      rule_type: 'free',
      flat: 0,
      slabs: [{ gte: 0, lte: 0, fee: 0 }],
    },
    cod_fee_rule: {
      rule_type: 'free',
      flat: 0,
      slabs: [{ gte: 0, lte: 0, fee: 0 }],
    },
    warehouse_pincode: '',
    enable_cod: true,
  },
  shouldCloseModal: false,
  errors: {},
  loading: true,
};

export default function shippingServicesReducer(state = initialState, action) {
  switch (action.type) {
    case ACTIONS.CREATE_SHIPPING_PROVIDERS_SUCCESS: {
      let newProviders = { ...state.shippingProviders };
      if (newProviders && newProviders.error) {
        delete newProviders.error;
      }
      const createdProvider = action.payload?.data;
      newProviders = createdProvider
        ? {
            ...newProviders,
            [createdProvider.provider_type]: { ...createdProvider },
          }
        : {};

      return merge(state, {
        shippingProviders: newProviders,
      });
    }
    case ACTIONS.CREATE_SHIPPING_PROVIDERS_ERROR:
      return merge(state, {
        shippingProviders: { ...state.shippingProviders, error: action.payload },
      });
    case ACTIONS.FETCH_SHIPPING_PROVIDERS_PENDING:
      return merge(state, { loading: true });
    case ACTIONS.FETCH_SHIPPING_PROVIDERS_SUCCESS: {
      const items = action.payload?.data?.items;
      let providers = {};
      let loader = false;
      providers = items.reduce((initialProvider, item) => {
        if (item.hasOwnProperty('shiprocket')) loader = true;
        return { ...initialProvider, [item.provider_type]: { ...item } };
      }, {});

      return merge(state, {
        shippingProviders: providers,
        loading: loader,
      });
    }
    case ACTIONS.DELETE_SHIPPING_PROVIDERS_SUCCESS: {
      const shippingProviders = { ...state.shippingProviders };
      for (const key in shippingProviders) {
        if (Object.values(shippingProviders[key]).includes(action.id)) {
          delete shippingProviders[key];
        }
      }
      return merge(state, {
        shippingProviders,
      });
    }
    case ACTIONS.CREATE_SHIPPING_METHOD_PROVIDERS_PENDING:
      return merge(state, {
        shouldCloseModal: false,
      });
    case ACTIONS.CREATE_SHIPPING_METHOD_PROVIDERS_SUCCESS: {
      const shippingMethods = formatShippingMethods(action.payload?.data || state.shippingMethods);
      return merge(state, {
        shouldCloseModal: true,
        shippingMethods,
        userShippingMethods: shippingMethods,
      });
    }
    case ACTIONS.FETCH_SHIPPING_METHOD_PROVIDERS_SUCCESS: {
      const methods = formatShippingMethods(
        action.payload?.data?.items[0] || state.shippingMethods,
      );
      const userShippingMethods = action.payload?.data?.items[0]
        ? methods
        : state.userShippingMethods;
      return merge(state, {
        shippingMethods: methods,
        userShippingMethods,
        loading: false,
      });
    }
    case ACTIONS.FETCH_SHIPPING_METHOD_PROVIDERS_ERROR:
      return merge(state, { loading: false });
    case ACTIONS.USER_SHIPPING_METHOD_UPDATE:
      return merge(state, {
        userShippingMethods: {
          ...state.userShippingMethods,
          ...action.payload,
        },
      });
    case ACTIONS.USER_SHIPPING_METHOD_RESET: {
      const methods = state.shippingMethods.id ? state.shippingMethods : state.userShippingMethods;
      return merge(state, { userShippingMethods: methods });
    }
    case ACTIONS.DELETE_SHIPPING_METHOD_PROVIDERS_SUCCESS:
      return merge(state, {
        shippingMethods: {},
        userShippingMethods: initialState.userShippingMethods,
      });
    case ACTIONS.MODAL_FLAG_MODIFY:
      return merge(state, {
        shouldCloseModal: action.payload.shouldCloseModal,
      });
    default:
      return state;
  }
}
