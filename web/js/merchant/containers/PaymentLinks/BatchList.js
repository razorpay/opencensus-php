import { Component } from 'react';
import { connect } from 'react-redux';
import ListContainer from 'merchant/containers/ListContainer';
import BatchList from 'merchant/containers/Batch/List';
import IssueAllLinks from './IssueAllLinks';
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
  issueAll = item => {
    this.props.openModal({
      size: 'small',
      component: <IssueAllLinks batchId={item.id} />,
    });
  };

  render() {
    let issuableIdList = [];

    issuableIdList = this.props.issuableIdList; // array of batch ids for which to show 'issue all links' btn

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
        issuableIdList={issuableIdList}
        {...this.props}
      />
    );
  }
}
