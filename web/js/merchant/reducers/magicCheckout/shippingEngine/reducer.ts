import { merge } from 'common/utils/immutable';
import { ADD_PROFILE } from 'merchant/views/MagicCheckout/ShippingSettings/constants';

import { ACTIONS } from './action';
import { createOrUpdateCategory, deleteCategory } from './category';
import { createOrUpdateShippingMethod, deleteShippingMethod } from './shippingMethods';
import { ShippingEngineStore, ShippingSummaryAPIResponse } from './types';
import { formatResponse } from './utils';
import { createOrUpdateZone, deleteZone } from './zone';

const initialState: ShippingEngineStore = {
  isLoading: {
    summary: false,
    zones: false,
    shipping_methods: false,
    item_categories: false,
  },
  shipping_profiles: {},
  selected_profile: '',
  validations: {
    zones: false,
    shipping_methods: false,
    item_categories: false,
  },
  default_profile: null,
};

export const shippingEngineReducer = (state = initialState, action): ShippingEngineStore => {
  switch (action.type) {
    case ACTIONS.SET_PROFILE: {
      const { profile } = action;
      let shipping_profiles = { ...state.shipping_profiles };
      if (profile === ADD_PROFILE) {
        shipping_profiles = { ...shipping_profiles, [profile]: { name: ADD_PROFILE } };
      }
      return merge(state, {
        selected_profile: profile,
        shipping_profiles,
      });
    }

    case ACTIONS.CLEAR_PROFILE: {
      const shipping_profiles = { ...state.shipping_profiles };
      if (shipping_profiles[ADD_PROFILE]) {
        delete shipping_profiles[ADD_PROFILE];
      }
      return merge(state, {
        selected_profile: null,
        shipping_profiles,
      });
    }

    case ACTIONS.FETCH_CONFIG_SUCCESS: {
      const { data } = action.payload;
      const { formattedProfiles, defaultProfile } = formatResponse(
        data as ShippingSummaryAPIResponse,
      );

      return merge(state, {
        isLoading: {
          summary: false,
          fee_rules: false,
          zones: false,
          item_categories: false,
          shipping_methods: false,
        },
        shipping_profiles: formattedProfiles,
        default_profile: defaultProfile,
        selected_profile: {},
        validations: {
          zones: true,
          item_categories: true,
        },
      });
    }

    // ZONES
    case ACTIONS.CREATE_ZONE_SUCCESS:
    case ACTIONS.UPDATE_ZONE_SUCCESS: {
      if (state.selected_profile) {
        return createOrUpdateZone(action, state);
      }
      return state;
    }
    case ACTIONS.DELETE_ZONE_SUCCESS: {
      return deleteZone(action, state);
    }

    case ACTIONS.CREATE_CATEGORY_SUCCESS:
    case ACTIONS.UPDATE_CATEGORY_SUCCESS:
      return createOrUpdateCategory(action, state);

    case ACTIONS.DELETE_CATEGORY_SUCCESS: {
      return deleteCategory(action, state);
    }

    case ACTIONS.CREATE_SHIPPING_METHOD_SUCCESS:
    case ACTIONS.UPDATE_SHIPPING_METHOD_SUCCESS:
      return createOrUpdateShippingMethod(action, state);

    case ACTIONS.DELETE_SHIPPING_METHOD_SUCCESS:
      return deleteShippingMethod(action, state);

    case ACTIONS.FETCH_CONFIG_PENDING:
      return merge(state, { isLoading: { ...state.isLoading, summary: true } });

    case ACTIONS.CREATE_ZONE_PENDING:
    case ACTIONS.UPDATE_ZONE_PENDING:
    case ACTIONS.DELETE_ZONE_PENDING:
      return merge(state, { isLoading: { ...state.isLoading, zones: true } });
    case ACTIONS.CREATE_CATEGORY_PENDING:
    case ACTIONS.UPDATE_CATEGORY_PENDING:
    case ACTIONS.DELETE_CATEGORY_PENDING:
      return merge(state, { isLoading: { ...state.isLoading, item_categories: true } });
    case ACTIONS.CREATE_SHIPPING_METHOD_PENDING:
    case ACTIONS.UPDATE_SHIPPING_METHOD_PENDING:
    case ACTIONS.DELETE_SHIPPING_METHOD_PENDING:
      return merge(state, { isLoading: { ...state.isLoading, shipping_methods: true } });
    case ACTIONS.FETCH_CONFIG_ERROR:
    case ACTIONS.CREATE_ZONE_ERROR:
    case ACTIONS.UPDATE_ZONE_ERROR:
    case ACTIONS.DELETE_ZONE_ERROR:
    case ACTIONS.CREATE_CATEGORY_ERROR:
    case ACTIONS.UPDATE_CATEGORY_ERROR:
    case ACTIONS.DELETE_CATEGORY_ERROR:
    case ACTIONS.DELETE_SHIPPING_METHOD_ERROR:
    case ACTIONS.UPDATE_SHIPPING_METHOD_ERROR:
    case ACTIONS.CREATE_SHIPPING_METHOD_ERROR:
      return merge(state, {
        isLoading: {
          summary: false,
          fee_rules: false,
          zones: false,
          item_categories: false,
          shipping_methods: false,
        },
        error: action.payload?.error,
      });
    default:
      return state;
  }
};
