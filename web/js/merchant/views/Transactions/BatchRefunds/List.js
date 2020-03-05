import { Component } from 'react';
import { connect } from 'react-redux';
import ListContainer from 'merchant/containers/ListContainer';
import BatchList from './components/BatchList';

import { fetchRefundBatches as fetchAll } from 'merchant/reducers/batches';

@connect(
  state => {
    return {
      mode: state.session.mode,
      ...state.refundbatches,
    };
  },
  { fetchAll }
)
export default class BatchListContainer extends ListContainer {
  render() {
    return (
      <BatchList
        form="batchListFilter"
        count={this.state.count}
        skip={this.state.skip}
        paginate={this.paginate}
        onSubmit={this.search}
        docUrl="https://razorpay.com/docs/refunds/batch-refunds/"
        uploadUrl="/refunds/batchupload"
        {...this.props}
      />
    );
  }
}
