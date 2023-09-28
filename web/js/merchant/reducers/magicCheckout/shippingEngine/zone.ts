import { merge, push, updateItem } from 'common/utils/immutable';

import { ACTIONS } from './action';
import { ShippingEngineStore } from './types';
import { buildZonesData } from './utils';

export const createOrUpdateZone = (action, state: ShippingEngineStore): ShippingEngineStore => {
  if (state.selected_profile) {
    let zones = state.shipping_profiles[state.selected_profile]?.zones || [];
    if (action.type === ACTIONS.CREATE_ZONE_SUCCESS) {
      zones = push(
        state.shipping_profiles[state.selected_profile]?.zones || [],
        ...buildZonesData([action.payload.data]),
      );
    } else {
      const idx = zones.findIndex((z) => z.id === action.payload.data.id);
      zones = updateItem(zones, idx, ...buildZonesData([action.payload.data]));
    }
    return merge(state, {
      ...state,
      shipping_profiles: {
        ...state.shipping_profiles,
        [state.shipping_profiles[state.selected_profile].name]: {
          ...state.shipping_profiles[state.selected_profile],
          zones,
        },
      },
      isLoading: { ...state.isLoading, zones: false },
      validations: { ...state.validations, zones: true },
    });
  }
  return state;
};

export const deleteZone = (action, state: ShippingEngineStore): ShippingEngineStore => {
  if (state.selected_profile) {
    const profile = state.shipping_profiles[state.selected_profile];
    const zones = profile.zones?.filter((zone) => zone.id !== action.zone_id) || [];
    return merge(state, {
      ...state,
      shipping_profiles: {
        ...state.shipping_profiles,
        [state.shipping_profiles[state.selected_profile].name]: {
          ...state.shipping_profiles[state.selected_profile],
          zones: [...zones],
        },
      },
      isLoading: { ...state.isLoading, zones: false },
      validations: { ...state.validations, zones: true },
    });
  }
  return state;
};
