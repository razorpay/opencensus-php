import { set } from 'rzp/utils/immutable';
import ajax from 'merchant/utils/ajax';
import { getActionName, makeCollectionReducer } from 'rzp/modules/collection';
import { merchantFetch } from 'rzp/utils/ajax';

const REFUND = 'REFUND_BATCHES';
const PAYMENT_LINK = 'PAYMENT_LINK_BATCHES';
const BATCH_DOWNLOAD = 'BATCH_DOWNLOAD';
const ISSUABLE_BATCHES = 'ISSUABLE_BATCHES';
const EDIT_ISSUABLE_BATCHES = 'EDIT_ISSUABLE_BATCHES';
const BATCH_VALIDATE = 'BATCH_VALIDATE';

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

const validateBatch = (actionType, batchType) => file => {
  let formData = new FormData();
  formData.append('file', file);
  formData.append('type', batchType);

  return {
    type: actionType,
    payload: merchantFetch({
      url: 'batches/validate',
      method: 'post',
      data: formData,
    }),
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

export const validatePaymentLinkBatch = validateBatch(
  BATCH_VALIDATE,
  'payment_link'
);

export const uploadRefundBatch = uploadBatch(REFUND, 'refund');
export const uploadPaymentLinkBatch = uploadBatch(PAYMENT_LINK, 'payment_link');

export const refundBatchesReducer = makeCollectionReducer(REFUND);
export const paymentLinkBatchesReducer = makeCollectionReducer(PAYMENT_LINK);

let paymentBatchIdsInitialState = {
  issuableIdList: [],
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
