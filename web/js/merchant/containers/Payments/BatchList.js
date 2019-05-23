import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import BatchList from 'merchant/containers/BatchNew/List';
import BatchUpload from './BatchUpload';
import setGaTrack from 'merchant/containers/BatchNew/ga';

import { fetchPaymentBatches as fetchAll } from 'merchant/modules/batches';

const gaEvents = setGaTrack('Dashboard - Payments - BU');

@connect(null, { fetchAll })
export default class BatchListContainer extends Component {
  renderUploadModal = () => <BatchUpload gaEvents={gaEvents} />;

  render() {
    return (
      <BatchList
        form="batchListFilter"
        docUrl="https://razorpay.com/docs/payment-methods/cards/batch-card-payments/"
        sampleUrl="https://cdn.razorpay.com/dashboard/sample_batch_payments.csv"
        batchType="direct_debit"
        renderUploadModal={this.renderUploadModal}
        gaEvents={gaEvents}
        {...this.props}
      />
    );
  }
}
