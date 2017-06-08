import React, { Component } from 'react';
import { connect } from 'react-redux';
import SettlementDetails from 'merchant/components/Settlements/Details';
import * as SettlementActions from 'merchant/modules/settlements/details';

@connect(state => state.settlement, SettlementActions)
export default class SettlementDetailsContainer extends Component {
  componentWillMount() {
    this.props.fetchItem(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchItem(nextProps.id);
    }
  }

  fetchBreakupDetails = settlement => {
    return this.props.fetchBreakupDetails(settlement);
  };

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
