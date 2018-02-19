import React, { Component } from 'react';
import { connect } from 'react-redux';

import * as ModalActions from 'rzp/modules/modals';
import * as NotificationsActions from 'rzp/modules/notifications';
import {
  fetchBatch,
  fetchBatchStats,
  fetchBatchInvoices,
} from 'merchant/modules/batches';

import BatchDetails from 'merchant/components/BatchNew/BatchDetails';

@connect(null, {
  fetchBatch,
  fetchBatchStats,
  fetchBatchInvoices,
  ...ModalActions,
  ...NotificationsActions,
})
export default class BatchDetailsContainer extends Component {
  state = {
    batch: {},
    stats: {},
    isLoading: true,
  };

  componentWillMount() {
    let { fetchBatch, fetchBatchStats, fetchBatchInvoices, id } = this.props;
    let requests = [
      fetchBatch(id),
      fetchBatchStats(id),
      fetchBatchInvoices(id),
    ];

    Promise.all(requests)
      .then(([batch, stats, invoices]) => {
        this.setState({
          batch: batch.data,
          invoices: invoices.data.items,
          stats: stats.data.stats,
          isLoading: false,
        });
      })
      .catch(error => {
        this.props.showNotification({
          type: 'error',
          message: 'Failed to fetch batch.',
        });
      });
  }
  render() {
    return <BatchDetails {...this.state} />;
  }
}
