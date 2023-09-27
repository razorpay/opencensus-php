import { merge } from 'common/utils/immutable';
import { ADD_PROFILE } from 'merchant/views/MagicCheckout/ShippingSettings/constants';

import { ShippingEngineStore } from './types';

export const createOrUpdateCategory = (action, state: ShippingEngineStore): ShippingEngineStore => {
  if (state.selected_profile) {
    delete state.shipping_profiles[ADD_PROFILE];
    return merge(state, {
      ...state,
      shipping_profiles: {
        ...state.shipping_profiles,
        [action.payload.data.name]: {
          ...state.shipping_profiles[state.selected_profile],
          ...action.payload.data,
          item_count: action.payload.data.items?.length,
        },
      },
      selected_profile: action.payload.data.name,
      isLoading: { ...state.isLoading, item_categories: false },
      validations: { ...state.validations, item_categories: true },
    });
  }
  return state;
};

export const deleteCategory = (action, state: ShippingEngineStore): ShippingEngineStore => {
  delete state.shipping_profiles[action.category.name];
  let profiles = { ...state.shipping_profiles };
  if (state.selected_profile) {
    profiles = { ...profiles, [ADD_PROFILE]: { name: ADD_PROFILE } };
  }
  return merge(state, {
    ...state,
    selected_profile: state.selected_profile ? ADD_PROFILE : null,
    shipping_profiles: {
      ...profiles,
    },
    isLoading: { ...state.isLoading, item_categories: false },
    validations: { ...state.validations, item_categories: true },
  });
};
