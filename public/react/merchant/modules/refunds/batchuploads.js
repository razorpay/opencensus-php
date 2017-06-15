import ajax from 'merchant/utils/ajax';
import { getActionName, makeCollectionReducer } from 'rzp/modules/collection';

const BATCH_UPLOAD = 'BATCH_UPLOAD';

export const fetchBatchUploads = params => {
  return {
    type: getActionName(BATCH_UPLOAD),
    payload: ajax('/batches'),
  };
};

export const uploadBatchRefunds = file => {
  let formData = new FormData();
  formData.append('file', file);
  formData.append('type', 'refund');

  return {
    type: BATCH_UPLOAD,
    payload: ajax({
      url: '/batches',
      method: 'post',
      data: formData,
      processData: false,
      contentType: false,
    }),
  };
};

export default makeCollectionReducer(BATCH_UPLOAD);
