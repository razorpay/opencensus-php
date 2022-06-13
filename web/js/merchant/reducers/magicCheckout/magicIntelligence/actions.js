import { merchantFetch, merchantFetchWithContentType } from 'merchant/utils/ajax';
import { createBatch, validateBatch } from 'merchant/reducers/batches';

const REDUCER_NAMESPACE = 'BLOCKLIST';
const REDUCER_NAMESPACE2 = 'ALLOWLIST';

export const ACTIONS = {
  FETCH_BLOCKLIST: `${REDUCER_NAMESPACE}_FETCH`,
  FETCH_BLOCKLIST_PENDING: `${REDUCER_NAMESPACE}_FETCH::PENDING`,
  FETCH_BLOCKLIST_SUCCESS: `${REDUCER_NAMESPACE}_FETCH::SUCCESS`,
  FETCH_BLOCKLIST_ERROR: `${REDUCER_NAMESPACE}_FETCH::ERROR`,

  UPLOAD_BLOCKLIST: `${REDUCER_NAMESPACE}_UPLOAD`,
  UPLOAD_BLOCKLIST_PENDING: `${REDUCER_NAMESPACE}_UPLOAD::PENDING`,
  UPLOAD_BLOCKLIST_SUCCESS: `${REDUCER_NAMESPACE}_UPLOAD::SUCCESS`,
  UPLOAD_BLOCKLIST_ERROR: `${REDUCER_NAMESPACE}_UPLOAD::ERROR`,

  DELETE_BLOCKLIST: `${REDUCER_NAMESPACE}_DELETE`,
  DELETE_BLOCKLIST_PENDING: `${REDUCER_NAMESPACE}_DELETE::PENDING`,
  DELETE_BLOCKLIST_SUCCESS: `${REDUCER_NAMESPACE}_DELETE::SUCCESS`,
  DELETE_BLOCKLIST_ERROR: `${REDUCER_NAMESPACE}_DELETE::ERROR`,

  FETCH_ALLOWLIST: `${REDUCER_NAMESPACE2}_FETCH`,
  FETCH_ALLOWLIST_PENDING: `${REDUCER_NAMESPACE2}_FETCH::PENDING`,
  FETCH_ALLOWLIST_SUCCESS: `${REDUCER_NAMESPACE2}_FETCH::SUCCESS`,
  FETCH_ALLOWLIST_ERROR: `${REDUCER_NAMESPACE2}_FETCH::ERROR`,

  UPLOAD_ALLOWLIST: `${REDUCER_NAMESPACE2}_UPLOAD`,
  UPLOAD_ALLOWLIST_PENDING: `${REDUCER_NAMESPACE2}_UPLOAD::PENDING`,
  UPLOAD_ALLOWLIST_SUCCESS: `${REDUCER_NAMESPACE2}_UPLOAD::SUCCESS`,
  UPLOAD_ALLOWLIST_ERROR: `${REDUCER_NAMESPACE2}_UPLOAD::ERROR`,

  DELETE_ALLOWLIST: `${REDUCER_NAMESPACE2}_DELETE`,
  DELETE_ALLOWLIST_PENDING: `${REDUCER_NAMESPACE2}_DELETE::PENDING`,
  DELETE_ALLOWLIST_SUCCESS: `${REDUCER_NAMESPACE2}_DELETE::SUCCESS`,
  DELETE_ALLOWLIST_ERROR: `${REDUCER_NAMESPACE2}_DELETE::ERROR`,
};

const CUSTOM_HEADERS = {
  'X-Creator-Id': window.rzp_user?.user?.id,
  'X-Creator-Type': window.rzp_user?.role,
};
const BATCH_TYPE_BLOCKLIST = 'one_cc_cod_eligibility_attribute_blacklist_upsert';
export const createBlocklist = createBatch(
  BATCH_TYPE_BLOCKLIST,
  BATCH_TYPE_BLOCKLIST,
  CUSTOM_HEADERS,
);
export const validateBlocklist = validateBatch(BATCH_TYPE_BLOCKLIST);

const BATCH_TYPE_ALLOWLIST = 'one_cc_cod_eligibility_attribute_whitelist_upsert';
export const createAllowlist = createBatch(
  BATCH_TYPE_ALLOWLIST,
  BATCH_TYPE_ALLOWLIST,
  CUSTOM_HEADERS,
);
export const validateAllowlist = validateBatch(BATCH_TYPE_ALLOWLIST);

export const uploadBlocklist = (payload) => {
  return {
    type: ACTIONS.UPLOAD_BLOCKLIST,
    payload: merchantFetchWithContentType({
      url: '1cc/rto_prediction_service/cod_eligibility_attribute/blacklist/upsert/bulk',
      method: 'post',
      data: payload,
      headers: CUSTOM_HEADERS,
    }),
    data: payload,
  };
};

export const fetchBlocklist = (payload = {}) => {
  return {
    type: ACTIONS.FETCH_BLOCKLIST,
    payload: merchantFetch({
      url: '1cc/rto_prediction_service/cod_eligibility_attribute/blacklist',
      data: payload,
    }),
  };
};

export const deleteBlocklist = (item_id) => {
  return {
    type: ACTIONS.DELETE_BLOCKLIST,
    payload: merchantFetch({
      url: `1cc/rto_prediction_service/cod_eligibility_attribute/${item_id}`,
      method: 'delete',
    }),
    item_id,
  };
};

export const uploadAllowlist = (payload) => {
  return {
    type: ACTIONS.UPLOAD_ALLOWLIST,
    payload: merchantFetchWithContentType({
      url: '1cc/rto_prediction_service/cod_eligibility_attribute/whitelist/upsert/bulk',
      method: 'post',
      data: payload,
      headers: CUSTOM_HEADERS,
    }),
    data: payload,
  };
};

export const fetchAllowlist = (payload = {}) => {
  return {
    type: ACTIONS.FETCH_ALLOWLIST,
    payload: merchantFetch({
      url: '1cc/rto_prediction_service/cod_eligibility_attribute/whitelist',
      data: payload,
    }),
  };
};

export const deleteAllowlist = (item_id) => {
  return {
    type: ACTIONS.DELETE_ALLOWLIST,
    payload: merchantFetch({
      url: `1cc/rto_prediction_service/cod_eligibility_attribute/${item_id}`,
      method: 'delete',
    }),
    item_id,
  };
};
