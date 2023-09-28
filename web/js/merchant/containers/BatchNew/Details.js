import React, { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import RTracking from 'react-tracking';

import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { batchDownload } from 'merchant/reducers/batches';

import BatchDetails from 'merchant/components/BatchNew/BatchDetails';

@connect(
  (state) => {
    const batchDetails = state.batchDetails;
    return {
      isLoading: batchDetails.loading,
      error: batchDetails.error,
      ...batchDetails.entity,
    };
  },
  {
    batchDownload,
    ...ModalActions,
    ...NotificationsActions,
  },
)
@RTracking(() => window.rzpQ.component('BatchDetails'))
class BatchDetailsContainer extends Component {
  handleDownload = (id) => {
    this.props.tracking.trackEvent(
      window.rzpQ.chargeAtWill().interaction(`download.details.initiate`),
    );
    this.props
      .batchDownload(id)
      .then((response) => {
        window.location = response.data.url;
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  fetchData = (id) => {
    if (!id) return;
    this.props.fetchBatchDetails({ id });
  };

  UNSAFE_componentWillMount() {
    this.fetchData(this.props.id);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetchData(nextProps.id);
    }
  }

  componentDidMount() {
    this.props.gaEvents.trackDetails('Open', this.props.id);
  }

  componentWillUnmount() {
    this.props.gaEvents.trackDetails('Close', this.props.id);
  }

  render() {
    return <BatchDetails onDownload={this.handleDownload} {...this.props} />;
  }
}

export default withRouter(BatchDetailsContainer);
