import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import BatchList from 'merchant/containers/BatchNew/List';
import BatchPaymentsUpload from 'merchant/views/Transactions/BatchPayments/BatchUpload';
import setGaTrack from 'merchant/containers/BatchNew/ga';

import { fetchPaymentBatches as fetchAll } from 'merchant/reducers/batches';

const gaEvents = setGaTrack('Dashboard - Payments - BU');

@connect(null, { fetchAll })
export default class BatchListContainer extends Component {
  renderUploadModal = () => (
    <BatchPaymentsUpload
      gaEvents={gaEvents}
      docUrl="https://razorpay.com/docs/payment-methods/cards/batch-card-payments/"
      sampleUrl="https://cdn.razorpay.com/dashboard/sample_batch_payments.csv"
    />
  );

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
