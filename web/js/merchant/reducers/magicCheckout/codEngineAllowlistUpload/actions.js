import { merchantFetch } from 'merchant/utils/ajax';

const REDUCER_NAMESPACE = 'MAGIC_COD_ENGINE_ALLOWLIST';

export const ACTIONS = {
  FETCH_LIST: `${REDUCER_NAMESPACE}_FETCH`,
  FETCH_LIST_PENDING: `${REDUCER_NAMESPACE}_FETCH::PENDING`,
  FETCH_LIST_SUCCESS: `${REDUCER_NAMESPACE}_FETCH::SUCCESS`,
  FETCH_LIST_ERROR: `${REDUCER_NAMESPACE}_FETCH::ERROR`,

  VALIDATE_LIST: `${REDUCER_NAMESPACE}_VALIDATE`,
  VALIDATE_LIST_PENDING: `${REDUCER_NAMESPACE}_VALIDATE::PENDING`,
  VALIDATE_LIST_SUCCESS: `${REDUCER_NAMESPACE}_VALIDATE::SUCCESS`,
  VALIDATE_LIST_ERROR: `${REDUCER_NAMESPACE}_VALIDATE::ERROR`,

  DELETE_LIST: `${REDUCER_NAMESPACE}_DELETE`,
  DELETE_LIST_PENDING: `${REDUCER_NAMESPACE}_DELETE::PENDING`,
  DELETE_LIST_SUCCESS: `${REDUCER_NAMESPACE}_DELETE::SUCCESS`,
  DELETE_LIST_ERROR: `${REDUCER_NAMESPACE}_DELETE::ERROR`,

  UPDATE_LIST: `${REDUCER_NAMESPACE}_UPDATE`,
  UPDATE_LIST_PENDING: `${REDUCER_NAMESPACE}_UPDATE::PENDING`,
  UPDATE_LIST_SUCCESS: `${REDUCER_NAMESPACE}_UPDATE::SUCCESS`,
  UPDATE_LIST_ERROR: `${REDUCER_NAMESPACE}_UPDATE::ERROR`,

  DOWNLOAD_LIST: `${REDUCER_NAMESPACE}_DOWNLOAD`,
  DOWNLOAD_LIST_PENDING: `${REDUCER_NAMESPACE}_DOWNLOAD::PENDING`,
  DOWNLOAD_LIST_SUCCESS: `${REDUCER_NAMESPACE}_DOWNLOAD::SUCCESS`,
  DOWNLOAD_LIST_ERROR: `${REDUCER_NAMESPACE}_DOWNLOAD::ERROR`,
};

export const fetchAllowlist = (payload) => {
  return {
    type: ACTIONS.FETCH_LIST,
    payload: merchantFetch({
      url: '1cc/shipping/cod/allowlist',
      data: payload,
    }),
  };
};

export const validateAllowlist = (file, progressTracker) => {
  const formData = new FormData();
  formData.append('file', file);

  return {
    type: ACTIONS.VALIDATE_LIST,
    payload: merchantFetch({
      url: '1cc/shipping/cod/allowlist',
      method: 'post',
      data: formData,
      onUploadProgress: progressTracker,
    }),
  };
};
export const updateAllowlist = (payload) => {
  const formData = new FormData();
  formData.append('file', payload.file);

  return {
    type: ACTIONS.UPDATE_LIST,
    payload: merchantFetch({
      url: '1cc/shipping/cod/allowlist',
      method: 'post',
      data: formData,
    }),
  };
};

export const deleteAllowlist = () => {
  return {
    type: ACTIONS.DELETE_LIST,
    payload: merchantFetch({
      url: '1cc/shipping/cod/allowlist',
      method: 'delete',
    }),
  };
};

export const downloadAllowlist = () => {
  return {
    type: ACTIONS.DOWNLOAD_LIST,
    payload: merchantFetch({
      url: '1cc/shipping/cod/allowlist/download',
    }),
  };
};
