import { Component, Fragment } from 'react';
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
  constructor(props) {
    super(props);
    //hotjar integration
    if (typeof window.hj === 'function') {
      window.hj('trigger', 'batch_payment_links');
      window.hj('tagRecording', ['batch_payment_links']);
    }
  }

  sendAll = item => {
    this.props.openModal({
      size: 'small',
      component: (
        <SendAllLinks batchId={item.id} fetchAll={this.props.fetchAll} />
      ),
    });
  };

  sendAllLinks = item => {
    if (['created', 'failure'].indexOf(item.status) < 0) {
      const allowSendAll = allowSendAllLinks(item);
      return (
        <button
          key={item.id}
          class="btn btn-xs btn-default"
          onClick={() => this.sendAll(item)}
          disabled={!allowSendAll}
        >
          {allowSendAll ? 'Send all links' : 'All links sent'}
        </button>
      );
    }
    return null;
  };

  render() {
    return (
      <BatchList
        form="batchListFilter"
        count={this.state.count}
        skip={this.state.skip}
        paginate={this.paginate}
        onSubmit={this.search}
        docUrl="https://razorpay.com/docs/private/payment-links-batch-uploads/"
        sampleUrl="/files/sample_batch_payment_links_v2.xlsx"
        sendAll={this.sendAll}
        batchType="payment_link"
        batchActions={[this.sendAllLinks]}
        {...this.props}
      />
    );
  }
}

function allowSendAllLinks(batch) {
  // config object will not be available for older batches
  // duplicate batches will have no success count
  if (Object.keys(batch.config).length) {
    if (
      parseInt(batch.config.sms_notify) > 0 ||
      parseInt(batch.config.email_notify) > 0
    ) {
      //if more than 0 payment link(s) has been sent, disable the btn
      return false;
    } else {
      return true;
    }
  }
  //disable for older batches
  return false;
}
