import { set } from 'rzp/utils/immutable';
import ajax from 'merchant/utils/ajax';
import { getActionName, makeCollectionReducer } from 'rzp/modules/collection';

const REFUND = 'REFUND_BATCHES';
const PAYMENT_LINK = 'PAYMENT_LINK_BATCHES';
const BATCH_DOWNLOAD = 'BATCH_DOWNLOAD';
const ISSUABLE_BATCHES = 'ISSUABLE_BATCHES';

const fetchBatchAjax = id => {
  return ajax({
    url: '/user/generic',
    appendModeInQueryParam: true,
    data: {
      route_name: 'batch_fetch_by_id',
      url_params: JSON.stringify({
        '{id}': id,
      }),
    },
  }).then(response => {
    return {
      data: {
        items: [response.data],
      },
    };
  });
};

const fetchBatchesAjax = (params, type) => {
  return ajax({
    url: '/user/generic',
    appendModeInQueryParam: true,
    data: {
      route_name: 'batch_fetch_multiple',
      query_params: JSON.stringify({
        ...params,
        type: type,
      }),
    },
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

const fetchIssuableBatchList = batchIdList => {
  return {
    type: ISSUABLE_BATCHES,
    payload: ajax({
      url: '/user/generic',
      appendModeInQueryParam: true,
      data: {
        route_name: 'invoice_batches_issuable',
        query_params: JSON.stringify({
          batch_ids: batchIdList,
        }),
      },
    }),
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

const uploadBatch = (actionType, batchType) => (file, mode, extraFields) => {
  let formData = new FormData();
  formData.append('file', file);
  formData.append('file_name', 'file');
  formData.append('route_name', 'batch_create');
  formData.append('body[type]', batchType);
  formData.append('mode', mode);

  for (let key in extraFields) {
    if (extraFields.hasOwnProperty(key)) {
      formData.append(key, extraFields[key]);
    }
  }

  return {
    type: actionType,
    payload: ajax({
      url: '/user/generic',
      method: 'post',
      data: formData,
      appendModeInURL: false,
      processData: false,
      contentType: false,
    }),
  };
};

export const issuePaymentLinkBatch = (batchId, body) => {
  return {
    type: `${PAYMENT_LINK}_ISSUE`,
    payload: ajax({
      method: 'POST',
      url: '/user/generic',
      appendModeInQueryParam: true,
      data: {
        route_name: 'invoice_issue_by_batch',
        url_params: JSON.stringify({
          '{batchId}': batchId,
        }),
        body,
      },
    }),
  };
};

export const batchDownload = batchId => {
  return {
    type: BATCH_DOWNLOAD,
    payload: ajax({
      url: '/user/generic',
      appendModeInQueryParam: true,
      data: {
        route_name: 'batch_download_file',
        url_params: JSON.stringify({
          '{id}': batchId,
        }),
      },
    }),
  };
};

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

    default:
      return state;
  }
};
