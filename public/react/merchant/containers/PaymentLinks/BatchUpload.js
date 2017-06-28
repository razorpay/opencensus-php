import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import BatchUpload from 'merchant/components/Batch/Upload';

import { showNotification } from 'rzp/modules/notifications';
import {
  uploadPaymentLinkBatch as uploadBatch,
} from 'merchant/modules/batches';

@withRouter
@connect(state => state.session, { uploadBatch, showNotification })
export default class BatchUploadContainer extends Component {
  render() {
    return (
      <BatchUpload
        batchType="payment_link"
        docUrl="https://docs.razorpay.com/v1/page/payment-links-batch-import"
        sampleUrl="https://dashboard.razorpay.com/files/sample_batch_payment_links.xlsx"
        closeUrl="/paymentlinks/batchuploads"
        title="payment links"
        modeFormatted={this.props.modeFormatted}
        {...this.props}
      />
    );
  }
}
