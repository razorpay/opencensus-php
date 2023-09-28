import { merge, push, updateItem } from 'common/utils/immutable';

import { ShippingEngineStore } from './types';
import { ACTIONS } from './action';

export const createOrUpdateShippingMethod = (
  action,
  state: ShippingEngineStore,
): ShippingEngineStore => {
  if (state.selected_profile) {
    const profile = state.shipping_profiles[state.selected_profile];
    const zones =
      profile.zones?.map((zone) => {
        if (zone.id === action.zone_id) {
          if (action.type === ACTIONS.CREATE_SHIPPING_METHOD_SUCCESS) {
            zone.shipping_methods = push(zone.shipping_methods, action.payload.data);
          } else {
            const idx = zone.shipping_methods.findIndex((s) => s.id === action.payload.data.id);
            zone.shipping_methods = updateItem(zone.shipping_methods, idx, action.payload.data);
          }
        }
        return zone;
      }) || [];
    return merge(state, {
      ...state,
      shipping_profiles: {
        ...state.shipping_profiles,
        [state.shipping_profiles[state.selected_profile].name]: {
          ...state.shipping_profiles[state.selected_profile],
          zones: [...zones],
        },
      },
      isLoading: { ...state.isLoading, shipping_methods: false },
      validations: { ...state.validations, shipping_methods: true },
    });
  }
  return state;
};

export const deleteShippingMethod = (action, state: ShippingEngineStore): ShippingEngineStore => {
  if (state.selected_profile) {
    const profile = state.shipping_profiles[state.selected_profile];
    const zones =
      profile.zones?.map((zone) => {
        if (zone.id === action.zone_id) {
          zone.shipping_methods = zone.shipping_methods.filter(
            (method) => method.id !== action.method_id,
          );
        }
        return zone;
      }) || [];
    return merge(state, {
      ...state,
      shipping_profiles: {
        ...state.shipping_profiles,
        [state.shipping_profiles[state.selected_profile].name]: {
          ...state.shipping_profiles[state.selected_profile],
          zones: [...zones],
        },
      },
      isLoading: { ...state.isLoading, shipping_methods: false },
      validations: { ...state.validations, shipping_methods: true },
    });
  }
  return state;
};
