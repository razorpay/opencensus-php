import { merchantFetch } from 'merchant/utils/ajax';

const REDUCER_NAMESPACE = 'COD_ENGINE';

export const ACTIONS = {
  FETCH_CONFIG: `${REDUCER_NAMESPACE}_FETCH_CONFIG`,
  FETCH_CONFIG_PENDING: `${REDUCER_NAMESPACE}_FETCH_CONFIG::PENDING`,
  FETCH_CONFIG_SUCCESS: `${REDUCER_NAMESPACE}_FETCH_CONFIG::SUCCESS`,
  FETCH_CONFIG_ERROR: `${REDUCER_NAMESPACE}_FETCH_CONFIG::ERROR`,

  UPDATE_ENGINE_CONFIG: `${REDUCER_NAMESPACE}_UPDATE_ENGINE_CONFIG`,

  UPSERT_FEE_RULES: `${REDUCER_NAMESPACE}_UPSERT_FEE_RULES`,
  UPSERT_FEE_RULES_PENDING: `${REDUCER_NAMESPACE}_UPSERT_FEE_RULES::PENDING`,
  UPSERT_FEE_RULES_SUCCESS: `${REDUCER_NAMESPACE}_UPSERT_FEE_RULES::SUCCESS`,
  UPSERT_FEE_RULES_ERROR: `${REDUCER_NAMESPACE}_UPSERT_FEE_RULES::ERROR`,

  UPDATE_FEE_RULE: `${REDUCER_NAMESPACE}_UPDATE_FEE_RULE`,
  UPDATE_FEE_RULE_PENDING: `${REDUCER_NAMESPACE}_UPDATE_FEE_RULE::PENDING`,
  UPDATE_FEE_RULE_SUCCESS: `${REDUCER_NAMESPACE}_UPDATE_FEE_RULE::SUCCESS`,
  UPDATE_FEE_RULE_ERROR: `${REDUCER_NAMESPACE}_UPDATE_FEE_RULE::ERROR`,

  DELETE_FEE_RULE: `${REDUCER_NAMESPACE}_DELETE_FEE_RULE`,
  DELETE_FEE_RULE_PENDING: `${REDUCER_NAMESPACE}_DELETE_FEE_RULE::PENDING`,
  DELETE_FEE_RULE_SUCCESS: `${REDUCER_NAMESPACE}_DELETE_FEE_RULE::SUCCESS`,
  DELETE_FEE_RULE_ERROR: `${REDUCER_NAMESPACE}_DELETE_FEE_RULE::ERROR`,

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

  VALIDATE_CONFIG: `${REDUCER_NAMESPACE}_VALIDATE_CONFIG`,
  SET_EDIT_MODE: `${REDUCER_NAMESPACE}_SET_EDIT_MODE`,
};

export const fetchConfig = () => {
  return {
    type: ACTIONS.FETCH_CONFIG,
    payload: merchantFetch({
      url: '1cc/shipping/cod/summary',
    }),
  };
};

export const updateEngineConfig = (payload) => {
  return {
    type: ACTIONS.UPDATE_ENGINE_CONFIG,
    payload,
  };
};

export const deleteFeeRule = (rule_id) => {
  return {
    type: ACTIONS.DELETE_FEE_RULE,
    payload: merchantFetch({
      url: `1cc/shipping/cod/fee_rule/${rule_id}`,
      method: 'delete',
    }),
    rule_id,
  };
};

export const upsertFeeRules = (payload) => {
  return {
    type: ACTIONS.UPSERT_FEE_RULES,
    payload: merchantFetch({
      url: '1cc/shipping/cod/fee_rules',
      method: 'post',
      data: payload,
    }),
  };
};

export const updateFeeRule = (payload) => {
  return {
    type: ACTIONS.UPDATE_FEE_RULE,
    payload: merchantFetch({
      url: '1cc/shipping/cod/fee_rule',
      method: 'post',
      data: payload,
    }),
  };
};

export const createZone = (payload) => {
  return {
    type: ACTIONS.CREATE_ZONE,
    payload: merchantFetch({
      url: '1cc/shipping/cod/zone',
      method: 'post',
      data: payload,
    }),
  };
};

export const updateZone = (payload) => {
  return {
    type: ACTIONS.UPDATE_ZONE,
    payload: merchantFetch({
      url: `1cc/shipping/cod/zone`,
      method: 'put',
      data: payload,
    }),
  };
};

export const deleteZone = (zone_id) => {
  return {
    type: ACTIONS.DELETE_ZONE,
    payload: merchantFetch({
      url: `1cc/shipping/cod/zone/${zone_id}`,
      method: 'delete',
    }),
    zone_id,
  };
};

export const validateConfig = (key, valid) => {
  return {
    type: ACTIONS.VALIDATE_CONFIG,
    payload: {
      valid,
      key,
    },
  };
};

export const setEditMode = (value) => {
  return {
    type: ACTIONS.SET_EDIT_MODE,
    value,
  };
};
