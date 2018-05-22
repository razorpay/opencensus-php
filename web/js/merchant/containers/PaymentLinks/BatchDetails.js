import { Component } from 'react';
import { connect } from 'react-redux';
import BatchDetails from 'merchant/containers/BatchNew/Details';
import BatchStats from '../../components/BatchNew/Stats';

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
        renderDetails={renderBatchDetails}
      />
    );
  }
}

function renderBatchDetails(props) {
  const stats = props.stats;
  const statsTable = [
    { title: 'Total rows processed', value: stats.batch_total },
    { title: 'Payment links created', value: stats.issued_count },
    {
      title: 'Paid',
      value: <span class="text-success">{stats.paid_count}</span>,
    },
    {
      title: 'Expired',
      value: <span class="text-danger">{stats.expired_count}</span>,
    },
  ];
  return <BatchStats stats={statsTable} />;
}
