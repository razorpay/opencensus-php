import { merchantFetch } from 'merchant/utils/ajax';

const BATCH_TYPE = 'onecc_rto_merchant_order_data';
const REDUCER_NAMESPACE = 'RTO_HISTORY_BATCHES';
const CUSTOM_HEADERS = {
  'X-Dashboard-User-Id': window.rzp_user?.user?.id,
};
const RTO_HISTORY_UPLOAD_URL = '1cc/rto_prediction_service/file_upload_audits';

export const ACTIONS = {
  FETCH_RTO_HISTORY: `${REDUCER_NAMESPACE}_FETCH`,
  FETCH_RTO_HISTORY_PENDING: `${REDUCER_NAMESPACE}_FETCH::PENDING`,
  FETCH_RTO_HISTORY_SUCCESS: `${REDUCER_NAMESPACE}_FETCH::SUCCESS`,
  FETCH_RTO_HISTORY_ERROR: `${REDUCER_NAMESPACE}_FETCH::ERROR`,

  VALIDATE_RTO_HISTORY: `${REDUCER_NAMESPACE}_VALIDATE`,
  VALIDATE_RTO_HISTORY_PENDING: `${REDUCER_NAMESPACE}_VALIDATE::PENDING`,
  VALIDATE_RTO_HISTORY_SUCCESS: `${REDUCER_NAMESPACE}_VALIDATE::SUCCESS`,
  VALIDATE_RTO_HISTORY_ERROR: `${REDUCER_NAMESPACE}_VALIDATE::ERROR`,

  RESET_FILE_ID: `${REDUCER_NAMESPACE}_RESET::FILE_ID`,
  CREATE_RTO_HISTORY: `${REDUCER_NAMESPACE}_CREATE`,
};

export const fetchAllRTOHistoryBatches = () => {
  return {
    type: ACTIONS.FETCH_RTO_HISTORY,
    payload: merchantFetch({
      url: `${RTO_HISTORY_UPLOAD_URL}/list`,
    }),
  };
};

export const validateRTOHistoryBatches = (file, progressTracker) => {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('type', BATCH_TYPE);
  formData.append('entity', 'orders_data');
  formData.append('name', `${window.rzp_user?.user?.id}_${Date.now()}`);

  return {
    type: ACTIONS.VALIDATE_RTO_HISTORY,
    payload: merchantFetch({
      url: 'ufh/files/upload',
      method: 'post',
      data: formData,
      headers: CUSTOM_HEADERS,
      onUploadProgress: progressTracker,
    }),
  };
};

export const createRTOHistoryBatches = (params) => {
  return {
    type: ACTIONS.CREATE_RTO_HISTORY,
    payload: merchantFetch({
      url: `${RTO_HISTORY_UPLOAD_URL}/create`,
      method: 'post',
      data: params,
      headers: CUSTOM_HEADERS,
    }),
  };
};

export const resetFileId = (fileId) => {
  return {
    type: ACTIONS.RESET_FILE_ID,
    payload: fileId,
  };
};
