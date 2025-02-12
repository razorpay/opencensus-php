import React, { Component } from 'react';
import { connect } from 'react-redux';
import SettlementDetails from 'merchantLA/components/Settlements/Details';
import * as SettlementActions from 'merchantLA/reducers/settlements/details';
import { getEventCategoryFromPath } from 'common/utils/rzp-utils';
import { compose } from 'redux';

class SettlementDetailsContainer extends Component {
  UNSAFE_componentWillMount() {
    this.props.fetchItem(this.props.id);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchItem(nextProps.id);
    }
  }

  fetchBreakupDetails = (settlement) => {
    return this.props.fetchBreakupDetails(settlement);
  };

  componentDidMount() {
    const { closeUrl, id } = this.props,
      eventCategory = getEventCategoryFromPath(closeUrl);
    eventCategory &&
      window.rzpAnalytics({
        eventCategory: eventCategory,
        eventAction: 'Open Details - Settlements',
        eventLabel: `settlement_id=${id}`,
      });
  }

  componentWillUnmount() {
    const { closeUrl, id } = this.props,
      eventCategory = getEventCategoryFromPath(closeUrl);
    eventCategory &&
      window.rzpAnalytics({
        eventCategory: eventCategory,
        eventAction: 'Close Details - Settlements',
        eventLabel: `settlement_id=${id}`,
      });
  }

  render() {
    let { loading, error, settlement, breakupDetails } = this.props;
    let statusMsg = {};

    if (error) {
      statusMsg = {
        type: 'error',
        message: this.props.error,
      };
    }

    return (
      <SettlementDetails
        settlement={settlement}
        isLoading={loading}
        statusMsg={statusMsg}
        onToggleBreakupDetails={this.fetchBreakupDetails}
        breakupDetails={breakupDetails}
      />
    );
  }
}

export default compose(connect((state) => state.settlement, SettlementActions))(
  SettlementDetailsContainer,
);
