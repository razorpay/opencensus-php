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
          <span class="form-option">Draft</span>
          <label>
            True
            <input name="draft" value="1" type="radio" />
          </label>
          <label>
            False
            <input name="draft" value="0" type="radio" />
          </label>
        </div>

        <div class="col-md-4">
          <span class="form-option">Sms Notify</span>
          <label>
            True
            <input name="sms_notify" value="1" type="radio" />
          </label>
          <label>
            False
            <input name="sms_notify" value="0" type="radio" />
          </label>
        </div>

        <div class="col-md-4">
          <span class="form-option">Email Notify</span>
          <label>
            True
            <input name="email_notify" value="1" type="radio" />
          </label>
          <label>
            False
            <input name="email_notify" value="0" type="radio" />
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
