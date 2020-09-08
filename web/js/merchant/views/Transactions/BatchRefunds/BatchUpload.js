import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import BatchUpload from './components/BatchUpload';

import { uploadRefundBatch as uploadBatch } from 'merchant/reducers/batches';
import { showNotification } from 'merchant_common/reducers/notifications';
import { SAMPLE_BATCH_REFUND_FILE } from './List';

@withRouter
@connect((state) => state.session, { uploadBatch, showNotification })
export default class BatchUploadContainer extends Component {
  render() {
    return (
      <BatchUpload
        batchType="refund"
        docUrl="https://razorpay.com/docs/refunds/batch-refunds/"
        sampleUrl={SAMPLE_BATCH_REFUND_FILE}
        closeUrl="/refunds/batchuploads"
        title="refunds"
        modeFormatted={this.props.modeFormatted}
        {...this.props}
      />
    );
  }
}
