import { Component } from 'react';
import { connect } from 'react-redux';
import BatchDetails from 'merchant/containers/BatchNew/Details';
import { fetchPaymentLinkBatchesDetails as fetchBatchDetails } from 'merchant/modules/batches';

@connect(null, {
  fetchBatchDetails,
})
export default class PaymentLinksBatchDetailsContainer extends Component {
  render() {
    return (
      <BatchDetails
        id={this.props.id}
        fetchBatchDetails={this.props.fetchBatchDetails}
      />
    );
  }
}
