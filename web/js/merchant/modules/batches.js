import { set, merge } from 'rzp/utils/immutable';
import ajax from 'merchant/utils/ajax';
import {
  getActionName,
  makeCollectionReducer,
  makeActionCollectionReducer,
} from 'rzp/modules/collection';
import { merchantFetch } from 'rzp/utils/ajax';

const REFUND = 'REFUND_BATCHES';

//Spelling it `batchs` instead of `batches` due to makeActionCollectionReducer use of singular namespace. see web/js/rzp/modules/collection.js
const PAYMENT_LINK = 'PAYMENT_LINK_BATCHS';
const BATCH_DOWNLOAD = 'BATCH_DOWNLOAD';
const ISSUABLE_BATCHES = 'ISSUABLE_BATCHES';
const EDIT_ISSUABLE_BATCHES = 'EDIT_ISSUABLE_BATCHES';
/* New Batch Action Types */
const VALIDATE_BATCH = 'VALIDATE_BATCH';
const CREATE_BATCH = 'PAYMENT_LINK_BATCH_CREATE';
const FETCH_BATCH = 'FETCH_BATCH';
const FETCH_BATCH_STATS = 'FETCH_BATCH_STATS';
const FETCH_BATCH_INVOICES = 'FETCH_BATCH_INVOICES';
const NOTIFY_BATCH = 'NOTIFY_BATCH';
const PAYMENT = 'PAYMENT_BATCHES';
const PAYMENT_BATCH_CREATE = 'PAYMENT_BATCHE_CREATE';

const fetchBatchAjax = id => {
  return merchantFetch(`batches/${id}`).then(response => {
    return {
      data: {
        items: [response.data],
      },
    };
  });
};

const fetchBatchesAjax = (params, type) => {
  params.type = type;
  return merchantFetch({
    url: 'batches',
    params: params,
  });
};

export const fetchRefundBatches = params => {
  return {
    type: getActionName(REFUND),
    payload: params.id
      ? fetchBatchAjax(params.id)
      : fetchBatchesAjax(params, 'refund'),
  };
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

export const fetchPaymentLinkBatches = params => {
  //for new batches
  params.with_config = '1';

  return dispatch => {
    return dispatch({
      type: getActionName(PAYMENT_LINK),
      payload: params.id
        ? fetchBatchAjax(params.id)
        : fetchBatchesAjax(params, 'payment_link').then(res => {
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
    type: `${PAYMENT_LINK}_FETCH_DETAILS`,
    payload: Promise.all([
      fetchBatchAjax(id),
      fetchBatchStats(id),
      fetchBatchInvoices(id),
    ]),
  };
};

const validateBatch = (actionType, batchType) => (file, progressTracker) => {
  let formData = new FormData();
  formData.append('file', file);
  formData.append('type', batchType);

  return {
    type: actionType,
    payload: merchantFetch({
      url: 'batches/validate',
      method: 'post',
      data: formData,
      onUploadProgress: progressTracker,
    }),
  };
};

export const fetchPaymentBatches = params => {
  return {
    type: getActionName(PAYMENT),
    payload: params.id
      ? fetchBatchAjax(params.id)
      : fetchBatchesAjax(params, 'direct_debit'),
  };
};

const createBatch = (actionType, batchType) => data => {
  return {
    type: actionType,
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

export const batchDownload = batchId => {
  return {
    type: BATCH_DOWNLOAD,
    payload: merchantFetch(`batches/${batchId}/download`),
  };
};

export const fetchBatch = batchId => {
  return {
    type: FETCH_BATCH,
    payload: merchantFetch(`batches/${batchId}`).then(
      response => response.data
    ),
  };
};

export const fetchBatchStats = batchId =>
  merchantFetch({
    method: 'get',
    url: `batches/${batchId}/stats`,
  });

export const fetchBatchInvoices = batchId =>
  merchantFetch(`invoices?batch_id=${batchId}`);

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

export const createPaymentLinkBatch = createBatch(CREATE_BATCH, 'payment_link');
export const validatePaymentLinkBatch = validateBatch(
  VALIDATE_BATCH,
  'payment_link'
);
export const createPaymentsBatch = createBatch(
  PAYMENT_BATCH_CREATE,
  'direct_debit'
);

export const uploadRefundBatch = uploadBatch(REFUND, 'refund');
export const uploadPaymentLinkBatch = uploadBatch(PAYMENT_LINK, 'payment_link');

export const refundBatchesReducer = makeCollectionReducer(REFUND);

//List Reducer
export const paymentLinkBatchesReducer = makeActionCollectionReducer(
  PAYMENT_LINK
);
export const paymentBatchesReducer = makeActionCollectionReducer(PAYMENT);

let paymentBatchIdsInitialState = {
  issuableIdList: [],
};

let batchDetailsReducerState = {
  loading: true,
  item: {},
  error: null,
};

export const batchDetailsReducer = function(
  state = batchDetailsReducerState,
  action
) {
  switch (action.type) {
    case `${PAYMENT_LINK}_FETCH_DETAILS::PENDING`:
      return merge(state, {
        loading: true,
      });

    case `${PAYMENT_LINK}_FETCH_DETAILS::SUCCESS`:
      const payload = action.payload;
      return merge(state, {
        loading: false,
        item: {
          batch: payload[0].data.items[0],
          stats: payload[1].data.stats,
          invoices: payload[2].data.items,
        },
        error: null,
      });

    default:
      return state;
  }
};

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
