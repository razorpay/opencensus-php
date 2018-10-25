import { set, merge } from 'rzp/utils/immutable';
import {
  getActionName,
  makeCollectionReducer,
  makeActionCollectionReducer,
} from 'merchant/modules/collection';
import {
  makeEntityReducer,
  entityFetchPendingState,
  entityFetchErrorState,
} from 'rzp/modules/entity';
import { merchantFetch } from 'merchant/utils/ajax';

const REFUND = 'REFUND_BATCHES';

//Spelling it `batchs` instead of `batches` due to makeActionCollectionReducer use of singular namespace. see web/js/rzp/modules/collection.js
const BATCH_DOWNLOAD = 'BATCH_DOWNLOAD';
const ISSUABLE_BATCHES = 'ISSUABLE_BATCHES';
const EDIT_ISSUABLE_BATCHES = 'EDIT_ISSUABLE_BATCHES';

/* Batch Names */
const BATCH = 'BATCH';
const PAYMENT_LINK = 'PAYMENT_LINK';

/* New Batch Action Types */
const NOTIFY_BATCH = 'NOTIFY_BATCH';

const appendBatches = namespace => namespace + '_BATCHS';
const getCreateActioName = namespace => namespace + '_BATCH_CREATE';
const getValidateActionName = namespace => namespace + '_BATCH_VALIDATE';
const getFetchActionName = namespace => appendBatches(namespace) + '_FETCH';
const getFetchDetailAction = namespace => namespace + '_BATCHS_FETCH_DETAILS';

const BATCH_DETAILS = getFetchDetailAction(BATCH);
const BATCH_LIST = getFetchActionName(BATCH);
const PAYMENT_LINK_DETAILS = getFetchDetailAction(PAYMENT_LINK);

const fetchBatchAjax = id => merchantFetch(`batches/${id}`);

const fetchBatchesAjax = (params, type) => {
  params.type = type;
  return merchantFetch({
    url: 'batches',
    params: params,
  });
};

export const fetchIssuableBatchList = batchIdList => {
  return {
    type: ISSUABLE_BATCHES,
    payload: merchantFetch({
      url: 'invoices/batches/issuable',
      params: {
        batch_ids: batchIdList,
      },
    }),
  };
};

