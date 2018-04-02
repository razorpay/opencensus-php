import { Component } from 'react';
import { connect } from 'react-redux';

import ListContainer from 'merchant/containers/ListContainer';
import BatchList from 'merchant/containers/Batch/List';

import { fetchPaymentBatches as fetchAll } from 'merchant/modules/batches';

@connect(
  state => {
    return {
      mode: state.session.mode,
      ...state.paymentBatches,
    };
  },
  { fetchAll }
)
export default class BatchListContainer extends ListContainer {
  render() {
    return (
      <BatchList
        form="batchListFilter"
        batchIdIsLink
        count={this.state.count}
        skip={this.paginate}
        onSubmit={this.search}
        docUrl="https://docs.razorpay.com/v1/page/batch-refunds"
        uploadUrl="/refunds/batchupload"
        {...this.props}
      />
    );
  }
}
