import { Component } from 'react';
import { connect } from 'react-redux';
import ListContainer from 'merchant/containers/ListContainer';
import BatchList from 'merchant/containers/Batch/List';
import IssueAllLinks from './IssueAllLinks';
import { openModal } from 'rzp/modules/modals';
import { fetchPaymentLinkBatches as fetchAll } from 'merchant/modules/batches';

@connect(
  state => {
    return {
      mode: state.session.mode,
      ...state.paymentlinkbatches,
    };
  },
  { fetchAll, openModal }
)
export default class BatchListContainer extends ListContainer {
  issueAll = item => {
    this.props.openModal({
      size: 'small',
      component: <IssueAllLinks batchId={item.id} />,
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
        issueAll={this.issueAll}
        {...this.props}
      />
    );
  }
}
