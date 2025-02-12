import React, { Component } from 'react';
import { connect } from 'react-redux';

import ReversalDetails from 'merchantLA/components/Marketplace/Reversals/Details';
import { fetchReversal } from 'merchantLA/reducers/marketplace/reversal';

import setGaTrack from './ga';

const gaEvents = setGaTrack('LA Dashboard - Reversals');

class ReversalDetailsContainer extends Component {
  fetchData(reversalId) {
    if (!reversalId) {
      return;
    }

    this.props.fetchReversal(reversalId);
  }

  UNSAFE_componentWillMount() {
    this.fetchData(this.props.id);
  }

  componentDidMount() {
    gaEvents.trackOpenDetails();
  }

  componentWillUnmount() {
    gaEvents.trackCloseDetails();
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetchData(nextProps.id);
    }
  }

  render() {
    const { entity, loading, errors, onClose, user } = this.props;
    let statusMsg = {};

    if (errors) {
      statusMsg = {
        type: 'error',
        message: errors,
      };
    }

    return (
      <ReversalDetails
        reversal={entity}
        isLoading={loading}
        statusMsg={statusMsg}
        onClose={onClose}
        parentAccountName={user.marketplace_merchant_name}
        merchant={user.merchants[user.current]}
        isRefundsAllowed={user.isAllowedLARefunds}
      />
    );
  }
}

export default connect((state) => ({ ...state.reversal, ...state.session }), {
  fetchReversal,
})(ReversalDetailsContainer);
