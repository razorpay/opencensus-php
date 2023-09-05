import React, { Component } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { withSplitzService } from 'common/splitz';
import { getEventCategoryFromPath } from 'common/utils/rzp-utils';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import * as RefundActions from 'merchant/reducers/refunds/details';
import RefundDetails from 'merchant/views/Transactions/v1/Refunds/components/RefundDetails';
import { getSelfServeSuccessData } from 'merchant/views/Transactions/v1/utils';

class RefundDetailsContainer extends Component {
  fetchItem(id) {
    const { fetchItem, splitz } = this.props;
    fetchItem(id).then((response) => {
      const selfServeSuccessData = getSelfServeSuccessData(
        'Refund Details Fetched',
        'Refund Details',
        splitz,
      );
      selfServeTrackSuccess(selfServeSuccessData);
      return response;
    });
  }

  UNSAFE_componentWillMount() {
    this.fetchItem(this.props.id);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetchItem(nextProps.id);
    }

    if (nextProps.refund) {
      const { id } = nextProps;
      window.rzpAnalytics?.({
        eventCategory: 'Dashboard - Refunds',
        eventAction: 'Open Details - Refunds',
        eventLabel: `refund_id=${id}`,
        speed_requested: nextProps.refund.speed_requested,
      });
    }
  }

  componentWillUnmount() {
    const { closeUrl, id } = this.props;
    const eventCategory = getEventCategoryFromPath(closeUrl);

    if (eventCategory)
      window.rzpAnalytics?.({
        eventCategory,
        eventAction: 'Close Details - Refunds',
        eventLabel: `refund_id=${id}`,
      });
  }

  viewRefundHistory = () => {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Refunds',
      eventAction: 'Open History - Refunds',
      eventLabel: `refund_id=${this.props.refund.id}`,
      speed_requested: this.props.refund.speed_requested,
    });
  };

  render() {
    const { loading, error, refund } = this.props;
    let statusMsg = {};

    if (error) {
      statusMsg = {
        type: 'error',
        message: error,
      };
    }

    return (
      <RefundDetails
        refund={refund}
        isLoading={loading}
        statusMsg={statusMsg}
        viewRefundHistory={this.viewRefundHistory}
      />
    );
  }
}

export default withSplitzService(
  connect(
    (state) => state.refund,
    (dispatch) => bindActionCreators(RefundActions, dispatch),
  )(RefundDetailsContainer),
);