// Removing 'Issue all links' btn from view
export const editIssuableBatchList = batchIdToRemove => {
  return {
    type: EDIT_ISSUABLE_BATCHES,
    batchIdToRemove,
  };
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

/* method to create action for fetching batch list */
const fetchBatches = (batchType, fetchActionName) => params => ({
  type: BATCH_LIST || fetchActionName,
  payload: fetchBatchesAjax(params, batchType),
});

/* method to create action for fetching batch details */
const fetchBatchDetails = (batchType, fetchDetailAction) => params => ({
  type: BATCH_DETAILS || fetchDetailAction,
  payload: fetchBatchAjax(params.id, batchType),
});

/* method to create action for create batch action */
const createBatch = batchType => data => {
  return {
    type: getCreateActioName(BATCH),
    payload: merchantFetch({
      url: 'batches',
      method: 'post',
      data: {
        type: batchType,
        ...data,
      },
    }).then(response => response.data),
  };
};

/* method to create action for upload batch action */
// currently used by only refund batches
const uploadBatch = (actionType, batchType) => (file, mode, extraFields) => {
  let formData = new FormData();
  formData.append('file', file);
  formData.append('type', batchType);

  for (let key in extraFields) {
    if (extraFields.hasOwnProperty(key)) {
      formData.append(key, extraFields[key]);
    }
  }

  return {
    type: actionType,
    payload: merchantFetch({
      url: 'batches',
      method: 'post',
      data: formData,
    }),
  };
};

/* extra methods for more details related to payment link batch */
export const fetchBatchStats = batchId =>
  merchantFetch({
    method: 'get',
    url: `batches/${batchId}/stats`,
  });

export const fetchBatchInvoices = batchId =>
  merchantFetch(`invoices?batch_id=${batchId}`);

/* actions currently used by only payment link batch */
export const issuePaymentLinkBatch = (batchId, data) => {
  return {
    type: `${PAYMENT_LINK}_ISSUE`,
    payload: merchantFetch({
      method: 'post',
      url: `invoices/batch/${batchId}/issue`,
      data,
    }),
  };
};

export const notifyBatch = (batchId, data) => {
  return {
    type: NOTIFY_BATCH,
    payload: merchantFetch({
      url: `invoices/batch/${batchId}/notify`,
      method: 'put',
      data,
    }),
  };
};

/* common batch actions */
export const batchDownload = batchId => {
  return {
    type: BATCH_DOWNLOAD,
    payload: merchantFetch(`batches/${batchId}/download`),
  };
};

/* actions refund batches */
export const fetchRefundBatches = params => {
  return {
    type: getActionName(REFUND),
    payload: params.id
      ? fetchBatchAjax(params.id)
      : fetchBatchesAjax(params, 'refund'),
  };
};

export const uploadRefundBatch = uploadBatch(REFUND, 'refund');

/* action for payment link batch */
export const fetchPaymentLinkBatches = params => {
  //for new batches
  params.with_config = '1';

  return dispatch => {
    return dispatch({
      type: BATCH_LIST,
      payload: fetchBatchesAjax(params, 'payment_link').then(res => {
        const listOfBatchIds = [];

        res.data.items.forEach(item => {
          listOfBatchIds.push(item.id);
        });

        dispatch(fetchIssuableBatchList(listOfBatchIds));

        return res;
      }),
    });
  };
};

export const fetchPaymentLinkBatchesDetails = params => {
  const id = params.id;

  params.with_config = '1';
  return {
    type: PAYMENT_LINK_DETAILS,
    payload: Promise.all([
      fetchBatchAjax(id),
      fetchBatchStats(id),
      fetchBatchInvoices(id),
    ]),
  };
};

export const createPaymentLinkBatch = createBatch('payment_link');
export const validatePaymentLinkBatch = validateBatch('payment_link');

/* direct debit batches */
export const createPaymentsBatch = createBatch('direct_debit');
export const fetchPaymentBatches = fetchBatches('direct_debit');

/* reducers */
export const refundBatchesReducer = makeCollectionReducer(REFUND);
export const batchesReducer = makeActionCollectionReducer(appendBatches(BATCH));

let paymentBatchIdsInitialState = {
  issuableIdList: [],
};

const onPaymentLinkDetails = (state, { payload }) =>
  merge(state, {
    loading: false,
    entity: {
      batch: payload[0].data,
      stats: payload[1].data.stats,
      invoices: payload[2].data.items,
    },
  });

const customBatchDetailsSet = (fetchDetailAction, onSuccess) => ({
  [`${fetchDetailAction}::PENDING`]: entityFetchPendingState,
  [`${fetchDetailAction}::ERROR`]: entityFetchErrorState,
  [`${fetchDetailAction}::SUCCESS`]: onSuccess,
});

export const batchDetailsReducer = makeEntityReducer(BATCH_DETAILS, {
  ...customBatchDetailsSet(PAYMENT_LINK_DETAILS, onPaymentLinkDetails),
});

export const PaymentBatchIdsReducer = function(
  state = paymentBatchIdsInitialState,
  action
) {
  switch (action.type) {
    case `${ISSUABLE_BATCHES}::SUCCESS`:
      return set(state, 'issuableIdList', action.payload.data);

    case 'EDIT_ISSUABLE_BATCHES':
      const index = state.issuableIdList.indexOf(action.batchIdToRemove);
      let issuableIdList = Object.assign([], state.issuableIdList);
      if (index > -1) {
        issuableIdList.splice(index, 1);
      }

      return set(state, 'issuableIdList', issuableIdList);

    default:
      return state;
  }
};
