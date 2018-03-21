import React, { Component } from 'react';
import { connect } from 'react-redux';

import * as ModalActions from 'rzp/modules/modals';
import * as NotificationsActions from 'rzp/modules/notifications';
import {
  fetchBatch,
  batchDownload,
  fetchBatchStats,
  fetchBatchInvoices,
} from 'merchant/modules/batches';

import BatchDetails from 'merchant/components/BatchNew/BatchDetails';

import { trackDetails } from './ga';

@connect(null, {
  fetchBatch,
  batchDownload,
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

  handleDownload = id => {
    let windowRef = window.open('', '_blank');
    this.props
      .batchDownload(id)
      .then(response => {
        windowRef.location.href = response.data.url;
      })
      .catch(({ errors }) => {
        windowRef.close();
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
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
          batch: batch,
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

  componentDidMount() {
    trackDetails('Open', this.props.id);
  }

  componentWillUnmount() {
    trackDetails('Close', this.props.id);
  }
  render() {
    return <BatchDetails onDownload={this.handleDownload} {...this.state} />;
  }
}
