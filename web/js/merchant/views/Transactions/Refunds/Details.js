import React, { Component } from 'react';
import { connect } from 'react-redux';
import RefundDetails from 'merchant/views/Transactions/Refunds/components/RefundDetails';
import * as RefundActions from 'merchant/reducers/refunds/details';
import { getEventCategoryFromPath } from 'common/utils/rzp-utils';
import { bindActionCreators } from 'redux';

class RefundDetailsContainer extends Component {
  componentWillMount() {
    this.props.fetchItem(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchItem(nextProps.id);
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

export default connect(
  (state) => state.refund,
  (dispatch) => bindActionCreators(RefundActions, dispatch),
)(RefundDetailsContainer);
