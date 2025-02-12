import { Component } from 'react';
import { connect } from 'react-redux';

import BatchList from 'merchant/containers/BatchNew/List';
import BatchUpload from 'merchant/containers/BatchNew/Upload';
import setGaTrack from 'merchant/containers/BatchNew/ga';
import {
  fetchLAReversalsBatches as fetchAll,
  createLinkedAccountReversalsBatch as createBatch,
  validateLinkedAccountReversalsBatch as validateBatch,
  batchDownload,
} from 'merchantLA/reducers/batches';
import { openModal } from 'merchant_common/reducers/modals';

const gaEvents = setGaTrack('LA Dashboard - Reversals BU');

class BatchListContainer extends Component {
  renderUploadModal = () => {
    return (
      <BatchUpload
        batchType="linked_account_reversal"
        maxRows={50000}
        maxFileSize={10485760}
        gaEvents={gaEvents}
        createBatch={this.props.createBatch}
        validateBatch={this.props.validateBatch}
        docUrl="https://razorpay.com/docs/route/dashboard/linked-account-dashboard/#batch-refunds"
        sampleUrl="/files/sample_batch_linked_account_reversals.xlsx"
      />
    );
  };

  render() {
    return (
      <div className="linked-account-reversal-batch">
        <BatchList
          form="batchListFilter"
          docUrl="https://razorpay.com/docs/route/dashboard/linked-account-dashboard/#batch-refunds"
          sampleUrl="/files/sample_batch_linked_account_reversals.xlsx"
          batchType="linked_account_reversal"
          renderUploadModal={this.renderUploadModal}
          gaEvents={gaEvents}
          extraPropBatchDownload={this.props.batchDownload}
          {...this.props}
        />
      </div>
    );
  }
}

export default connect((state) => state.batches, {
  fetchAll,
  createBatch,
  validateBatch,
  openModal,
  batchDownload,
})(BatchListContainer);
