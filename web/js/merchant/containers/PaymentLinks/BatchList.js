import { Component } from 'react';
import { connect } from 'react-redux';
import ListContainer from 'merchant/containers/ListContainer';
import BatchList from 'merchant/containers/BatchNew/List';
import SendAllLinks from 'merchant/containers/BatchNew/SendAllLinks';
import { openModal } from 'rzp/modules/modals';
import {
  fetchPaymentLinkBatches as fetchAll,
  fetchIssuableBatchList,
} from 'merchant/modules/batches';

@connect(
  state => {
    return {
      mode: state.session.mode,
      user: state.session.user,
      issuableIdList: state.paymentBatchIds.issuableIdList,
      ...state.paymentlinkbatches,
    };
  },
  { fetchAll, fetchIssuableBatchList, openModal }
)
export default class BatchListContainer extends ListContainer {
  sendAll = item => {
    this.props.openModal({
      size: 'small',
      component: (
        <SendAllLinks batchId={item.id} fetchAll={this.props.fetchAll} />
      ),
    });
  };

  render() {
    return (
      <BatchList
        form="batchListFilter"
        count={this.state.count}
        skip={this.state.skip}
        paginate={this.paginate}
        onSubmit={this.search}
        docUrl="https://docs.razorpay.com/v1/page/payment-links-batch-import"
        uploadUrl="/paymentlinks/batchuploads/new"
        sampleUrl="https://dashboard.razorpay.com/files/sample_batch_payment_links.xlsx"
        sendAll={this.sendAll}
        batchType="payment_link"
        {...this.props}
      />
    );
  }
}
