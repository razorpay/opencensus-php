import { Component } from 'react';
import { connect } from 'react-redux';
import ListContainer from 'merchant/containers/ListContainer';
import BatchList from 'merchant/components/Batch/List';

import { showNotification } from 'rzp/modules/notifications';
import {
  fetchPaymentLinkBatches as fetchAll,
  issuePaymentLinkBatch,
} from 'merchant/modules/batches';

@connect(
  state => {
    return {
      mode: state.session.mode,
      ...state.paymentlinkbatches,
    };
  },
  { fetchAll, showNotification, issuePaymentLinkBatch }
)
export default class BatchListContainer extends ListContainer {
  issueAll = item => {
    this.context.confirm({
      message: 'Issue all payment links?',
      affirmativeLabel: 'Yes',
      affirmativePendingLabel: 'Issuing...',
      action: () => {
        this.props
          .issuePaymentLinkBatch(item.id)
          .then(() => {
            this.props.showNotification({
              type: 'success',
              message: 'Successful',
            });
          })
          .catch(({ errors }) => {
            this.props.showNotification({
              type: 'error',
              message: errors,
            });
          });
      },
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
