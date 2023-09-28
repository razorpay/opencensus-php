import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import BatchUpload from './components/BatchUpload';
import { getCustomURL } from 'merchant/components/DocsLink';
import { uploadRefundBatch as uploadBatch } from 'merchant/reducers/batches';
import { showNotification } from 'merchant_common/reducers/notifications';
import { SAMPLE_BATCH_REFUND_FILE } from './List';
import { bindActionCreators } from 'redux';

class BatchUploadContainer extends Component {
  render() {
    return (
      <BatchUpload
        batchType="refund"
        docUrl={getCustomURL('https://razorpay.com/docs/payments/refunds/batch/')}
        sampleUrl={SAMPLE_BATCH_REFUND_FILE}
        closeUrl="/refunds/batchuploads"
        title="refunds"
        modeFormatted={this.props.modeFormatted}
        {...this.props}
      />
    );
  }
}

export default withRouter(
  connect(
    (state) => state.session,
    (dispatch) => bindActionCreators({ uploadBatch, showNotification }, dispatch),
  )(BatchUploadContainer),
);
