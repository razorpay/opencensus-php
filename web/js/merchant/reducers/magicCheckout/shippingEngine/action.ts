import { merchantFetch } from 'merchant/utils/ajax';
import { Zone } from 'merchant/views/MagicCheckout/common/components/ZoneModal/types';

const REDUCER_NAMESPACE = 'MAGIC_SHIPPING_ENGINE';

export const ACTIONS = {
  FETCH_CONFIG: `${REDUCER_NAMESPACE}_FETCH_CONFIG`,
  FETCH_CONFIG_PENDING: `${REDUCER_NAMESPACE}_FETCH_CONFIG::PENDING`,
  FETCH_CONFIG_SUCCESS: `${REDUCER_NAMESPACE}_FETCH_CONFIG::SUCCESS`,
  FETCH_CONFIG_ERROR: `${REDUCER_NAMESPACE}_FETCH_CONFIG::ERROR`,

  UPDATE_ENGINE_CONFIG: `${REDUCER_NAMESPACE}_UPDATE_ENGINE_CONFIG`,

  CREATE_ZONE: `${REDUCER_NAMESPACE}_CREATE_ZONE`,
  CREATE_ZONE_PENDING: `${REDUCER_NAMESPACE}_CREATE_ZONE::PENDING`,
  CREATE_ZONE_SUCCESS: `${REDUCER_NAMESPACE}_CREATE_ZONE::SUCCESS`,
  CREATE_ZONE_ERROR: `${REDUCER_NAMESPACE}_CREATE_ZONE::ERROR`,

  UPDATE_ZONE: `${REDUCER_NAMESPACE}_UPDATE_ZONE`,
  UPDATE_ZONE_PENDING: `${REDUCER_NAMESPACE}_UPDATE_ZONE::PENDING`,
  UPDATE_ZONE_SUCCESS: `${REDUCER_NAMESPACE}_UPDATE_ZONE::SUCCESS`,
  UPDATE_ZONE_ERROR: `${REDUCER_NAMESPACE}_UPDATE_ZONE::ERROR`,

  DELETE_ZONE: `${REDUCER_NAMESPACE}_DELETE_ZONE`,
  DELETE_ZONE_PENDING: `${REDUCER_NAMESPACE}_DELETE_ZONE::PENDING`,
  DELETE_ZONE_SUCCESS: `${REDUCER_NAMESPACE}_DELETE_ZONE::SUCCESS`,
  DELETE_ZONE_ERROR: `${REDUCER_NAMESPACE}_DELETE_ZONE::ERROR`,

  CREATE_CATEGORY: `${REDUCER_NAMESPACE}_CREATE_CATEGORY`,
  CREATE_CATEGORY_PENDING: `${REDUCER_NAMESPACE}_CREATE_CATEGORY::PENDING`,
  CREATE_CATEGORY_SUCCESS: `${REDUCER_NAMESPACE}_CREATE_CATEGORY::SUCCESS`,
  CREATE_CATEGORY_ERROR: `${REDUCER_NAMESPACE}_CREATE_CATEGORY::ERROR`,

  UPDATE_CATEGORY: `${REDUCER_NAMESPACE}_UPDATE_CATEGORY`,
  UPDATE_CATEGORY_PENDING: `${REDUCER_NAMESPACE}_UPDATE_CATEGORY::PENDING`,
  UPDATE_CATEGORY_SUCCESS: `${REDUCER_NAMESPACE}_UPDATE_CATEGORY::SUCCESS`,
  UPDATE_CATEGORY_ERROR: `${REDUCER_NAMESPACE}_UPDATE_CATEGORY::ERROR`,

  DELETE_CATEGORY: `${REDUCER_NAMESPACE}_DELETE_CATEGORY`,
  DELETE_CATEGORY_PENDING: `${REDUCER_NAMESPACE}_DELETE_CATEGORY::PENDING`,
  DELETE_CATEGORY_SUCCESS: `${REDUCER_NAMESPACE}_DELETE_CATEGORY::SUCCESS`,
  DELETE_CATEGORY_ERROR: `${REDUCER_NAMESPACE}_DELETE_CATEGORY::ERROR`,

  CREATE_SHIPPING_METHOD: `${REDUCER_NAMESPACE}_CREATE_SHIPPING_METHOD`,
  CREATE_SHIPPING_METHOD_PENDING: `${REDUCER_NAMESPACE}_CREATE_SHIPPING_METHOD::PENDING`,
  CREATE_SHIPPING_METHOD_SUCCESS: `${REDUCER_NAMESPACE}_CREATE_SHIPPING_METHOD::SUCCESS`,
  CREATE_SHIPPING_METHOD_ERROR: `${REDUCER_NAMESPACE}_CREATE_SHIPPING_METHOD::ERROR`,

  UPDATE_SHIPPING_METHOD: `${REDUCER_NAMESPACE}_UPDATE_SHIPPING_METHOD`,
  UPDATE_SHIPPING_METHOD_PENDING: `${REDUCER_NAMESPACE}_UPDATE_SHIPPING_METHOD::PENDING`,
  UPDATE_SHIPPING_METHOD_SUCCESS: `${REDUCER_NAMESPACE}_UPDATE_SHIPPING_METHOD::SUCCESS`,
  UPDATE_SHIPPING_METHOD_ERROR: `${REDUCER_NAMESPACE}_UPDATE_SHIPPING_METHOD::ERROR`,

  DELETE_SHIPPING_METHOD: `${REDUCER_NAMESPACE}_DELETE_SHIPPING_METHOD`,
  DELETE_SHIPPING_METHOD_PENDING: `${REDUCER_NAMESPACE}_DELETE_SHIPPING_METHOD::PENDING`,
  DELETE_SHIPPING_METHOD_SUCCESS: `${REDUCER_NAMESPACE}_DELETE_SHIPPING_METHOD::SUCCESS`,
  DELETE_SHIPPING_METHOD_ERROR: `${REDUCER_NAMESPACE}_DELETE_ZONE::ERROR`,

  SET_PROFILE: `${REDUCER_NAMESPACE}_SET_PROFILE`,
  CLEAR_PROFILE: `${REDUCER_NAMESPACE}_CLEAR_PROFILE`,
};

