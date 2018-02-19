import React, { Component } from 'react';
import { connect } from 'react-redux';

import * as ModalActions from 'rzp/modules/modals';
import * as NotificationsActions from 'rzp/modules/notifications';
import { fetchBatch, fetchBatchStats } from 'merchant/modules/batches';

import BatchDetails from 'merchant/components/BatchNew/BatchDetails';

@connect(null, {
  fetchBatch,
  fetchBatchStats,
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
    let { fetchBatch, fetchBatchStats, id } = this.props;
    let requests = [fetchBatch(id), fetchBatchStats(id)];

    Promise.all(requests)
      .then(([batch, stats]) => {
        this.setState({
          batch: batch.data,
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
    let { batch, stats, isLoading } = this.state;
    return <BatchDetails batch={batch} stats={stats} isLoading={isLoading} />;
  }
}
