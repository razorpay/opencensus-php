import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import ListContainer from 'merchant/containers/ListContainer';
import BatchList from 'merchant/containers/BatchNew/List';
import BatchUpload from './BatchUpload';
import setGaTrack from 'merchant/containers/BatchNew/ga';

import { fetchPaymentBatches as fetchAll } from 'merchant/modules/batches';
import { openModal, closeModal } from 'rzp/modules/modals';

const gaEvents = setGaTrack('Dashboard - Payments - BU');

@connect(
  state => {
    return {
      mode: state.session.mode,
      user: state.session.user,
      ...state.paymentBatches,
    };
  },
  { fetchAll }
)
export default class BatchListContainer extends ListContainer {
  renderUploadModal = () => <BatchUpload gaEvents={gaEvents} />;

  render() {
    return (
      <BatchList
        form="batchListFilter"
        count={this.state.count}
        skip={this.paginate}
        paginate={this.paginate}
        onSubmit={this.search}
        docUrl="https://docs.razorpay.com/v1/page/batch-card-payments"
        sampleUrl="https://cdn.razorpay.com/dashboard/sample_batch_payments.csv"
        batchType="direct_debit"
        renderUploadModal={this.renderUploadModal}
        gaEvents={gaEvents}
        {...this.props}
      />
    );
  }
}
