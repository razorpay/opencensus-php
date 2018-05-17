import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import ListContainer from 'merchant/containers/ListContainer';
import BatchList from 'merchant/containers/Batch/List';
import BatchUpload from './BatchUpload';

import { fetchPaymentBatches as fetchAll } from 'merchant/modules/batches';
import { openModal, closeModal } from 'rzp/modules/modals';

@withRouter
@connect(
  state => {
    return {
      mode: state.session.mode,
      ...state.paymentBatches,
    };
  },
  { fetchAll, openModal }
)
export default class BatchListContainer extends ListContainer {
  componentWillReceiveProps(nextProps) {
    if (nextProps.match.params.mode === 'new') {
      this.props.openModal({
        size: 'large',
        closeModal: () => {
          this.props.closeModal();
          this.props.history.push('/payments/batchuploads');
        },
        component: <BatchUpload />,
      });
    }
  }

  render() {
    return (
      <BatchList
        form="batchListFilter"
        showBatchName
        count={this.state.count}
        skip={this.paginate}
        onSubmit={this.search}
        docUrl="https://docs.razorpay.com/v1/page/batch-card-payments"
        uploadUrl="/payments/batchuploads/new"
        {...this.props}
      />
    );
  }
}
