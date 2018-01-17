import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import BatchUpload from 'merchant/components/Batch/Upload';

import { uploadRefundBatch as uploadBatch } from 'merchant/modules/batches';
import { showNotification } from 'rzp/modules/notifications';

@withRouter
@connect(state => state.session, { uploadBatch, showNotification })
export default class BatchUploadContainer extends Component {
  render() {
    return (
      <BatchUpload
        batchType="refund"
        docUrl="https://docs.razorpay.com/v1/page/batch-refunds"
        sampleUrl="https://dashboard.razorpay.com/files/sample_batch_refund.xlsx"
        closeUrl="/refunds/batchuploads"
        title="refunds"
        modeFormatted={this.props.modeFormatted}
        {...this.props}
      />
    );
  }
}