export const fetchSummary = (setEditMode = true) => {
  return {
    type: ACTIONS.FETCH_CONFIG,
    payload: merchantFetch({
      url: '1cc/shipping/profiles',
    }),
    setEditMode,
  };
};

export const setProfile = (profile: string): any => {
  return {
    type: ACTIONS.SET_PROFILE,
    profile,
  };
};

export const clearProfile = (): any => {
  return {
    type: ACTIONS.CLEAR_PROFILE,
  };
};

export const updateEngineConfig = (payload) => {
  return {
    type: ACTIONS.UPDATE_ENGINE_CONFIG,
    payload,
  };
};

export const createZone = (payload: Zone): any => {
  return {
    type: ACTIONS.CREATE_ZONE,
    payload: merchantFetch({
      url: '1cc/shipping/zones',
      method: 'post',
      data: payload,
    }),
  };
};

export const updateZone = (payload) => {
  return {
    type: ACTIONS.UPDATE_ZONE,
    payload: merchantFetch({
      url: `1cc/shipping/zones/${payload.id}`,
      method: 'put',
      data: payload,
    }),
  };
};

export const deleteZone = (zone_id) => {
  return {
    type: ACTIONS.DELETE_ZONE,
    payload: merchantFetch({
      url: `1cc/shipping/zones/${zone_id}`,
      method: 'delete',
    }),
    zone_id,
  };
};

export const createCategory = (payload) => {
  return {
    type: ACTIONS.CREATE_CATEGORY,
    payload: merchantFetch({
      url: '1cc/shipping/item/category',
      method: 'post',
      data: payload,
    }),
  };
};

export const updateCategory = (payload) => {
  return {
    type: ACTIONS.UPDATE_CATEGORY,
    payload: merchantFetch({
      url: `1cc/shipping/item/category/${payload.id}`,
      method: 'put',
      data: payload,
    }),
  };
};

export const deleteCategory = (category) => {
  return {
    type: ACTIONS.DELETE_CATEGORY,
    payload: merchantFetch({
      url: `1cc/shipping/item/category/${category.id}`,
      method: 'delete',
    }),
    category,
  };
};

export const createShippingMethod = (payload) => {
  return {
    type: ACTIONS.CREATE_SHIPPING_METHOD,
    payload: merchantFetch({
      url: '1cc/shipping/methods',
      method: 'post',
      data: payload,
    }),
    zone_id: payload.zone_id,
  };
};

export const updateShippingMethod = (payload) => {
  return {
    type: ACTIONS.UPDATE_SHIPPING_METHOD,
    payload: merchantFetch({
      url: `1cc/shipping/methods/${payload.id}`,
      method: 'put',
      data: payload,
    }),
    zone_id: payload.zone_id,
  };
};

export const deleteShippingMethod = (method_id, zone_id) => {
  return {
    type: ACTIONS.DELETE_SHIPPING_METHOD,
    payload: merchantFetch({
      url: `1cc/shipping/methods/${method_id}`,
      method: 'delete',
    }),
    method_id,
    zone_id,
  };
};
