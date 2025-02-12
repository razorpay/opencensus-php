import React, { Component } from 'react';
import { connect } from 'react-redux';

import { fetchReversal } from 'merchant/reducers/marketplace/reversal';
import { fetchTransfer } from 'merchant/reducers/marketplace/transfers/details';
import ReversalDetails from 'merchant/views/Marketplace/Reversals/components/Details';

class ReversalDetailsContainer extends Component {
  fetchData(reversalId) {
    if (!reversalId) {
      return;
    }

    this.props.fetchReversal(reversalId).then((resp) => {
      if (this.props.notAllowFetchTransfer) return;

      if (resp) {
        this.props.fetchTransfer(resp.transfer_id);
      }
    });
  }

  UNSAFE_componentWillMount() {
    this.fetchData(this.props.id);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetchData(nextProps.id);
    }
  }

  render() {
    const { reversal, transfer, onClose, user } = this.props;
    let statusMsg = {};

    const errors = reversal.errors || transfer.errors;

    if (errors) {
      statusMsg = {
        type: 'error',
        message: errors,
      };
    }

    return (
      <ReversalDetails
        reversal={reversal.entity}
        transfer={transfer.entity}
        isLoading={reversal.loading || transfer.loading}
        statusMsg={statusMsg}
        onClose={onClose}
        merchant={user.merchants[user.current]}
      />
    );
  }
}

export default connect(
  (state) => ({
    reversal: state.reversal,
    transfer: state.transfer,
    user: state.session.user,
  }),
  {
    fetchTransfer,
    fetchReversal,
  },
)(ReversalDetailsContainer);
