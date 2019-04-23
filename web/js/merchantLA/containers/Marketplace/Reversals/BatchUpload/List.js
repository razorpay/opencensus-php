import { Component } from 'react';
import { connect } from 'react-redux';

import BatchList from 'merchant/containers/BatchNew/List';
import BatchUpload from 'merchant/containers/BatchNew/Upload';
import { openModal } from 'rzp/modules/modals';
import {
  fetchLAReversalsBatches as fetchAll,
  createLinkedAccountReversalsBatch as createBatch,
  validateLinkedAccountReversalsBatch as validateBatch,
} from 'merchantLA/modules/batches';
import setGaTrack from 'merchant/containers/BatchNew/ga';

const gaEvents = setGaTrack('LA Dashboard - Reversals BU');

@connect(state => state.laReversalsBatches, {
  fetchAll,
  createBatch,
  validateBatch,
  openModal,
})
export default class BatchListContainer extends Component {
  renderUploadModal = () => {
    return (
      <BatchUpload
        batchType="linked_account_reversal"
        maxRows={50000}
        maxFileSize={10485760}
        gaEvents={gaEvents}
        createBatch={this.props.createBatch}
        validateBatch={this.props.validateBatch}
        docUrl="" // https://razorpay.com/docs/linked_account/batch-upload/" TODO: add the docs after docs are updated
        sampleUrl="/files/sample_batch_linked_account_reversals.xlsx"
      />
    );
  };

  render() {
    return (
      <div className="linked-account-reversal-batch">
        <BatchList
          form="batchListFilter"
          docUrl="" // https://razorpay.com/docs/linked_account/batch-upload/" TODO: add the docs after docs are updated
          sampleUrl="/files/sample_batch_linked_account_reversals.xlsx"
          batchType="linked_account_reversal"
          renderUploadModal={this.renderUploadModal}
          gaEvents={gaEvents}
          {...this.props}
        />
      </div>
    );
  }
}
