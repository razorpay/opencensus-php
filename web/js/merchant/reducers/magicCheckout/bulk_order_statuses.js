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

const BATCH_TYPE = 'fulfillment_order_update';
const REDUCER_NAMESPACE = `${BATCH_TYPE}_BATCHES`;

export const fetchAllOrderStatusBatches = (params = {}) => ({
  type: getActionName(REDUCER_NAMESPACE),
  payload: params.id ? fetchBatchAjax(params.id) : fetchBatchesAjax(params, BATCH_TYPE),
});

export const createOrderStatusBatch = createBatch(BATCH_TYPE, BATCH_TYPE);
export const validateOrderStatusBatch = validateBatch(BATCH_TYPE);

export const orderStatusBatchesReducer = makeActionCollectionReducer(REDUCER_NAMESPACE, {
  [`${REDUCER_NAMESPACE}_FETCH::PENDING`]: listFetchPendingState,
  [`${REDUCER_NAMESPACE}_FETCH::SUCCESS`]: listFetchSuccessState,
  [`${REDUCER_NAMESPACE}_FETCH::ERROR`]: listFetchErrorState,
});
