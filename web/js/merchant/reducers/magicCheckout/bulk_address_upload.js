import {
  createBatch,
  validateBatch,
  fetchBatchAjax,
  fetchBatchesAjax,
} from 'merchant/reducers/batches';
import {
  getActionName,
  listFetchErrorState,
  listFetchPendingState,
  listFetchSuccessState,
  makeActionCollectionReducer,
} from 'merchant/reducers/collection';
import { merchantFetch } from 'merchant/utils/ajax';

const BATCH_TYPE = 'raw_address';
const REDUCER_NAMESPACE = `${BATCH_TYPE}_BATCHS`;

export const fetchAllAddressBatches = (params = {}) => ({
  type: getActionName(REDUCER_NAMESPACE),
  payload: params.id ? fetchBatchAjax(params.id) : fetchBatchesAjax(params, BATCH_TYPE),
});

export const downloadFailedAddress = (id = '') => ({
  type: `${REDUCER_NAMESPACE}_DOWNLOAD_FAILED_ADDRESS`,
  payload: merchantFetch({ url: `raw_address/file/${id.substring(6)}` }), // API signature accepts somerandomid instead of batch_somerandomid
});

export const createAddressBatch = createBatch(BATCH_TYPE, BATCH_TYPE);
export const validateAddressBatch = validateBatch(BATCH_TYPE);

export const addressBatchesReducer = makeActionCollectionReducer(REDUCER_NAMESPACE, {
  [`${REDUCER_NAMESPACE}_FETCH::PENDING`]: listFetchPendingState,
  [`${REDUCER_NAMESPACE}_FETCH::SUCCESS`]: listFetchSuccessState,
  [`${REDUCER_NAMESPACE}_FETCH::ERROR`]: listFetchErrorState,
});
