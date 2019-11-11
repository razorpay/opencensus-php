import { set, merge } from 'rzp/utils/immutable';
import {
  getActionName,
  makeActionCollectionReducer,
} from 'merchantLA/reducers/collection';
import {
  makeEntityReducer,
  entityFetchPendingState,
  entityFetchErrorState,
} from 'rzp/modules/entity';
import { merchantFetch } from 'merchantLA/utils/ajax';

//Spelling it `batchs` instead of `batches` due to makeActionCollectionReducer use of singular namespace. see web/js/rzp/modules/collection.js
const BATCH_DOWNLOAD = 'BATCH_DOWNLOAD';

/* Batch Names */
const BATCH = 'BATCH';
const LA_REVERSALS = 'LA_REVERSALS';

const appendBatches = namespace => namespace + '_BATCHS';
const getCreateActionName = namespace => namespace + '_BATCH_CREATE';
const getValidateActionName = namespace => namespace + '_BATCH_VALIDATE';
const getFetchActionName = namespace => appendBatches(namespace) + '_FETCH';
const getFetchDetailAction = namespace => namespace + '_BATCHS_FETCH_DETAILS';

const BATCH_DETAILS = getFetchDetailAction(BATCH);
const LINKED_ACCOUNT_REVERSAL = getFetchDetailAction(LA_REVERSALS);

const fetchBatchAjax = id =>
  merchantFetch(`batches/${id}`).then(response => ({
    batch: response.data,
  }));

const fetchBatchesAjax = (params, type) => {
  params[Array.isArray(type) ? 'types' : 'type'] = type;
  return merchantFetch({
    url: 'batches',
    params: params,
  });
};

/* methods to create actions for validating batch */
const validateBatch = batchType => (file, progressTracker) => {
  let formData = new FormData();
  formData.append('file', file);
  formData.append('type', batchType);

  return {
    type: getValidateActionName(BATCH),
    payload: merchantFetch({
      url: 'batches/validate',
      method: 'post',
      data: formData,
      onUploadProgress: progressTracker,
    }),
  };
};

/* method to create action for create batch action */
const createBatch = batchType => data => {
  return {
    type: getCreateActionName(BATCH),
    payload: merchantFetch({
      url: 'batches',
      method: 'post',
      data: {
        type: batchType,
        file_id: data.file_id,
        name: data.name,
      },
    }).then(response => response.data),
  };
};

// common batch actions
export const batchDownload = batchId => {
  return {
    type: BATCH_DOWNLOAD,
    payload: merchantFetch(`batches/${batchId}/download`),
  };
};

export const fetchBatchStats = batchId =>
  merchantFetch({
    method: 'get',
    url: `batches/${batchId}/stats`,
  });

// reducers
export const batchesReducer = makeActionCollectionReducer(appendBatches(BATCH));

// linked_account_reversal
export const createLinkedAccountReversalsBatch = createBatch(
  'linked_account_reversal'
);
export const validateLinkedAccountReversalsBatch = validateBatch(
  'linked_account_reversal'
);

export const fetchLAReversalsBatchesDetails = params => {
  const id = params.id;

  params.with_config = '1';
  return {
    type: LINKED_ACCOUNT_REVERSAL,
    payload: Promise.all([
      fetchBatchAjax(id),
      // fetchBatchStats(id),
    ]),
  };
};

/* actions la reversals batches */
export const fetchLAReversalsBatches = params => {
  return {
    type: getFetchActionName(BATCH),
    payload: fetchBatchesAjax(params, 'linked_account_reversal'),
  };
};

const onLinkedAccountReversalsDetails = (state, { payload }) => {
  return merge(state, {
    loading: false,
    entity: {
      batch: payload[0].batch,
      // stats: payload[1].data.stats,
    },
  });
};

const customBatchDetailsSet = (fetchDetailAction, onSuccess) => ({
  [`${fetchDetailAction}::PENDING`]: entityFetchPendingState,
  [`${fetchDetailAction}::ERROR`]: entityFetchErrorState,
  [`${fetchDetailAction}::SUCCESS`]: onSuccess,
});

export const batchDetailsReducer = makeEntityReducer(BATCH_DETAILS, {
  ...customBatchDetailsSet(
    LINKED_ACCOUNT_REVERSAL,
    onLinkedAccountReversalsDetails
  ),
});
