import React from 'react';
import { connect } from 'react-redux';

import {
  createGCMSEmailDeliveryBatch,
  validateGCMSEmailDeliveryBatch,
} from 'merchant/reducers/batches';

import { BatchUploadWrapper } from './BatchUploadWrapper';
import { BATCH_TYPES, BATCH_UPLOAD_INFO_MESSAGES } from './constants';

const OrderEmailDeliveryBatchUpload = ({
  maxRows,
  createBatch,
  validateBatch,
  onSuccess,
}: {
  maxRows: number;
  createBatch: () => void;
  validateBatch: () => void;
  onSuccess: () => void;
}) => {
  const sampleUrl = `/files/sample_batch_gcms_upload_email_delivery_giftcards.xlsx`;

  return (
    <BatchUploadWrapper
      onSuccess={onSuccess}
      batchType={BATCH_TYPES.CREATE_GCMS_EMAIL_DELIVERY}
      docUrl={sampleUrl}
      title="Upload GCMS Delivery Batch"
      points={[`The number of rows should not exceed ${maxRows}.`].concat(
        BATCH_UPLOAD_INFO_MESSAGES,
      )}
      createBatch={createBatch}
      validateBatch={validateBatch}
    />
  );
};

export default connect(null, {
  createBatch: createGCMSEmailDeliveryBatch,
  validateBatch: validateGCMSEmailDeliveryBatch,
})(OrderEmailDeliveryBatchUpload);
