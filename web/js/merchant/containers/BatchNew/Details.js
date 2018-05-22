import React, { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import * as ModalActions from 'rzp/modules/modals';
import * as NotificationsActions from 'rzp/modules/notifications';
import {
  batchDownload,
  fetchPaymentLinkBatchesDetails,
} from 'merchant/modules/batches';

import BatchDetails from 'merchant/components/BatchNew/BatchDetails';

import { trackDetails } from './ga';

@withRouter
@connect(
  state => {
    const batchDetails = state.batchDetails;
    return {
      isLoading: batchDetails.loading,
      error: batchDetails.error,
      ...batchDetails.item,
    };
  },
  {
    batchDownload,
    ...ModalActions,
    ...NotificationsActions,
  }
)
export default class BatchDetailsContainer extends Component {
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

  fetchData = id => {
    if (!id) return;
    this.props.fetchBatchDetails({ id });
  };

  componentWillMount() {
    this.fetchData(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetchData(nextProps.id);
    }
  }

  componentDidMount() {
    trackDetails('Open', this.props.id);
  }

  componentWillUnmount() {
    trackDetails('Close', this.props.id);
  }

  render() {
    return <BatchDetails onDownload={this.handleDownload} {...this.props} />;
  }
}
