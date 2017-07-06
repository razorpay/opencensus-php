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
    const additionalFieldsComp = (
      <div class="row">
        <div class="col-md-4">
          <label class="form-label">
            Draft
            <input name="draft" type="checkbox" />
          </label>
        </div>

        <div class="col-md-4">
          <label class="form-label">
            Sms Notify
            <input name="sms_notify" type="checkbox" />
          </label>
        </div>

        <div class="col-md-4">
          <label class="form-label">
            Email Notify
            <input name="email_notify" type="checkbox" />
          </label>
        </div>
      </div>
    );

    return (
      <BatchUpload
        batchType="payment_link"
        docUrl="https://docs.razorpay.com/v1/page/payment-links-batch-import"
        sampleUrl="https://dashboard.razorpay.com/files/sample_batch_payment_links.xlsx"
        closeUrl="/paymentlinks/batchuploads"
        title="payment links"
        additionalFields={['draft', 'sms_notify', 'email_notify']}
        additionalFieldsComp={additionalFieldsComp}
        modeFormatted={this.props.modeFormatted}
        {...this.props}
      />
    );
  }
}
